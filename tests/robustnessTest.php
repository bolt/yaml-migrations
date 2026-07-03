<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\Migrate;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class robustnessTest extends TestCase
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

    public function testMissingConfigThrowsInsteadOfKillingTheProcess(): void
    {
        $output = $this->createMock(OutputInterface::class);

        $this->expectException(RuntimeException::class);

        new Migrate($output, $this->workspace.'/does-not-exist.yaml');
    }

    public function testMigrationWithoutSinceGivesAClearError(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-nosince.yaml', <<<YAML
        file: whatever.yaml

        add:
          hello: world
        YAML);

        $output = $this->createMock(OutputInterface::class);
        $migrate = new Migrate($output, $this->writeConfig());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/since/');

        $migrate->list();
    }
}
