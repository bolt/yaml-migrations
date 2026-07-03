<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\Migrate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class checkpointAdvanceTest extends TestCase
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

    public function testMoveOnlyRunAdvancesCheckpoint(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/source/foo.yaml', "hello: world\n");
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-move.yaml', <<<YAML
        file: foo.yaml
        since: 2.0.0

        move: bar.yaml
        YAML);

        $output = $this->createMock(OutputInterface::class);
        (new Migrate($output, $this->writeConfig()))->process();

        $this->assertSame(
            '2.0.0',
            trim((string) file_get_contents($this->workspace.'/checkpoint.txt')),
            'Checkpoint must advance after a move-only migration run.'
        );
    }

    public function testDeleteOnlyRunAdvancesCheckpoint(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/source/gone.yaml', "hello: world\n");
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-delete.yaml', <<<YAML
        file: gone.yaml
        since: 3.0.0

        delete: true
        YAML);

        $output = $this->createMock(OutputInterface::class);
        (new Migrate($output, $this->writeConfig()))->process();

        $this->assertSame(
            '3.0.0',
            trim((string) file_get_contents($this->workspace.'/checkpoint.txt')),
            'Checkpoint must advance after a delete-only migration run.'
        );
    }
}
