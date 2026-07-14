<?php

declare(strict_types=1);

namespace base\components\files;

use RuntimeException;
use vakata\files\File;
use vakata\files\FileStorageInterface;
use vakata\http\Uri;

class Files
{
    public function __construct(
        protected FileStorageInterface $files,
        protected Uri $url,
        protected string $slug = 'upload',
        protected string $salt = ''
    ) {
    }
    public function storage(): FileStorageInterface
    {
        return $this->files;
    }
    public function get(string $id, ?string $version = null): File
    {
        return !isset($version) ? $this->files->get($id) : $this->files->getVersion($id, $version);
    }
    public function version(string $id, string $version, string $contents): File
    {
        return $this->files->setVersion($id, $version, $contents);
    }
    public function getFileHash(File $file): string
    {
        return sha1($file->id() . '/' . $file->name() . '/' . $this->salt);
    }
    public function getQueryHash(File $file, array $query = []): string
    {
        if (!isset($query['w']) && !isset($query['h']) && !isset($query['l'])) {
            return '';
        }
        return sha1(
            $file->id() . '.' .
            $file->name() . '.' .
            ($query['w'] ?? '') . '.' .
            ($query['h'] ?? '') . '.' .
            ($query['l'] ?? '') . '.' .
            $this->salt
        );
    }
    public function toLink(string|File $file, array $query = [], bool $absolute = false): string
    {
        if (is_string($file)) {
            $file = $this->get($file);
        }
        $path = $this->slug . '/' . $file->id() . '/' . $this->getFileHash($file) . '/' . $file->name();
        $hash = $this->getQueryHash($file, $query);
        if ($hash) {
            $query['_k'] = $hash;
        }
        return $this->url->get($path, $query, $absolute);
    }
    public function fromLink(string $link, array $query = []): File
    {
        $link = explode('/', trim($link, '/'));
        if (count($link) < 4) {
            throw new RuntimeException('Invalid format', 400);
        }
        if ($link[0] !== $this->slug) {
            throw new RuntimeException('Invalid slug', 400);
        }
        $file = $this->get($link[1] ?? '');
        if ($link[2] !== $this->getFileHash($file)) {
            throw new RuntimeException('Invalid hash', 404);
        }
        if ($link[3] !== $file->name()) {
            throw new RuntimeException('Invalid name', 404);
        }
        $hash = $this->getQueryHash($file, $query);
        if ($hash !== ($query['_k'] ?? '')) {
            throw new RuntimeException('Invalid query key', 404);
        }
        return $file;
    }
}
