<?php

declare(strict_types=1);

namespace webadmin\api;

use Throwable;
use webadmin\modules\common\crud\CRUDException;

class APIException extends CRUDException
{
    public function __construct(string $message, int $code, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
