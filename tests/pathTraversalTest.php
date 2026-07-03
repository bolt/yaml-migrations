<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\Migrate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class pathTraversalTest extends TestCase
{
    private string $workspace;

    private string $outside;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $root = sys_get_temp_dir().'/yaml-migrations-'.uniqid('', true);
        $this->workspace = $root.'/work';
        $this->outside = $root.'/outside';
        $this->filesystem->mkdir([
            $this->workspace.'/migrations',
            $this->workspace.'/source',
            $this->workspace.'/target',
            $this->outside,
        ]);
        $this->filesystem->dumpFile($this->workspace.'/checkpoint.txt', '1.0.0');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(\dirname($this->workspace));
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

    public function testAddMigrationCannotWriteOutsideTarget(): void
    {
        $victim = $this->outside.'/victim.yaml';
        $this->filesystem->dumpFile($victim, "original: content\n");

        // `file` escapes the target directory via ../
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-escape.yaml', <<<YAML
        file: ../../outside/victim.yaml
        since: 2.0.0

        add:
          injected: true
        YAML);

        $output = $this->createMock(OutputInterface::class);
        $migrate = new Migrate($output, $this->writeConfig());

        try {
            $migrate->process();
        } catch (\Throwable) {
            // A thrown exception is an acceptable way to reject the migration.
        }

        $this->assertSame(
            "original: content\n",
            (string) file_get_contents($victim),
            'A migration must not be able to write outside the target directory.'
        );
    }

    public function testMoveMigrationCannotEscapeTarget(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/source/foo.yaml', "hello: world\n");

        // `move` escapes the target directory via ../
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-move.yaml', <<<YAML
        file: foo.yaml
        since: 2.0.0

        move: ../../outside/escaped.yaml
        YAML);

        $output = $this->createMock(OutputInterface::class);
        $migrate = new Migrate($output, $this->writeConfig());

        try {
            $migrate->process();
        } catch (\Throwable) {
            // A thrown exception is an acceptable way to reject the migration.
        }

        $this->assertFileDoesNotExist(
            $this->outside.'/escaped.yaml',
            'A move migration must not be able to place files outside the target directory.'
        );
    }
}
