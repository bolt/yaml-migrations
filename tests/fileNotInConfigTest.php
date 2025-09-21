<?php

declare(strict_types=1);

namespace YamlMigrate\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use YamlMigrate\Migrate;

class fileNotInConfigTest extends TestCase
{
    public function testFileNotInConfig(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $migrate = new Migrate($output, 'config.yaml');

        $this->expectException(\Throwable::class);

        $migrate->process('foo.yaml');
    }
}
