<?php

declare(strict_types=1);

namespace webadmin\api;

use JsonSerializable;

class API implements JsonSerializable
{
    /** @var array<string,Entity|Error> */
    protected array $components;
    /** @var array<string,Endpoint> */
    protected array $endpoints;
    protected string $contentType;
    /** @var array<string,Parameter> */
    protected array $headers;
    /** @var array<string,Parameter> */
    protected array $cookies;
    protected string $version = '3.1.1';
    protected string $title;
    protected string $apiVersion;
    /** @var array<string,string> */
    protected array $tags;
    /** @var array<int,string> */
    protected array $servers;
    public function __construct(string $title, string $apiVersion, string $contentType)
    {
        $this->title = $title;
        $this->apiVersion = $apiVersion;
        $this->tags = [];
        $this->components = [];
        $this->endpoints = [];
        $this->headers = [];
        $this->cookies = [];
        $this->contentType = $contentType;
        $this->servers = [];
    }
    public function jsonSerialize(): mixed
    {
        $used = [];
        $paths = [];
        $schemas = [];

        foreach ($this->endpoints as $endpoint) {
            if (!isset($paths[$endpoint->getPath()])) {
                $paths[$endpoint->getPath()] = [];
            }
            $contentType = ($endpoint->getContentType() ?? $this->contentType);
            $path = [
                'summary'   => $endpoint->getName()
            ];
            if (count($endpoint->getTags())) {
                $path['tags'] = $endpoint->getTags();
            }
            $params = [];

            /** @var array<string,Parameter> */
            $headers = array_merge($this->headers, $endpoint->getHeaders());
            foreach ($headers as $header) {
                $params[] = $header->getSchema('header');
            }
            /** @var array<string,Parameter> */
            $cookies = array_merge($this->cookies, $endpoint->getCookies());
            foreach ($cookies as $cookie) {
                $params[] = $cookie->getSchema('cookie');
            }
            foreach ($endpoint->getQueryParams() as $param) {
                $params[] = $param->getSchema('query');
            }
            foreach ($endpoint->getPathParams() as $param) {
                $params[] = $param->getSchema('path');
            }

            if (count($params)) {
                $path['parameters'] = $params;
            }

            if (($request = $endpoint->getRequestBody())) {
                /** @psalm-suppress PossiblyNullArgument */
                if ($request->getName() && $this->hasComponent($request->getName())) {
                    $used[] = $request->getName();
                    $schema = [ '$ref' => '#/components/schemas/' . $request->getName() ];
                } else {
                    $schema = $request->getSchema();
                }
                $path['requestBody'] = [
                    'description'   => $request->getDescription(),
                    'content'   => [
                        $contentType => [
                            'schema' => $schema
                        ]
                    ]
                ];
            }

            $response = $endpoint->getResponseBody();
            $path['responses'] = [
                200   => [
                    'description' => $response->getDescription()
                ]
            ];

            if (count($response->getFields())) {
                /** @psalm-suppress PossiblyNullArgument */
                if ($response->getName() && $this->hasComponent($response->getName())) {
                    $used[] = $response->getName();
                    $schema = [ '$ref' => '#/components/schemas/' . $response->getName() ];
                } else {
                    $schema = $response->getSchema();
                }
                $path['responses'][200]['content'][$contentType]['schema'] = $schema;
            }

            foreach ($endpoint->getErrors() as $error) {
                $path['responses'][$error->getCode()] = [
                    'description' => $error->getDescription()
                ];

                if (count($error->getFields())) {
                    if ($this->hasComponent($error->getName())) {
                        $used[] = $error->getName();
                        $schema = [ '$ref' => '#/components/schemas/' . $error->getName() ];
                    } else {
                        $schema = $error->getSchema();
                    }
                    $path['responses'][$error->getCode()]['content'][$contentType]['schema'] = $schema;
                }
            }

            foreach ($endpoint->getMethods() as $method) {
                $paths[$endpoint->getPath()][strtolower($method)] = $path;
            }
        }
        foreach ($this->components as $component) {
            if ($component->getName() && in_array($component->getName(), $used)) {
                /** @psalm-suppress PossiblyNullArrayOffset */
                $schemas[$component->getName()] = $component->getSchema();
            }
        }

        return [
            'openapi'   => $this->version,
            'info'      => [
                'title'     => $this->title,
                'version'   => $this->apiVersion
            ],
            'tags'          => array_map(
                function (string $name, string $description): array {
                    return [
                        'name'          => $name,
                        'description'   => $description
                    ];
                },
                array_keys($this->tags),
                $this->tags
            ),
            'servers'       => array_map(
                function (string $address): array {
                    return [ 'url' => $address ];
                },
                $this->getServers()
            ),
            'paths'         => $paths,
            'components'    => [
                'schemas'   => $schemas,
                'securitySchemes' => [
                    'token' => [
                        'type'          => 'http',
                        'scheme'        => 'bearer',
                        'bearerFormat'  => 'JWE'
                    ]
                ]
            ],
            'security' => [
                [
                    'token' => []
                ]
            ]
        ];
    }
    public function addEndpoint(Endpoint $value): static
    {
        if (isset($this->endpoints[$value->getName()])) {
            throw new \Exception('Endpoint already exists');
        }
        $this->endpoints[$value->getName()] = $value;

        return $this;
    }
    public function getEndpoint(string $name): ?Endpoint
    {
        return $this->endpoints[$name] ?? null;
    }
    public function removeEndpoint(string $name): static
    {
        unset($this->endpoints[$name]);

        return $this;
    }
    public function registerComponent(Entity $value): static
    {
        if ($value->getName()) {
            /** @psalm-suppress PossiblyNullArrayOffset */
            $this->components[$value->getName()] = $value;
        }

        return $this;
    }
    public function setContentType(string $value): static
    {
        $this->contentType = $value;

        return $this;
    }
    public function addHeader(Parameter $value): static
    {
        if (isset($this->headers[$value->getName()])) {
            throw new \Exception('Header already exists');
        }

        $this->headers[$value->getName()] = $value;

        return $this;
    }
    public function addCookie(Parameter $value): static
    {
        if (isset($this->cookies[$value->getName()])) {
            throw new \Exception('Cookie already exists');
        }

        $this->cookies[$value->getName()] = $value;

        return $this;
    }
    public function getComponent(string $name): Entity
    {
        if (!$this->hasComponent($name) || !($this->components[$name] instanceof Entity)) {
            throw new \Exception('Invalid component');
        }

        return $this->components[$name];
    }
    public function getError(string $name): Error
    {
        if (!$this->hasComponent($name) || !($this->components[$name] instanceof Error)) {
            throw new \Exception('Invalid component');
        }

        return $this->components[$name];
    }
    public function hasComponent(string $name): bool
    {
        return isset($this->components[$name]);
    }
    /** @return array<string,Endpoint> */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }
    public function addTag(string $name, string $description): static
    {
        if ($this->hasTag($name)) {
            throw new \Exception('Tag already exists');
        }

        $this->tags[$name] = $description;

        return $this;
    }
    public function hasTag(string $name): bool
    {
        return isset($this->tags[$name]);
    }
    public function addServer(string $url): static
    {
        $this->servers[] = $url;

        return $this;
    }
    public function getServers(): array
    {
        return array_values(array_unique($this->servers));
    }
    /**
     * @param string $type
     * @return array{type:string,format:?string}
     */
    public static function getTypeAndFormat(string $type): array
    {
        $types = [
            'int' => [
                'type'      => 'number',
                'format'    => 'int64'
            ],
            'float' => [
                'type'      => 'number',
                'format'    => 'float'
            ],
            'bool' => [
                'type'      => 'boolean',
                'format'    => null
            ],
            'string' => [
                'type'      => 'string',
                'format'    => null
            ],
            'array' => [
                'type'      => 'array',
                'format'    => null
            ]
        ];

        return $types[$type] ?? $types['string'];
    }
}
