<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use Exception;

class CRUDException extends Exception
{
    protected array $errors = [];

    public function setErrors(array $errors): CRUDException
    {
        $this->errors = $errors;
        return $this;
    }
    public function getErrors(): array
    {
        return $this->errors;
    }
}
