<?php

declare(strict_types=1);

namespace zni\middleware;

use vakata\http\Request;
use vakata\database\DBInterface;
use vakata\http\Response;
use vakata\cache\CacheInterface;
use Closure;

class UserDecorator
{
    protected DBInterface $db;
    protected string $siteCookieName;
    protected ?Closure $callback;
    protected ?CacheInterface $cache;
    protected int $cacheTimeout;
    protected bool $messaging = false;
    protected bool $cms = false;

    public function __construct(
        DBInterface $db,
        string $siteCookieName = 'SITE',
        ?callable $callback = null,
        ?CacheInterface $cache = null,
        int $cacheTimeout = 90,
        bool $messaging = false,
        bool $cms = false
    ) {
        $this->db = $db;
        $this->cache = $cache;
        $this->cacheTimeout = $cacheTimeout;
        $this->siteCookieName = $siteCookieName;
        $this->callback = $callback ? Closure::fromCallable($callback) : null;
        $this->messaging = $messaging;
        $this->cms = $cms;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {

        $user  = $req->getAttribute('user');
        if (!$user) {
            return $next($req);
        }
        $colors = [
            'red',
            'orange',
            'yellow',
            'olive',
            'green',
            'teal',
            'blue',
            'violet',
            'purple',
            'pink',
            'brown',
            'grey',
            'black'
        ];
        $user->set('color', $colors[abs((int)$user->getID()) % count($colors)]);
        $tmp = [];
        $hit = false;
        if ($this->cache) {
            $tmp = $this->cache->get('user-' . $user->getID());
            if (is_array($tmp)) {
                $hit = true;
            }
        }
        $tmp['organization'] = $tmp['organization'] ?? $this->db->rows(
            "SELECT o2.*
                FROM
                    organization o1,
                    organization o2,
                    user_organizations uo
                WHERE o1.org = uo.org AND o2.lft >= o1.lft AND o2.rgt <= o1.rgt AND uo.usr = ?
                ORDER BY o2.lft",
            [$user->getID()]
        )
            ->toArray('org');
        if ($this->messaging) {
            $tmp['notifications'] = $tmp['notifications'] ?? (int)$this->db->val(
                "SELECT COUNT(notification) FROM notification_recipients
                    WHERE recipient = ? AND opened IS NULL",
                [$user->getID()]
            );
        }
        if ($this->cms) {
            $tmp['sites'] = $tmp['sites'] ?? $this->db->rows(
                "SELECT s.site, s.name
                    FROM sites s, user_site us
                    WHERE s.disabled = 0 AND s.site = us.site AND us.usr = ?
                    ORDER BY s.name",
                [$user->getID()]
            )
                ->toArray('site', 'name');
            $site = $req->getCookieParams()[$this->siteCookieName] ?? null;
            $user->set('site', isset($tmp['sites'][$site]) ? $site : (array_keys($tmp['sites'] ?? [])[0] ?? null));

            if ($user->site) {
                $tmp['languages'] = $tmp['languages'] ?? $this->db->rows(
                    "SELECT l.lang, l.local
                    FROM languages l, user_lang ul, site_lang sl
                    WHERE l.lang = ul.lang AND ul.usr = ? AND sl.lang = ul.lang AND sl.site = ?
                    ORDER BY l.lang",
                    [$user->getID(), $user->site]
                )
                    ->toArray('lang', 'local');
            } else {
                $tmp['languages'] = $tmp['languages'] ?? $this->db->rows(
                    "SELECT l.lang, l.local
                    FROM languages l, user_lang ul
                    WHERE l.lang = ul.lang AND ul.usr = ?
                    ORDER BY l.lang",
                    [$user->getID()]
                )
                    ->toArray('lang', 'local');
            }
        }

        $tmp['egn'] = $tmp['egn'] ?? $this->db->one(
            "SELECT up.id as id
            FROM users u
            JOIN user_providers up  on u.usr  = up.usr
            WHERE u.usr = ?",
            [$user->getID()]
        );

        if ($this->cache && !$hit) {
            $this->cache->set('user-' . $user->getID(), $tmp, $this->cacheTimeout);
        }
        foreach ($tmp as $k => $v) {
            $user->set($k, $v);
        }


        if ($this->callback) {
            call_user_func($this->callback, $user);
        }
        return $next($req);
    }
}
