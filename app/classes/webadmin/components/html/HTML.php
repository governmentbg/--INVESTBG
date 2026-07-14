<?php

declare(strict_types=1);

namespace webadmin\components\html;

class HTML
{
    protected string $data = '';

    public static function from(string $data = ''): self
    {
        return new self($data);
    }

    public function __construct(string $data = '')
    {
        $this->data = $data;
    }
    public function __toString(): string
    {
        return $this->data;
    }
}
