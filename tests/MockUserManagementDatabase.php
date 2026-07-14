<?php

declare(strict_types=1);

namespace tests;

use vakata\user\UserException;
use vakata\user\UserInterface;
use vakata\user\UserManagementDatabase;

class MockUserManagementDatabase extends UserManagementDatabase
{
    public function getUserByProviderID(string $provider, string $id, bool $updateUsed = false): UserInterface
    {
        if ($provider !== 'Password' || $id !== 'mockauth') {
            throw new UserException('User not found', 404);
        }
        return $this->getUser('1');
    }
}
