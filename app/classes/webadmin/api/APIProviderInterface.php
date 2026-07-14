<?php

declare(strict_types=1);

namespace webadmin\api;

interface APIProviderInterface
{
    public function getEndpoints(API $api): void;
}
