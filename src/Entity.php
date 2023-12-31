<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\ObjectRelationshipMapping;

use ArrayAccess;
use InvalidArgumentException;
use Iterator;
use JsonSerializable;
use ReflectionClass;
use Stringable;

abstract class Entity implements JsonSerializable, Stringable, ArrayAccess, Iterator
{
    private int $position = 0;
    private array $properties = [];
    public function __construct(private ?int $aid)
    {
        foreach (get_object_vars($this) as $property => $value) {
            if (!in_array($property, ['position', 'properties'], true)) {
                $this->properties[] = $property;
            }
        }
    }
    final public function getAid(): ?int
    {
        return $this->aid;
    }
    final public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }
        return in_array($this, $this->properties, true);
    }
    final public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("Property {$offset} does not exist.");
        }
        return $this->{$offset};
    }
    final public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new ObjectImmutableException('Entity is immutable.');
    }
    final public function offsetUnset(mixed $offset): void
    {
        throw new ObjectImmutableException('Entity is immutable.');
    }
    final public function __toString(): string
    {
        $identifier = sha1(serialize($this->jsonSerialize()));
        return static::class . "({$identifier})";
    }
    final public function jsonSerialize(): array
    {
        $data = get_object_vars($this);
        unset($data['position']);
        unset($data['properties']);
        return $data;
    }
    final public function current(): string
    {
        return $this->{$this->properties[$this->position]};
    }
    final public function next(): void
    {
        $this->position++;
    }
    final public function key(): mixed
    {
        return $this->properties[$this->position];
    }
    final public function valid(): bool
    {
        return $this->position < count($this->properties) && $this->position >= 0;
    }
    final public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @param array<string, mixed> $modified
     */
    public function with(array $modified): static
    {
        foreach (array_keys($modified) as $property) {
            if (!in_array($property, $this->properties, true)) {
                throw new UnknownPropertyException("Property {$property} does not exist.");
            }
        }
        $rc = new ReflectionClass($this);
        $arguments = [];
        foreach ($rc->getConstructor()->getParameters() as $parameter) {
            $arguments[] = $modified[$parameter->getName()] ?? $this->{$parameter->getName()};
        }
        return new static(...$arguments);
    }
}
