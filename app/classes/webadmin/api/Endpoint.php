<?php

declare(strict_types=1);

namespace webadmin\api;

use Closure;

class Endpoint
{
    protected string $name;
    protected string $path;
    /** @var array<string,bool> */
    protected array $methods;
    protected Closure $func;
    protected ?Entity $request;
    protected Entity $response;
    /** @var array<string,Parameter> */
    protected array $headers;
    /** @var array<string,Parameter> */
    protected array $cookies;
    /** @var array<string,Parameter> */
    protected array $pathParams;
    /** @var array<string,Parameter> */
    protected array $queryParams;
    protected ?string $contentType;
    /** @var array<string,Error> */
    protected array $errors;
    protected array $tags;
    public function __construct(string $name, string $path)
    {
        $this->name = $name;
        $this->path = $path;
        $this->methods = [
            'GET'       => false,
            'POST'      => false,
            'PUT'       => false,
            'DELETE'    => false,
            'OPTIONS'   => false,
            'HEAD'      => false,
            'PATCH'     => false,
            'TRACE'     => false
        ];
        $this->request = null;
        $this->headers = [];
        $this->cookies = [];
        $this->pathParams = [];
        $this->queryParams = [];
        $this->contentType = null;
        $this->errors = [];
        $this->tags = [];
        $this->func = Closure::fromCallable(function () {
        });
        $this->response = new Entity();
    }
    public function enableMethod(string $method): static
    {
        $method = strtoupper($method);
        if (isset($this->methods[$method])) {
            $this->methods[$method] = true;
        }

        return $this;
    }
    public function enableMethods(array $methods): static
    {
        foreach ($methods as $method) {
            $this->enableMethod($method);
        }

        return $this;
    }
    public function enablePost(): static
    {
        $this->enableMethod('POST');

        return $this;
    }
    public function enableGet(): static
    {
        $this->enableMethod('GET');

        return $this;
    }
    public function enablePut(): static
    {
        $this->enableMethod('PUT');

        return $this;
    }
    public function enableDelete(): static
    {
        $this->enableMethod('DELETE');

        return $this;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getPath(): string
    {
        return $this->path;
    }
    public function getMethods(): array
    {
        return array_keys(
            array_filter(
                $this->methods
            )
        );
    }
    public function setCallback(Closure $func): static
    {
        $this->func = $func;

        return $this;
    }
    public function getCallback(): Closure
    {
        return $this->func;
    }
    public function setRequest(Entity $value): static
    {
        $this->request = $value;

        return $this;
    }
    public function setResponse(Entity $value): static
    {
        $this->response = $value;

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
    public function addPathParam(Parameter $value): static
    {
        if (isset($this->pathParams[$value->getName()])) {
            throw new \Exception('Path parameter already exists');
        }

        $this->pathParams[$value->getName()] = $value;

        return $this;
    }
    public function addQueryParam(Parameter $value): static
    {
        if (isset($this->queryParams[$value->getName()])) {
            throw new \Exception('Query parameter already exists');
        }

        $this->queryParams[$value->getName()] = $value;

        return $this;
    }
    public function setContentType(string $value): static
    {
        $this->contentType = $value;

        return $this;
    }
    public function getContentType(): ?string
    {
        return $this->contentType;
    }
    public function addError(Error $value): static
    {
        if (isset($this->errors[$value->getName()])) {
            throw new \Exception('Error already exists');
        }

        $this->errors[$value->getName()] = $value;

        return $this;
    }
    public function addTag(string $name): static
    {
        $this->tags[] = $name;

        return $this;
    }
    public function getTags(): array
    {
        return array_unique($this->tags);
    }
    public function getRequestBody(): ?Entity
    {
        return $this->request;
    }
    public function getResponseBody(): Entity
    {
        return $this->response;
    }
    /** @return array<string,Error> */
    public function getErrors(): array
    {
        return $this->errors;
    }
    /** @return array<string,Parameter>  */
    public function getHeaders(): array
    {
        return $this->headers;
    }
    /** @return array<string,Parameter>  */
    public function getCookies(): array
    {
        return $this->cookies;
    }
    /** @return array<string,Parameter>  */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }
    /** @return array<string,Parameter>  */
    public function getPathParams(): array
    {
        return $this->pathParams;
    }
}
