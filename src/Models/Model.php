<?php

declare(strict_types=1);

namespace Farzai\Geonames\Models;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * A read-only row of the snapshot. Nulls mean unknown, and fields this package does
 * not know are kept in `toArray()` untouched, so a newer snapshot minor still reads.
 *
 * @implements Arrayable<string, mixed>
 */
abstract class Model implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $row  the row as it is in the snapshot (snake_case)
     */
    final public function __construct(protected readonly array $row) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row): static
    {
        return new static($row);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->row;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->row;
    }

    /**
     * `$country->callingCode`, `$city->nameTh`: a camelCase property reads the accessor of
     * the same name, or else the snapshot field in snake_case (`calling_code`).
     */
    public function __get(string $name): mixed
    {
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }

        return $this->row[strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name))] ?? null;
    }

    public function __isset(string $name): bool
    {
        return method_exists($this, $name) || $this->__get($name) !== null;
    }

    public function isAbolished(): bool
    {
        return ($this->row['status'] ?? 'active') === 'abolished';
    }

    protected function string(string $key): ?string
    {
        $value = $this->row[$key] ?? null;

        return is_string($value) || is_int($value) ? (string) $value : null;
    }

    protected function int(string $key): ?int
    {
        $value = $this->row[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    protected function float(string $key): ?float
    {
        $value = $this->row[$key] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    protected function bool(string $key): bool
    {
        return (bool) ($this->row[$key] ?? false);
    }
}
