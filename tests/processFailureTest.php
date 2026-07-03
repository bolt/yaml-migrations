<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\Migrate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class processFailureTest extends TestCase
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

    public function testRunStopsAtFirstFailureWithoutAdvancingCheckpoint(): void
    {
        // First (by filename) migration fails: it moves a source file that
        // does not exist into a different target directory.
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-fail.yaml', <<<YAML
        file: missing.yaml
        since: 2.0.0

        move: moved.yaml
        YAML);

        // A later, perfectly valid migration that would create target/real.yaml.
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-02-ok.yaml', <<<YAML
        file: real.yaml
        since: 3.0.0

        add:
          hello: world
        YAML);

        $output = $this->createMock(OutputInterface::class);
        $migrate = new Migrate($output, $this->writeConfig());

        // The run must handle the failure itself, not blow up the caller.
        $migrate->process();

        $this->assertSame(
            '1.0.0',
            trim((string) file_get_contents($this->workspace.'/checkpoint.txt')),
            'Checkpoint must not advance when a migration failed.'
        );

        $this->assertFileDoesNotExist(
            $this->workspace.'/target/real.yaml',
            'Processing must stop at the first failure and not run later migrations.'
        );
    }
}
