<?php

declare(strict_types=1);

namespace Bolt\YamlMigrations\Tests;

use Bolt\YamlMigrations\ArrayMerge;
use PHPUnit\Framework\TestCase;

class arrayMergeTest extends TestCase
{
    public function testAddsNewTopLevelKeys(): void
    {
        $result = ArrayMerge::merge([
            'a' => 1,
        ], [
            'b' => 2,
        ]);

        $this->assertSame([
            'a' => 1,
            'b' => 2,
        ], $result);
    }

    public function testMergingIntoEmptyDataReturnsTheAddition(): void
    {
        $result = ArrayMerge::merge([], [
            'a' => 1,
            'b' => 2,
        ]);

        $this->assertSame([
            'a' => 1,
            'b' => 2,
        ], $result);
    }

    public function testOverridesAnExistingScalarValue(): void
    {
        // This is the "replace" behaviour: a later value wins over the former.
        $result = ArrayMerge::merge([
            'foo' => 'old',
        ], [
            'foo' => 'new',
        ]);

        $this->assertSame([
            'foo' => 'new',
        ], $result);
    }

    public function testDeeplyMergesNestedMapsKeepingBothSides(): void
    {
        $result = ArrayMerge::merge(
            [
                'a' => [
                    'x' => 1,
                    'shared' => 'keep',
                ],
            ],
            [
                'a' => [
                    'y' => 2,
                    'shared' => 'override',
                ],
            ],
        );

        $this->assertSame([
            'a' => [
                'x' => 1,
                'shared' => 'override',
                'y' => 2,
            ],
        ], $result);
    }

    public function testAppendsToListsWhileDroppingDuplicateValues(): void
    {
        // Documented behaviour: list items already present are not re-added,
        // so re-running a migration that appends stays idempotent.
        $result = ArrayMerge::merge([
            'list' => ['a', 'b'],
        ], [
            'list' => ['b', 'c'],
        ]);

        $this->assertSame([
            'list' => ['a', 'b', 'c'],
        ], $result);
    }

    public function testAddingIdenticalDataIsANoOp(): void
    {
        // doMigrationAdd() relies on strict equality to detect "nothing to do".
        $data = [
            'a' => 1,
            'nested' => [
                'b' => 2,
            ],
        ];

        $this->assertSame($data, ArrayMerge::merge($data, $data));
    }
}
