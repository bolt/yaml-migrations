<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\ArrayMerge;
use PHPUnit\Framework\TestCase;

class removeArrayRecursivelyTest extends TestCase
{
    public function testRemovePathDeeperThanScalarDataIsLeftUntouched(): void
    {
        // The data has a scalar where the migration expects a nested map.
        $data = [
            'security' => 'somestring',
        ];
        $removables = [
            'security' => [
                'role_hierarchy' => null,
            ],
        ];

        ArrayMerge::removeArrayRecursively($data, $removables);

        // Nothing to remove: the scalar has no `role_hierarchy` child, so the
        // data must be left untouched rather than crashing with a TypeError.
        $this->assertSame([
            'security' => 'somestring',
        ], $data);
    }

    public function testRemoveNestedKeyStillWorks(): void
    {
        $data = [
            'security' => [
                'role_hierarchy' => [
                    'ADMIN' => 'USER',
                ],
                'keep' => 'me',
            ],
        ];
        $removables = [
            'security' => [
                'role_hierarchy' => null,
            ],
        ];

        ArrayMerge::removeArrayRecursively($data, $removables);

        $this->assertSame([
            'security' => [
                'keep' => 'me',
            ],
        ], $data);
    }

    public function testRemoveDeeplyNestedKeyKeepingSiblings(): void
    {
        $data = [
            'security' => [
                'role_hierarchy' => 'x',
                'access_control' => 'y',
                'foo' => [
                    'bar' => [
                        'qux' => 'z',
                        'keep' => 'me',
                    ],
                    'sibling' => 'stays',
                ],
            ],
            'top' => 'level',
        ];
        $removables = [
            'security' => [
                'role_hierarchy' => null,
                'access_control' => null,
                'foo' => [
                    'bar' => [
                        'qux' => null,
                    ],
                ],
            ],
        ];

        ArrayMerge::removeArrayRecursively($data, $removables);

        $this->assertSame([
            'security' => [
                'foo' => [
                    'bar' => [
                        'keep' => 'me',
                    ],
                    'sibling' => 'stays',
                ],
            ],
            'top' => 'level',
        ], $data);
    }

    public function testKeysAbsentFromRemovablesAreUntouched(): void
    {
        $data = [
            'list' => ['a', 'b', 'c'],
            'map' => [
                'one' => 1,
                'two' => 2,
            ],
        ];

        ArrayMerge::removeArrayRecursively($data, []);

        $this->assertSame([
            'list' => ['a', 'b', 'c'],
            'map' => [
                'one' => 1,
                'two' => 2,
            ],
        ], $data);
    }
}
