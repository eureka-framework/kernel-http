<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Tests\Service;

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

        $this->assertInstanceOf(DataCollection::class, $collection);
    }

    /**
     * @return void
     */
    public function testICanInstantiateNonEmptyCollection(): void
    {
        $data = ['one' => 1, 'two' => 2];
        $collection = new DataCollection($data);

        $this->assertEquals(2, $collection->length());

        foreach ($collection as $key => $value) {
            $this->assertEquals($data[$key], $value);
        }

        $collection->reset();

        $this->assertEquals($data, $collection->toArray());
    }
}
