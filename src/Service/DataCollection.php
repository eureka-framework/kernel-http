<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Kernel\Http\Service;

/**
 * Data Collection class.
 * @implements \Iterator<string|int|float|bool|null>
 *
 * @author Romain Cottard
 */
class DataCollection implements \Iterator
{
    /** @var int $length Length of the collection */
    protected int $length = 0;

    /** @var int Current position of the cursor in collection. */
    protected int $index = 0;

    /** @var array<int, string> $indices Index of keys */
    protected array $indices = [];

    /** @var array<string,mixed> $collection Collection of data. */
    protected array $collection = [];

    /**
     * DataCollection constructor.
     *
     * @param array<string,mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->collection = [];

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $this->add((string) $key, $value);
            }
        }
    }

    /**
     * Add data to the collection.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function add(string $key, $value): self
    {
        $this->collection[$key]       = $value;
        $this->indices[$this->length] = $key;
        $this->length++;

        return $this;
    }

    /**
     * Get length of the collection.
     *
     * @return int
     */
    public function length(): int
    {
        return $this->length;
    }

    /**
     * Get current data
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->collection[$this->indices[$this->index]];
    }

    /**
     * Reset internal cursor.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->index = 0;
    }

    /**
     * Get current key.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->indices[$this->index];
    }

    /**
     * Go to the next data
     *
     * @return void
     */
    public function next(): void
    {
        $this->index++;
    }

    /**
     * Go to the previous data.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * Check if have more data in the collection
     *
     * @return bool
     */
    public function valid(): bool
    {
        return ($this->index < $this->length);
    }

    /**
     * Convert to array
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $array = $this->collection;
        reset($array);

        return $array;
    }
}
