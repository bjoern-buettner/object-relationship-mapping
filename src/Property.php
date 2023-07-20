<?php

declare(strict_types=1);

namespace Me\BjoernBuettner\ObjectRelationshipMapping;

class Property
{
    public function __construct(
        private readonly string $name,
        private readonly string $column,
        private readonly string $type,
        private readonly bool $nullable,
    ) {
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getColumn(): string
    {
        return $this->column;
    }
    public function getType(): string
    {
        return $this->type;
    }
    public function isNullable(): bool
    {
        return $this->nullable;
    }
}