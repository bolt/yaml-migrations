<?php

declare(strict_types=1);

namespace YamlMigrate;

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
     * }
     */
    private array $statistics = [
        'updated' => 0,
        'skipped' => 0,
        'deleted' => 0,
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
            die("Config file {$configFilename} not found.");
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
                'Processed %s files. Updated: %s, deleted: %s, skipped: %s.',
                \count($list),
                $this->statistics['updated'],
                $this->statistics['deleted'],
                $this->statistics['skipped']
            );
            $this->output->writeln("<fg=green>{$output}</>");

            // We only update the checkpoint if we process the list, not a single file
            if (! $onlyFilename && $this->statistics['updated'] > 0) {
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

        $success = true;

        foreach ($list as $filename) {
            $success = $success && $this->processFile($filename);
        }

        return $success;
    }

    private function processFile(string $filename): bool
    {
        $migration = Yaml::parseFile($filename, Yaml::PARSE_CUSTOM_TAGS);

        $inputFilename = sprintf('%s/%s', $this->config['source'], $migration['file']);
        $outputFilename = sprintf('%s/%s', $this->config['target'], $migration['file']);

        if (is_readable($inputFilename)) {
            $data = (array) Yaml::parseFile($inputFilename, Yaml::PARSE_CUSTOM_TAGS);
        } else {
            $data = [];
        }

        $migratedData = $this->doMigration($inputFilename, $data, $migration);

        if ($migratedData) {
            // Regular data, write the file...
            $output = Yaml::dump($migratedData, 4, 4, Yaml::DUMP_NULL_AS_TILDE);

            $filesystem = new Filesystem();
            $filesystem->mkdir(\dirname($outputFilename));

            FileWriter::writeFile($outputFilename, $output);
            // FileWriter::writeFile($outputFilename . '.bak',  Yaml::dump($data, 4, 4, Yaml::DUMP_NULL_AS_TILDE));

            $this->verboseOutput(" - Written file '".$outputFilename."'.");
            $this->statistics['updated']++;
        } elseif (\is_array($migratedData)) {
            // If the array is empty, we should _remove_ the target file
            $filesystem = new Filesystem();
            $filesystem->mkdir(\dirname($outputFilename));
            $filesystem->remove($outputFilename);

            $this->verboseOutput(" - Deleting file '".$migration['file']."'.");
            $this->statistics['deleted']++;
        }

        $this->setMaxCheckpoint($migration['since']);

        return true;
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

            if (Comparator::greaterThan($yaml['since'], $this->checkpoint)) {
                $list[$file->getFilename()] = $file->getRealPath();
            }
        }

        ksort($list);

        return $list;
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
