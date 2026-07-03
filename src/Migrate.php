<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations;

use Composer\Semver\Comparator;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Webimpress\SafeWriter\FileWriter;

class Migrate
{
    /**
     * @var array{
     *     migrations: string,
     *     checkpoint?: string,
     *     source: string,
     *     target: string,
     * }
     */
    private array $config;

    private string $checkpoint = '0.0.0';

    /**
     * @var array{
     *  updated: 0|positive-int,
     *  skipped: 0|positive-int,
     *  deleted: 0|positive-int,
     *  moved: 0|positive-int,
     * }
     */
    private array $statistics = [
        'updated' => 0,
        'skipped' => 0,
        'deleted' => 0,
        'moved' => 0,
    ];

    public function __construct(
        private readonly OutputInterface $output,
        string $configFilename
    ) {
        $this->initialize($configFilename);
    }

    private function initialize(string $configFilename): void
    {
        if (file_exists($configFilename)) {
            $this->config = Yaml::parseFile($configFilename);
        } elseif (file_exists(\dirname(__DIR__).'/'.$configFilename)) {
            $this->config = Yaml::parseFile(\dirname(__DIR__).'/'.$configFilename);
        } else {
            throw new RuntimeException("Config file {$configFilename} not found.");
        }

        if (file_exists($this->checkpointFilename())) {
            $this->checkpoint = mb_trim(file_get_contents($this->checkpointFilename()) ?: throw new RuntimeException('Could not get checkpoint file content'));
        }
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        $list = $this->getListToProcess();

        $this->output->writeln('<options=bold>Files to process:</>');
        foreach ($list as $filename) {
            $filename = str_replace(\dirname(__DIR__), '…', $filename);
            $this->output->writeln(" - {$filename}");
        }

        return $list;
    }

    public function process(?string $onlyFilename = null): void
    {
        $list = $this->getListToProcess();

        $success = $this->processIterator($list, $onlyFilename);

        if ($success) {
            $output = sprintf(
                'Processed %s files. Updated: %s, deleted: %s, moved: %s, skipped: %s.',
                \count($list),
                $this->statistics['updated'],
                $this->statistics['deleted'],
                $this->statistics['moved'],
                $this->statistics['skipped']
            );
            $this->output->writeln("<fg=green>{$output}</>");

            // We only update the checkpoint if we process the list, not a single file
            $processed = $this->statistics['updated'] + $this->statistics['deleted'] + $this->statistics['moved'];
            if (! $onlyFilename && $processed > 0) {
                $this->output->writeln("Updating checkpoint to {$this->checkpoint}");
                FileWriter::writeFile($this->checkpointFilename(), $this->checkpoint);
            }
        }
    }

    /**
     * @param array<string, string> $list
     * @throws Exception
     */
    public function processIterator(array $list, ?string $onlyFilename = null): bool
    {
        if ($onlyFilename) {
            if (! \array_key_exists($onlyFilename, $list)) {
                throw new Exception("File '".$onlyFilename."' is not available in configured input folder.");
            }

            return $this->processFile($list[$onlyFilename]);
        }

        foreach ($list as $filename) {
            // Stop at the first failure so a broken migration cannot advance
            // the checkpoint or let subsequent (possibly dependent) migrations
            // run on top of a half-applied state.
            if (! $this->processFile($filename)) {
                return false;
            }
        }

        return true;
    }

    private function processFile(string $filename): bool
    {
        try {
            $this->doProcessFile($filename);
        } catch (\Throwable $e) {
            $this->output->writeln(sprintf(
                "<error>Failed to process migration '%s': %s</error>",
                $filename,
                $e->getMessage()
            ));

            return false;
        }

        return true;
    }

    private function doProcessFile(string $filename): void
    {
        $migration = Yaml::parseFile($filename, Yaml::PARSE_CUSTOM_TAGS);

        $sourceFilename = $this->safeJoin($this->config['source'], $migration['file']);
        if (is_readable($sourceFilename)) {
            $data = (array) Yaml::parseFile($sourceFilename, Yaml::PARSE_CUSTOM_TAGS);
        } else {
            $data = [];
        }

        // Special case for move
        if (\array_key_exists('move', $migration)) {
            $targetFilename = $this->safeJoin($this->config['target'], $migration['file']);
            $targetFilenameAfterMove = $this->safeJoin($this->config['target'], $migration['move']);

            $filesystem = new Filesystem();
            $filesystem->mkdir(\dirname($targetFilenameAfterMove));

            if (is_readable($targetFilename)) {
                // If the file already exists in the target folder (from previous migrations), move it in there
                $this->verboseOutput("Moving {$targetFilename} to {$targetFilenameAfterMove}");
                $filesystem->rename($targetFilename, $targetFilenameAfterMove);
            } elseif ($this->config['source'] !== $this->config['target']) {
                // Target is a different directory, copy it instead
                $this->verboseOutput("Copying (moving) {$sourceFilename} to {$targetFilenameAfterMove}");
                $filesystem->copy($sourceFilename, $targetFilenameAfterMove);
            } else {
                // Otherwise, move it from the source to target
                $this->verboseOutput("Moving {$sourceFilename} to {$targetFilenameAfterMove}");
                $filesystem->rename($sourceFilename, $targetFilenameAfterMove);
            }

            $this->statistics['moved']++;
        } else {
            $migratedData = $this->doMigration($sourceFilename, $data, $migration);

            $targetFilename = $this->safeJoin($this->config['target'], $migration['file']);
            if ($migratedData) {
                // Regular data, write the file...
                $output = Yaml::dump($migratedData, 4, 2, Yaml::DUMP_NULL_AS_TILDE);

                $filesystem = new Filesystem();
                $filesystem->mkdir(\dirname($targetFilename));

                FileWriter::writeFile($targetFilename, $output);
                // FileWriter::writeFile($outputFilename . '.bak',  Yaml::dump($data, 4, 2, Yaml::DUMP_NULL_AS_TILDE));

                $this->verboseOutput(" - Written file '" . $targetFilename . "'.");
                $this->statistics['updated']++;
            } elseif (\is_array($migratedData)) {
                // If the array is empty, we should _remove_ the target file
                $filesystem = new Filesystem();
                $filesystem->mkdir(\dirname($targetFilename));
                $filesystem->remove($targetFilename);

                $this->verboseOutput(" - Deleting file '" . $migration['file'] . "'.");
                $this->statistics['deleted']++;
            }
        }

        $this->setMaxCheckpoint($migration['since']);
    }

