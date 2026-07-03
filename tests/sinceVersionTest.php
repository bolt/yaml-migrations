<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\Migrate;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class sinceVersionTest extends TestCase
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

    public function testUnquotedFloatLikeSinceIsRejected(): void
    {
        // Unquoted `1.10` is parsed by YAML as the float 1.1, which would
        // silently mis-order the migration. It must be rejected.
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-float.yaml', <<<YAML
        file: whatever.yaml
        since: 1.10

        add:
          hello: world
        YAML);

        $output = $this->createMock(OutputInterface::class);
        $migrate = new Migrate($output, $this->writeConfig());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/since/');

        $migrate->list();
    }

    public function testQuotedSinceIsAccepted(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-quoted.yaml', <<<YAML
        file: whatever.yaml
        since: "1.10"

        add:
          hello: world
        YAML);

        $output = $this->createMock(OutputInterface::class);
        $migrate = new Migrate($output, $this->writeConfig());

        $list = $migrate->list();

        $this->assertCount(1, $list, 'A quoted string version must be accepted.');
    }
}
