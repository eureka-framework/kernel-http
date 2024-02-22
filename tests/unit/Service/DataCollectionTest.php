<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Unit\Service;

use Eureka\Kernel\Http\Service\DataCollection;
use PHPUnit\Framework\TestCase;

/**
 * Class DataCollectionTest
 *
 * @author Romain Cottard
 */
class DataCollectionTest extends TestCase
{
    /**
     * @return void
     */
    public function testICanInstantiateCollection(): void
    {
        $collection = new DataCollection();

        self::assertInstanceOf(DataCollection::class, $collection);
    }

    /**
     * @return void
     */
    public function testICanInstantiateNonEmptyCollection(): void
    {
        $data = ['one' => 1, 'two' => 2];
        $collection = new DataCollection($data);

        self::assertEquals(2, $collection->length());

        foreach ($collection as $key => $value) {
            self::assertEquals($data[$key], $value);
        }

        $collection->reset();

        self::assertEquals($data, $collection->toArray());
    }
}
