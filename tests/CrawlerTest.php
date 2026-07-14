<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;

final class CrawlerTest extends TestCase
{
    use HTTPTrait;

    protected function shouldSearch(string $url): bool
    {
        $file = array_reverse(explode('/', explode('?', $url)[0]))[0] ?? '';
        $ext  = strpos($file, '.') === false ? 'html' : array_reverse(explode('.', $file))[0] ?? '';
        return in_array($ext, ['htm','html']);
    }
    protected function normalizeUrl(string $url, string $currentUrl = null): string
    {
        $data = parse_url('http://localhost/');
        if ($url === 'p+') {
            return $url;
        }
        if ($url[0] === '?' && $currentUrl !== null) {
            return explode('?', $currentUrl)[0] . $url;
        }
        if (substr($url, 0, 2) === '//') {
            return $url;
        }
        if ($url[0] === '/') {
            return ($data['scheme'] ?? 'http') . '://' . ($data['host'] ?? 'localhost') . $url;
        }
        if (strpos($url, '//') === false && $currentUrl) {
            $currentUrl = explode('/', substr($currentUrl, strlen('http://localhost/')));
            unset($currentUrl[count($currentUrl) - 1]);
            $segments = explode('/', ltrim(preg_replace('(^\\./)', '', $url), '/'));
            foreach ($segments as $k => $segment) {
                if ($segment === '..') {
                    if (!count($currentUrl)) {
                        return $url;
                    }
                    unset($currentUrl[count($currentUrl) - 1]);
                    unset($segments[$k]);
                }
            }
            return 'http://localhost/' . implode('/', array_filter(array_merge($currentUrl, $segments)));
        }
        return $url;
    }

    public function testAll(): void
    {
        $this
            ->clear()
            ->get('/')
            ->follow()
            ->post(['username' => 'mockauth', 'password' => 'mockauth'])
            ->follow()
                ->assertStatus(200)
                ->assertLocation('/')
            ;
        $visited = [];
        $visited['/login'] = true;
        $visited['/pages/preview/'] = true;
        /**
         * @var string[]
         */
        /** @var string[] $waiting */
        $waiting = ['/'];
        while (($url = array_shift($waiting)) !== null) {
            $this
                ->get($url, true)
                ->assertStatus(200, $url);
            $visited[$url] = true;
            if ($this->shouldSearch($url)) {
                $matches = [];
                if (
                    preg_match_all(
                        '((href=)(\'|")?([^ \'"]+)(\'|"))i',
                        (string)$this->response->getBody(),
                        $matches
                    )
                ) {
                    foreach ($matches[3] as $match) {
                        if (
                            $match === '#' ||
                            strpos($match, 'tel:') === 0 ||
                            strpos($match, 'data:') === 0 ||
                            strpos($match, 'mailto:') === 0 ||
                            strpos($match, 'javascript:') === 0
                        ) {
                            continue;
                        }
                        $matchUrl = $this->normalizeUrl(htmlspecialchars_decode($match), 'http://localhost' . $url);
                        if (strpos($matchUrl, 'http://localhost/') === 0 && $this->shouldSearch($matchUrl)) {
                            $shortUrl = str_replace('http://localhost/', '/', $matchUrl);
                            if (
                                !isset($visited[$shortUrl]) &&
                                !in_array($shortUrl, $waiting) &&
                                strpos($shortUrl, '/relation/') === false &&
                                strpos($shortUrl, '/log/') === false &&
                                strpos($shortUrl, '/log?') === false
                            ) {
                                $waiting[] = $shortUrl;
                            }
                        }
                    }
                }
            }
        }
    }
}
