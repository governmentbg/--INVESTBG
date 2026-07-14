<?php

declare(strict_types=1);

namespace tests;

use vakata\authentication\Credentials;
use vakata\authentication\Manager;
use vakata\authentication\AuthenticationException;

class MockManager extends Manager
{
    public function supports(array $data = []): bool
    {
        return isset($data['username']) && isset($data['password']);
    }
    public function authenticate(array $data = []): Credentials
    {
        if (
            isset($data['username']) &&
            isset($data['password']) &&
            $data['username'] === 'admin' &&
            $data['password'] === 'admin'
        ) {
            return new Credentials('mockauth', 'mockauth');
        }
        throw new AuthenticationException('No supported authentication methods');
    }
}
