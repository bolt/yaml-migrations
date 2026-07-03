<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\Migrate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class moveTest extends TestCase
{
    private string $workspace;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->workspace = sys_get_temp_dir().'/yaml-migrations-'.uniqid('', true);
        $this->filesystem->mkdir([
            $this->workspace.'/migrations',
            $this->workspace.'/source',
            $this->workspace.'/target',
        ]);
        $this->filesystem->dumpFile($this->workspace.'/checkpoint.txt', '1.0.0');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->workspace);
    }

    private function writeConfig(string $source, string $target): string
    {
        $config = <<<YAML
        migrations: {$this->workspace}/migrations
        checkpoint: {$this->workspace}/checkpoint.txt
        source: {$source}
        target: {$target}
        YAML;
        $configFile = $this->workspace.'/config.yaml';
        $this->filesystem->dumpFile($configFile, $config);

        return $configFile;
    }

    private function writeMove(string $file, string $move): void
    {
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-move.yaml', <<<YAML
        file: {$file}
        since: "2.0.0"

        move: {$move}
        YAML);
    }

    public function testCopiesFromSourceToASeparateTargetLeavingSourceInPlace(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/source/foo.yaml', "hello: world\n");
        $this->writeMove('foo.yaml', 'bar.yaml');

        $output = $this->createMock(OutputInterface::class);
        (new Migrate($output, $this->writeConfig($this->workspace.'/source', $this->workspace.'/target')))->process();

        $this->assertFileExists($this->workspace.'/target/bar.yaml');
        $this->assertSame("hello: world\n", (string) file_get_contents($this->workspace.'/target/bar.yaml'));
        $this->assertFileExists($this->workspace.'/source/foo.yaml', 'A cross-directory move copies, leaving the source untouched.');
    }

    public function testRenamesAnAlreadyPresentTargetFile(): void
    {
        // The file was already placed in target by an earlier migration.
        $this->filesystem->dumpFile($this->workspace.'/target/foo.yaml', "hello: world\n");
        $this->writeMove('foo.yaml', 'bar.yaml');

        $output = $this->createMock(OutputInterface::class);
        (new Migrate($output, $this->writeConfig($this->workspace.'/source', $this->workspace.'/target')))->process();

        $this->assertFileExists($this->workspace.'/target/bar.yaml');
        $this->assertSame("hello: world\n", (string) file_get_contents($this->workspace.'/target/bar.yaml'));
        $this->assertFileDoesNotExist($this->workspace.'/target/foo.yaml', 'The original target file is renamed away.');
    }

    public function testMovesWithinTheSameDirectory(): void
    {
        // source === target: overwrite-in-place configuration.
        $dir = $this->workspace.'/inplace';
        $this->filesystem->mkdir($dir);
        $this->filesystem->dumpFile($dir.'/foo.yaml', "hello: world\n");
        $this->writeMove('foo.yaml', 'bar.yaml');

        $output = $this->createMock(OutputInterface::class);
        (new Migrate($output, $this->writeConfig($dir, $dir)))->process();

        $this->assertFileExists($dir.'/bar.yaml');
        $this->assertFileDoesNotExist($dir.'/foo.yaml', 'An in-place move renames the original file.');
    }

    public function testCreatesNestedDestinationDirectories(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/source/foo.yaml', "hello: world\n");
        $this->writeMove('foo.yaml', 'nested/deeper/bar.yaml');

        $output = $this->createMock(OutputInterface::class);
        (new Migrate($output, $this->writeConfig($this->workspace.'/source', $this->workspace.'/target')))->process();

        $this->assertFileExists($this->workspace.'/target/nested/deeper/bar.yaml');
    }
}
