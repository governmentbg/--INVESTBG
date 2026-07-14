<?php

declare(strict_types=1);

namespace webadmin\api;

class Error extends Entity
{
    protected int $code;
    public function __construct(string $name, string $description, int $code)
    {
        parent::__construct($name, $description);
        $this->code = $code;
    }
    public function getCode(): int
    {
        return $this->code;
    }
    public function getDescription(): string
    {
        return (string) $this->description;
    }
    public function getName(): string
    {
        return (string) $this->name;
    }
}
