<?php

declare(strict_types=1);

namespace webadmin\modules\site\translation;

use webadmin\Jobs;
use vakata\config\Config;
use vakata\database\DBInterface;

class TranslationService
{
    protected string $path;
    /** @var array<string,string> $langs */
    protected array $langs;
    protected Jobs $jobs;
    protected DBInterface $db;

    public function __construct(DBInterface $db, Config $config, Jobs $jobs)
    {
        $this->jobs = $jobs;
        $this->path = $config->get('STORAGE_INTL_PUBLIC');
        $this->langs = $db->rows("SELECT code, name FROM languages ORDER BY lang")->toArray('code', 'name');
        $this->db = $db;
    }

    /**
     * @return array<string,string>
     */
    public function getLanguages(): array
    {
        return $this->langs;
    }

    /**
     * @param string $lang
     * @return array<string,string>
     */
    public function getTranslations(string $lang): array
    {
        if (!isset($this->langs[$lang])) {
            throw new \RuntimeException('Invalid lang');
        }
        $file = $this->path . DIRECTORY_SEPARATOR . basename($lang) . '.json';
        if (!is_file($file) || !is_readable($file)) {
            return [];
        }
        try {
            $overrides = $this->db->all(
                "SELECT k, v FROM translations_public WHERE locale = ? ORDER BY k",
                [ $lang ],
                'k',
                true
            );
            $data = file_get_contents($file) ?: throw new \RuntimeException();
            $temp = json_decode($data, true);
            if (!is_array($temp)) {
                $temp = [];
            }
            $temp = array_merge($temp, $overrides);
            ksort($temp);
            return $temp;
        } catch (\Exception $e) {
            return [];
        }
    }
    /**
     * @param string $lang
     * @param array<string,string> $data
     * @return void
     */
    public function setTranslations(string $lang, array $data): void
    {
        if (!isset($this->langs[$lang])) {
            throw new \RuntimeException('Invalid lang');
        }
        $path = $this->path . DIRECTORY_SEPARATOR . basename($lang) . '.json';
        $overrides = $this->db->all(
            "SELECT k, v FROM translations_public WHERE locale = ? ORDER BY k",
            [ $lang ],
            'k',
            true
        );
        $file = [];
        if (is_file($path)) {
            $file = @json_decode(file_get_contents($path) ?: '{}', true);
        }
        foreach ($overrides as $k => $v) {
            if (!isset($data[$k])) {
                $this->db->query("DELETE FROM translations_public WHERE locale = ? AND k = ?", [ $lang, $k ]);
            }
        }
        foreach ($data as $k => $v) {
            if (!strlen($v)) {
                continue;
            }
            if (array_key_exists($k, $overrides) && isset($file[$k]) && $file[$k] === $v) {
                $this->db->query("DELETE FROM translations_public WHERE locale = ? AND k = ?", [ $lang, $k ]);
            } elseif (array_key_exists($k, $overrides) && $overrides[$k] === $v) {
                continue;
            } elseif (array_key_exists($k, $overrides)) {
                $this->db->query("UPDATE translations_public SET v = ? WHERE locale = ? AND k = ?", [ $v, $lang, $k ]);
            } else {
                $this->db->query("INSERT INTO translations_public (locale, k, v) VALUES (?, ?, ?)", [ $lang, $k, $v ]);
            }
        }
        $this->jobs->cacheLangs();
    }
    public function store(string $lang): void
    {
        $data = $this->getTranslations($lang);
        $path = $this->path . DIRECTORY_SEPARATOR . basename($lang) . '.json';
        if (!is_writable($path)) {
            throw new \Exception('File not writable');
        }
        if (
            !@file_put_contents(
                $path,
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: ''
            )
        ) {
            throw new \Exception('File not writable');
        }
        $this->db->query("DELETE FROM translations_public WHERE locale = ?", $lang);
    }
}
