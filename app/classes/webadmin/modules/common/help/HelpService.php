<?php

declare(strict_types=1);

namespace webadmin\modules\common\help;

use vakata\database\DBInterface;

class HelpService
{
    protected DBInterface $db;

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }

    protected function normalizeUrl(string $url): string
    {
        return implode('/', array_slice(array_filter(explode('/', trim($url, '/'))), 0, 2));
    }

    public function set(string $url, string $content = ''): void
    {
        $url = $this->normalizeUrl($url);
        $this->db->table('help')->filter('url', $url)->delete();
        if (strlen(strip_tags($content))) {
            $this->db->table('help')->insert([
                'url' => $url,
                'content' => $content
            ]);
        }
    }
    public function get(string $url): ?string
    {
        $url = $this->normalizeUrl($url);
        return (string)$this->db->val("SELECT content FROM help WHERE url = ?", [$url]) ?: null;
    }
}
