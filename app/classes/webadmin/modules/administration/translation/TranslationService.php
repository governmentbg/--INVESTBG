<?php

declare(strict_types=1);

namespace webadmin\modules\administration\translation;

use vakata\database\DBInterface;
use webadmin\Jobs;

class TranslationService
{
    protected Jobs $jobs;
    protected DBInterface $db;

    /**
     * @param Jobs $jobs
     * @param DBInterface $db
     * @return void
     */
    public function __construct(Jobs $jobs, DBInterface $db)
    {
        $this->jobs = $jobs;
        $this->db   = $db;
    }

    public function getMissingTranslations(string $locale): array
    {
        return $this->db->all(
            "SELECT k, v FROM translations WHERE locale = ? AND (v IS NULL OR v = '') ORDER BY k",
            [ $locale ],
            'k',
            true
        );
    }
    public function getTranslations(string $locale, string $path): array
    {
        $overrides = $this->db->all(
            "SELECT k, v FROM translations WHERE locale = ? ORDER BY k",
            [ $locale ],
            'k',
            true
        );
        $data = [];
        if (is_file($path)) {
            $data = @json_decode(file_get_contents($path) ?: '{}', true);
        }
        $data = array_merge($data, array_filter($overrides));
        ksort($data);
        return $data;
    }

    public function setTranslations(string $locale, string $path, array $data, bool $remove = false): void
    {
        $overrides = $this->db->all(
            "SELECT k, v FROM translations WHERE locale = ? ORDER BY k",
            [ $locale ],
            'k',
            true
        );
        $file = [];
        if (is_file($path)) {
            $file = @json_decode(file_get_contents($path) ?: '{}', true);
        }
        if ($remove) {
            foreach ($overrides as $k => $v) {
                if (!isset($data[$k])) {
                    $this->db->query("DELETE FROM translations WHERE locale = ? AND k = ?", [ $locale, $k ]);
                }
            }
        }
        foreach ($data as $k => $v) {
            if (!strlen($v)) {
                continue;
            }
            if (array_key_exists($k, $overrides) && isset($file[$k]) && $file[$k] === $v) {
                $this->db->query("DELETE FROM translations WHERE locale = ? AND k = ?", [ $locale, $k ]);
            } elseif (array_key_exists($k, $overrides) && $overrides[$k] === $v) {
                continue;
            } elseif (array_key_exists($k, $overrides)) {
                $this->db->query("UPDATE translations SET v = ? WHERE locale = ? AND k = ?", [ $v, $locale, $k ]);
            } else {
                $this->db->query("INSERT INTO translations (locale, k, v) VALUES (?, ?, ?)", [ $locale, $k, $v ]);
            }
        }
        $this->jobs->cacheLangs();
    }

    public function store(string $locale, string $path): void
    {
        $data = $this->getTranslations($locale, $path);
        if (!is_writable($path)) {
            throw new \Exception('File not writable');
        }
        $data = array_filter(
            $data,
            function ($v) {
                return isset($v);
            }
        );
        if (
            !@file_put_contents(
                $path,
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: ''
            )
        ) {
            throw new \Exception('File not writable');
        }
        $this->db->query("DELETE FROM translations WHERE locale = ?", $locale);
    }
}
