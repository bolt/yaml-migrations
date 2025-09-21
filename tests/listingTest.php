<?php

declare(strict_types=1);

namespace YamlMigrate\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use YamlMigrate\Migrate;

class listingTest extends TestCase
{
    public function testGetListingAmount(): void
    {
        $output = $this->createMock(OutputInterface::class);

        $migrate = new Migrate($output, 'config.yaml');

        $res = $migrate->list();

        $this->assertSame(14, \count($res));
    }
}