    /**
     * @phpstan-ignore missingType.iterableValue,missingType.iterableValue,missingType.iterableValue
     */
    private function doMigration(string $filename, array $data, array $migration): ?array
    {
        $displayname = sprintf('%s/%s', basename(\dirname($filename)), basename($filename));

        $this->verboseOutput('Migrating '.$displayname.': ');

        $result = null;

        if (\array_key_exists('add', $migration)) {
            $result = $this->doMigrationAdd($data, $migration);
        } elseif (\array_key_exists('delete', $migration)) {
            $result = $this->doMigrationDelete($migration);
        } elseif (\array_key_exists('remove', $migration)) {
            $result = $this->doMigrationRemove($data, $migration);
        }

        return $result;
    }

    /**
     * @phpstan-ignore missingType.iterableValue,missingType.iterableValue,missingType.iterableValue
     */
    private function doMigrationAdd(array $data, array $migration): ?array
    {
        $migratedData = ArrayMerge::merge($data, $migration['add']);

        if ($data === $migratedData) {
            $this->verboseOutput(" - File '".$migration['file']."' does not need updating");
            $this->statistics['skipped']++;

            return null;
        }

        $this->verboseOutput(' - Adding '.\count($migration['add']).' keys.');

        return $migratedData;
    }

    /**
     * @phpstan-ignore missingType.iterableValue,missingType.iterableValue,missingType.iterableValue
     */
    private function doMigrationRemove(array $data, array $migration): ?array
    {
        $migratedData = $data;
        ArrayMerge::removeArrayRecursively($migratedData, $migration['remove']);

        if ($data === $migratedData) {
            $this->verboseOutput(" - File '".$migration['file']."' does not need updating");
            $this->statistics['skipped']++;

            return null;
        }

        $this->verboseOutput(' - Removing '.\count($migration['remove']).' keys.');

        return $migratedData;
    }

    /**
     * @phpstan-ignore missingType.iterableValue,missingType.iterableValue
     */
    private function doMigrationDelete(array $migration): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    private function getListToProcess(): array
    {
        $finder = new Finder();

        $files = $finder->files()->in($this->config['migrations'])->name('m_*.yaml');

        $list = [];

        foreach ($files as $file) {
            $yaml = Yaml::parseFile($file->getRealPath(), Yaml::PARSE_CUSTOM_TAGS);

            if (! \is_array($yaml) || ! \array_key_exists('since', $yaml)) {
                throw new RuntimeException(sprintf(
                    "Migration '%s' is missing the required 'since' version key.",
                    $file->getFilename()
                ));
            }

            if (! \is_string($yaml['since'])) {
                // Unquoted YAML numbers are parsed as int/float, so `1.10`
                // becomes the float 1.1 and is silently mis-ordered. Require a
                // quoted string so the version is preserved exactly.
                throw new RuntimeException(sprintf(
                    "Migration '%s' has a non-string 'since' value; wrap the version "
                    .'in quotes (e.g. "1.10") so YAML does not parse it as a number.',
                    $file->getFilename()
                ));
            }

            if (Comparator::greaterThan($yaml['since'], $this->checkpoint)) {
                $list[$file->getFilename()] = $file->getRealPath();
            }
        }

        ksort($list);

        return $list;
    }

    /**
     * Join a migration-supplied relative path onto a configured base
     * directory, rejecting any path that would escape that base (e.g. via
     * `../`). Returns the normalized, absolute path.
     */
    private function safeJoin(string $base, string $relative): string
    {
        $baseAbsolute = $this->normalizePath($this->toAbsolutePath($base));
        $combined = $this->normalizePath($baseAbsolute.'/'.$relative);

        if ($combined !== $baseAbsolute && ! str_starts_with($combined, $baseAbsolute.'/')) {
            throw new RuntimeException(sprintf(
                "Migration path '%s' escapes the configured '%s' directory.",
                $relative,
                $base
            ));
        }

        return $combined;
    }

    private function toAbsolutePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return (getcwd() ?: '.').'/'.$path;
    }

    /**
     * Lexically resolve `.` and `..` segments without touching the
     * filesystem, so it works for paths that do not exist yet.
     */
    private function normalizePath(string $path): string
    {
        $isAbsolute = str_starts_with($path, '/');
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return ($isAbsolute ? '/' : '').implode('/', $segments);
    }

    private function verboseOutput(string $str): void
    {
        if (! $this->output->isVerbose()) {
            return;
        }

        $this->output->writeln($str);
    }

    private function checkpointFilename(): string
    {
        if (\array_key_exists('checkpoint', $this->config)) {
            return $this->config['checkpoint'];
        }

        return $this->config['migrations'].'/checkpoint.txt';
    }

    private function setMaxCheckpoint(string $version): void
    {
        if (Comparator::greaterThan($version, $this->checkpoint)) {
            $this->checkpoint = $version;
        }
    }
}
