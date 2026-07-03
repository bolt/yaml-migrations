<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\Migrate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class deleteAndEmptySourceTest extends TestCase
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

    private function writeConfig(): string
    {
        $config = <<<YAML
        migrations: {$this->workspace}/migrations
        checkpoint: {$this->workspace}/checkpoint.txt
        source: {$this->workspace}/source
        target: {$this->workspace}/target
        YAML;
        $configFile = $this->workspace.'/config.yaml';
        $this->filesystem->dumpFile($configFile, $config);

        return $configFile;
    }

    private function runMigrations(): void
    {
        $output = $this->createMock(OutputInterface::class);
        (new Migrate($output, $this->writeConfig()))->process();
    }

    public function testDeleteRemovesTheTargetFile(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/target/gone.yaml', "hello: world\n");
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-delete.yaml', <<<YAML
        file: gone.yaml
        since: "3.0.0"

        delete: true
        YAML);

        $this->runMigrations();

        $this->assertFileDoesNotExist($this->workspace.'/target/gone.yaml');
    }

    public function testDeleteOfAnAbsentTargetIsHarmless(): void
    {
        // Nothing to delete: the run must complete without error.
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-delete.yaml', <<<YAML
        file: never-existed.yaml
        since: "3.0.0"

        delete: true
        YAML);

        $this->runMigrations();

        $this->assertFileDoesNotExist($this->workspace.'/target/never-existed.yaml');
    }

    public function testAddOnAnEmptySourceFileStillWritesTheTarget(): void
    {
        // Empty file parses to null; it must be treated as an empty array.
        $this->filesystem->dumpFile($this->workspace.'/source/empty.yaml', '');
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-add.yaml', <<<YAML
        file: empty.yaml
        since: "3.0.0"

        add:
          hello: world
        YAML);

        $this->runMigrations();

        $this->assertFileExists($this->workspace.'/target/empty.yaml');
        $this->assertStringContainsString('hello: world', (string) file_get_contents($this->workspace.'/target/empty.yaml'));
    }

    public function testAddOnAMissingSourceFileStillWritesTheTarget(): void
    {
        // No source file at all: the migration should seed a fresh target.
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-add.yaml', <<<YAML
        file: brand-new.yaml
        since: "3.0.0"

        add:
          hello: world
        YAML);

        $this->runMigrations();

        $this->assertFileExists($this->workspace.'/target/brand-new.yaml');
        $this->assertStringContainsString('hello: world', (string) file_get_contents($this->workspace.'/target/brand-new.yaml'));
    }

    public function testRemoveOnAnEmptySourceIsASkippedNoOp(): void
    {
        // Removing keys from an empty file changes nothing, so no file is
        // written and the run must not crash.
        $this->filesystem->dumpFile($this->workspace.'/source/empty.yaml', '');
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-remove.yaml', <<<YAML
        file: empty.yaml
        since: "3.0.0"

        remove:
          something: ~
        YAML);

        $this->runMigrations();

        $this->assertFileDoesNotExist($this->workspace.'/target/empty.yaml');
    }
}
