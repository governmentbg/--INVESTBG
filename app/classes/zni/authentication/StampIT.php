<?php

namespace zni\authentication;

use vakata\authentication\oauth\OAuth;

class StampIT extends OAuth
{
    /**
     *  @psalm-suppress all
     *  @phpstan-ignore-next-line
     */
    protected $permissions  = 'pid,name,mail,organization';
    /**
     *  @psalm-suppress all
     *  @phpstan-ignore-next-line
     */
    protected $authorizeUrl = 'https://id.stampit.org/authorize?';
    /**
     *  @psalm-suppress all
     *  @phpstan-ignore-next-line
     */
    protected $tokenUrl     = 'https://id.stampit.org/access_token';
    /**
     *  @psalm-suppress all
     *  @phpstan-ignore-next-line
     */
    protected $infoUrl      = 'https://id.stampit.org/me?';
    /**
     *  @psalm-suppress all
     *  @phpstan-ignore-next-line
     */
    protected $grantType    = '';

    protected function extractUserData(array $data): array
    {
        return array_merge($data, [
            'name'          => $data['name'] ?? null,
            'mail'          => $data['mail'] ?? null,
            'egn'           => $data['egn'] ?? null,
            'bulstat'       => $data['bulstat'] ?? null,
            'organization'  => $data['organization'] ?? null
        ]);
    }
    protected function extractUserID(array $data): string
    {
        return $data['egn'] ?? ($data['mail'] ?? $data['certno'] ?? "");
    }
}
