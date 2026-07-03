<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\Migrate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class endToEndMigrationTest extends TestCase
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

    private function process(?string $onlyFilename = null): void
    {
        $output = $this->createMock(OutputInterface::class);
        (new Migrate($output, $this->writeConfig()))->process($onlyFilename);
    }

    private function checkpoint(): string
    {
        return trim((string) file_get_contents($this->workspace.'/checkpoint.txt'));
    }

    public function testRemoveMigrationRewritesTargetWithoutTheRemovedKeys(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/source/services.yaml', <<<YAML
        security:
          role_hierarchy:
            ADMIN: USER
          keep: me
        other: value
        YAML);
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-remove.yaml', <<<YAML
        file: services.yaml
        since: "3.0.0"

        remove:
          security:
            role_hierarchy: ~
        YAML);

        $this->process();

        $this->assertSame([
            'security' => [
                'keep' => 'me',
            ],
            'other' => 'value',
        ], Yaml::parseFile($this->workspace.'/target/services.yaml'));
    }

    public function testAddMigrationOverridesAnExistingKey(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/source/replace.yaml', <<<YAML
        foo: oldFooValue
        bar: keepMe
        YAML);
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-replace.yaml', <<<YAML
        file: replace.yaml
        since: "3.0.0"

        add:
          foo: newFooValue
        YAML);

        $this->process();

        $this->assertSame([
            'foo' => 'newFooValue',
            'bar' => 'keepMe',
        ], Yaml::parseFile($this->workspace.'/target/replace.yaml'));
    }

    public function testSingleFileRunProcessesOnlyThatFileAndLeavesCheckpointUntouched(): void
    {
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-01-other.yaml', <<<YAML
        file: other.yaml
        since: "2.0.0"

        add:
          a: 1
        YAML);
        $this->filesystem->dumpFile($this->workspace.'/migrations/m_2025-01-02-only.yaml', <<<YAML
        file: only.yaml
        since: "3.0.0"

        add:
          b: 2
        YAML);

        $this->process('m_2025-01-02-only.yaml');

        $this->assertFileExists($this->workspace.'/target/only.yaml');
        $this->assertFileDoesNotExist(
            $this->workspace.'/target/other.yaml',
            'A single-file run must not process other migrations.'
        );
        $this->assertSame(
            '1.0.0',
            $this->checkpoint(),
            'A single-file run must never advance the checkpoint.'
        );
    }
}
