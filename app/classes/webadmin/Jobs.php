<?php

declare(strict_types=1);

namespace webadmin;

use base\Jobs as BaseJobs;
use RuntimeException;
use vakata\collection\Collection;
use vakata\phptree\Tree;
use vakata\spreadsheet\Reader;
use webadmin\modules\common\ekatte\CityType;
use webadmin\modules\common\ekatte\Region;

class Jobs extends BaseJobs
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->app = $app;
        $this->cnf = $app->config();
    }

    public function permissions(): void
    {
        parent::permissions();
        $needed = [];
        $needed[] = $this->cnf->getString('STORAGE_KEYS');
        $needed[] = $this->cnf->getString('STORAGE_INTL_PUBLIC');
        $needed[] = $this->cnf->getString('STORAGE_LOG_PUBLIC');
        if ($this->cnf->getString('CACHE_PUBLIC') === 'FILE') {
            $needed[] = $this->cnf->getString('STORAGE_CACHE_PUBLIC');
        }
        if ($this->cnf->getString('SENDFILE')) {
            $needed[] = explode(':', $this->cnf->getString('SENDFILE'))[1] ?? '';
        }
        foreach ($needed as $dir) {
            if (!$dir) {
                continue;
            }
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            } else {
                @chmod($dir, 0777);
            }
        }
    }

    public function clearFile(string|int $file): void
    {
        $tmp = rtrim($this->cnf->getString('STORAGE_TMP'), '/\\');
        $files = scandir($tmp);
        if (!$files) {
            $files = [];
        }
        foreach ($files as $name) {
            if (
                is_file($tmp . '/' . $name) &&
                preg_match('(^' . preg_quote((string)$file) . '_[\dx]+\.[a-z]{3,4}$)i', $name)
            ) {
                @unlink($tmp . '/' . $name);
            }
        }
        if ($this->cnf->getString('SENDFILE')) {
            $dir = explode(':', $this->cnf->getString('SENDFILE'), 2);
            $dir = $dir[1] ?? '';
            $dir = rtrim($dir, '/\\');
            $files = is_dir($dir) ? scandir($dir) : [];
            if (!$files) {
                $files = [];
            }
            foreach ($files as $name) {
                if (
                    is_file($dir . '/' . $name) &&
                    (
                        preg_match('(^' . preg_quote((string)$file) . '$)i', $name) ||
                        preg_match('(^' . preg_quote((string)$file) . '_[\dx]+$)i', $name)
                    )
                ) {
                    @unlink($dir . '/' . $name);
                }
            }
        }
    }
    public function passwordsEncrypt(): void
    {
        if (!$this->cnf->getString('PASSWORDKEY')) {
            if (!is_writeable($this->cnf->getString('BASEDIR') . '/.env')) {
                throw new \Exception('Config file not writeable - input PASSWORDKEY manually.');
            }
            $pkey = \vakata\random\Generator::string(32);
            file_put_contents(
                $this->cnf->getString('BASEDIR') . '/.env',
                preg_replace(
                    '(PASSWORDKEY.*?\n)ui',
                    '',
                    file_get_contents($this->cnf->getString('BASEDIR') . '/.env') ?: throw new RuntimeException()
                ) . "\n" . 'PASSWORDKEY = "' . $pkey . '"' . "\n"
            );
        } else {
            $pkey = $this->cnf->getString('PASSWORDKEY');
        }

        $dbc = $this->app->db();
        foreach (
            $dbc->get(
                "SELECT id, data FROM user_providers WHERE provider = ?",
                ['PasswordDatabase'],
                'id',
                true
            ) as $user => $hash
        ) {
            $parts = explode("\n", $hash);
            if (count($parts) !== 3) {
                $iv = openssl_random_pseudo_bytes(12) ?: throw new RuntimeException();
                $tag = openssl_random_pseudo_bytes(16) ?: throw new RuntimeException();
                $cipher = openssl_encrypt($hash, 'aes-256-gcm', $pkey, 0, $iv, $tag) ?: throw new RuntimeException();
                $passwd = base64_encode($iv) . "\n" . base64_encode($tag) . "\n" . $cipher;
                $dbc->query(
                    "UPDATE user_providers SET data = ? WHERE provider = ? AND id = ?",
                    [ $passwd, 'PasswordDatabase', $user ]
                );
            }
        }
        $this->cacheEnv();
    }
    public function passwordsDecrypt(): void
    {
        if (!$this->cnf->getString('PASSWORDKEY')) {
            throw new \Exception('No decryption key.');
        }

        $dbc = $this->app->db();
        foreach (
            $dbc->get(
                "SELECT id, data FROM user_providers WHERE provider = ?",
                ['PasswordDatabase'],
                'id',
                true
            ) as $user => $hash
        ) {
            $parts = explode("\n", $hash);
            if (count($parts) === 3) {
                $iv = base64_decode($parts[0]);
                $tag = base64_decode($parts[1]);
                $passwd = openssl_decrypt(
                    $parts[2],
                    'aes-256-gcm',
                    $this->cnf->getString('PASSWORDKEY'),
                    0,
                    $iv,
                    $tag
                );
                if ($passwd !== false) {
                    $dbc->query(
                        "UPDATE user_providers SET data = ? WHERE provider = ? AND id = ?",
                        [ $passwd, 'PasswordDatabase', $user ]
                    );
                }
            }
        }

        if (!is_writeable($this->cnf->getString('BASEDIR') . '/.env')) {
            throw new \Exception('Config file not writeable - remove PASSWORDKEY manually.');
        }

        file_put_contents(
            $this->cnf->getString('BASEDIR') . '/.env',
            preg_replace(
                '(PASSWORDKEY.*?\n)ui',
                '',
                file_get_contents($this->cnf->getString('BASEDIR') . '/.env') ?: throw new RuntimeException()
            ) ?? throw new RuntimeException()
        );
        $this->cacheEnv();
    }
    public function cacheClean(): void
    {
        parent::cacheClean();
        if ($this->app instanceof App) {
            $cachePublic = $this->app->cachePublic();
            if ($cachePublic) {
                $cachePublic->clear();
                $this->cachePublic();
            }
        }
    }
    public function cacheLangs(): void
    {
        parent::cacheLangs();
        $all = [];
        foreach ($this->app->db()->all("SELECT * FROM translations") as $row) {
            if (isset($row['v'])) {
                $all[$row['locale']][$row['k']] = (string)$row['v'];
            }
        }
        $this->app->cache()->set('translations', $all);
    }
    /**
     * @return array<array-key,array<mixed>>
     */
    protected function sites(bool $preview = false): array
    {
        $dbc = $this->app->db();
        $tree = Tree::fromDatabase(
            $dbc,
            'tree_struct',
            [
                'id'       => 'id',
                'parent'   => 'pid',
                'position' => 'pos',
                'level'    => 'lvl',
                'left'     => 'lft',
                'right'    => 'rgt'
            ]
        );
        /** @var array<int,array{site:int,tree:int}> */
        $sites = $dbc->all(
            "SELECT site, tree FROM sites s WHERE s.disabled = 0 ORDER BY s.dflt DESC",
            null,
            'site'
        );
        $domains = $dbc->all(
            "SELECT site, domain FROM site_domain WHERE site IN(??)",
            [ array_keys($sites) ],
            'domain',
            true
        );
        $treeArray = $tree->toArray(false);
        foreach ($treeArray as $k => $node) {
            $treeArray[$k] = $node['data'];
        }
        $treeArray = array_values($treeArray);
        usort($treeArray, function ($a, $b) {
            return $a['pid'] < $b['pid'] ? -1 : ($a['pid'] > $b['pid'] ? 1 : ($a['pos'] <=> $b['pos']));
        });
        $pages = $dbc->all(
            "SELECT d.url, d.id, d.lang, d.hidden, d.title, d.redirect, d.template, d.menu, d.settings, d.created
             FROM tree_data_pub d
             WHERE d.published = 1"
        );
        if ($preview) {
            $previews = $dbc->all(
                "SELECT d.url, d.id, d.lang, d.hidden, d.title, d.redirect, d.template, d.menu, d.settings, d.created
                 FROM tree_data d
                 WHERE d.published = 2
                 ORDER BY created ASC"
            );
            foreach ($previews as $preview) {
                foreach ($pages as $k => $v) {
                    if (
                        $v['id'] === $preview['id'] &&
                        $v['lang'] === $preview['lang'] &&
                        strtotime($v['created']) < strtotime($preview['created'])
                    ) {
                        $pages[$k] = $preview;
                    }
                }
            }
        }
        foreach ($pages as $k => $v) {
            unset($pages[$k]['created']);
        }
        $menus = $dbc->all(
            "SELECT menu, site, lang, is_default, slug, items FROM menus",
            null,
            'menu'
        );
        foreach ($sites as $site => $data) {
            if (!(int)$site) {
                continue;
            }
            $sites[$site]['domains'] = [];
            foreach ($domains as $domain => $s) {
                if ($site === $s) {
                    $sites[$site]['domains'][] = $domain;
                }
            }
            $sites[$site]['redirects'] = $dbc->all(
                "SELECT url_from, url_to FROM redirects WHERE site = ?",
                $data['site'],
                'url_from',
                true
            );
            $sites[$site]['languages'] = $dbc->all(
                "SELECT l.lang, l.code, l.name, l.local FROM languages l, site_lang sl
                 WHERE l.lang = sl.lang AND sl.site = ?
                 ORDER BY l.lang",
                $data['site'],
                'lang'
            );
            $sites[$site]['language'] = array_keys($sites[$site]['languages'])[0];
            foreach (array_keys($sites[$site]['languages']) as $lang) {
                $ltree = Tree::fromAdjacencyArray($treeArray, 'id', 'pid', 'pos', $data['tree'], false);
                $ltree->remap();
                foreach ($pages as $page) {
                    if ($page['lang'] === $lang) {
                        $url = trim($page['url'], '/');
                        $node = $ltree->getNode($page['id'], false);
                        if ($node) {
                            if (!$preview && (int)$page['hidden']) {
                                $node->remove();
                            } else {
                                if (!(int)$page['menu']) {
                                    foreach ($menus as $menu) {
                                        if (
                                            (int)$menu['is_default'] &&
                                            (int)$menu['lang'] === $lang &&
                                            (int)$menu['site'] === (int)$data['site']
                                        ) {
                                            $page['menu'] = $menu['menu'];
                                        }
                                    }
                                }
                                foreach ($page as $k => $v) {
                                    $node->{$k} = $v;
                                }
                                if (
                                    (int)$page['id'] === (int)$data['tree'] &&
                                    (int)$page['lang'] === $sites[$site]['language']
                                ) {
                                    $sites[$site]['homepage'] = $url;
                                }
                                $sites[$site]['pages'][$url] = [
                                    'lang' => $page['lang'],
                                    'id' => $page['id']
                                ];
                            }
                            if ($page['redirect']) {
                                $sites[$site]['redirects'][$url] = $page['redirect'];
                            }
                        }
                    }
                }
                $ltree->remap();
                $sites[$site]['languages'][$lang]['tree'] = $ltree;
            }
        }
        $getMenu = function (int $lang, int $id, int $depth, Tree $tree) use (&$getMenu): array {
            if (!$depth) {
                return [];
            }
            $children = [];
            foreach ($tree->getNode($id, false)?->getChildren() ?? [] as $child) {
                $url = rtrim($child->url, '/*');
                if (strlen(trim($child->redirect))) {
                    $url = trim($child->redirect);
                }
                $children[] = [
                    'id' => $child->id,
                    'url' => $url,
                    'text' => $child->title,
                    'children' => $getMenu($lang, (int)$child->id, $depth - 1, $tree)
                ];
            }
            return $children;
        };
        foreach ($sites as $site => $data) {
            $sites[$site]['menus'] = [];
            foreach ($menus as $k => $menu) {
                if ((int)$menu['site'] !== (int)$data['site']) {
                    continue;
                }
                $tmenu = $menu;
                $items = json_decode($tmenu['items'], true);
                $tmenu['items'] = [];
                /** @psalm-suppress PossiblyNullArrayAccess */
                $mtree = $data['languages'][(int)$menu['lang']]['tree'] ?? null;
                if ($mtree) {
                    foreach ($items as $item) {
                        if ($item['type'] === 'text') {
                            $tmenu['items'][] = [
                                'id'        => null,
                                'url'       => rtrim(($item['href'] ?? '#') ?: '#', '/*'),
                                'text'      => $item['name'] ?? '',
                                'children'  => []
                            ];
                        } else {
                            $mnode = $mtree->getNode((int)$item['type'], false);
                            if (!$mnode) {
                                continue;
                            }
                            $tmenu['items'][] = [
                                'id' => $item['type'],
                                'url' => $item['href'] ?: rtrim($mnode->url, '/*'),
                                'text' => $item['name'] ?: $mnode->title,
                                'children' => $getMenu(
                                    (int)$menu['lang'],
                                    (int)$item['type'],
                                    (int)$item['depth'],
                                    $mtree
                                )
                            ];
                        }
                    }
                }
                $sites[$site]['menus'][$menu['menu']] = $tmenu;
            }
        }
        $templates = $dbc->all("SELECT template, base, zones, widgets FROM templates", null, 'template');
        foreach ($templates as $template => $data) {
            $templates[$template]['zones'] = json_decode($data['zones'] ?? '[]', true) ?? [];
            $templates[$template]['widgets'] = json_decode($data['widgets'] ?? '[]', true) ?? [];
        }
        foreach ($sites as $site => $data) {
            $sites[$site]['templates'] = $templates;
        }
        $sites['domains'] = $domains;
        return $sites;
    }
    public function cachePublic(bool $preview = false): void
    {
        if ($this->app instanceof App && $cache = $this->app->cachePublic()) {
            $sites = $this->sites($preview);
            $cache->set('sites' . ($preview ? '.preview' : ''), $sites, $preview ? 1800 : 0);
        }
    }

    public function frontendFix(): void
    {
        $base = $this->cnf->getString('BASEDIR') . '/public/assets';

        // FOMANTIC
        $file = $base . '/static/fomantic-ui-css/semantic.min.css';
        $content = file_get_contents($file) ?: throw new RuntimeException();
        // remove Lato and use system font
        $content = str_replace('font-family:Lato,', 'font-family:', $content);
        // remove animation
        // $content = preg_replace('(([\d]*\.)?[\d]+m?s( |;|\}))i', '0s$2', $content) ?? '';
        // remove border-radius
        // $content = preg_replace_callback(
        //     '(radius:([^;}]+))i',
        //     function (array $matches) {
        //         $values = explode(' ', $matches[1]);
        //         foreach ($values as $k => $v) {
        //             if ($v == '.21428571rem' || $v == '.28571429rem') {
        //                 //$values[$k] = '2px';
        //                 $values[$k] = '0';
        //             }
        //         }
        //         return 'radius:' . implode(' ', $values);
        //     },
        //     $content
        // );
        // file_put_contents($file, $content ?? throw new RuntimeException());
        file_put_contents($file, $content);

        // remove inline styles from semantic
        $file = $base . '/static/fomantic-ui-css/semantic.min.js';
        $content = file_get_contents($file) ?: throw new RuntimeException();
        $content = str_replace('.attr("style"', '.css("cssText"', $content);
        file_put_contents($file, $content);

        // TINYMCE
        // add nonce scripts
        $file = $base . '/static/tinymce/tinymce.min.js';
        $content = file_get_contents($file) ?: throw new RuntimeException();
        $content = preg_replace(
            '(([a-z]+)' . preg_quote('.id="mceDefaultStyles",') . ')ui',
            '$1.id="mceDefaultStyles",$1.setAttribute("nonce",window.tinyNonce),',
            str_replace('([a-z]+).setAttribute("nonce",window.tinyNonce),', '', $content)
        ) ?? '';
        $content = str_replace('style="display: inline-block;"', '', $content);
        file_put_contents($file, $content);

        // PLUPLOAD
        // remove inline styles
        $file = $base . '/static/plupload/plupload.full.min.js';
        $content = file_get_contents($file) ?: throw new RuntimeException();
        $content = preg_replace(
            '(style="[^"]+")ui',
            '',
            $content
        ) ?? '';
        file_put_contents($file, $content);

        // JSTREE
        copy($base . '/jstree.png', $base . '/static/jstree/themes/default/32px.png');

        // remove source maps
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $base . '/static/',
                \FilesystemIterator::KEY_AS_PATHNAME |
                \FilesystemIterator::CURRENT_AS_FILEINFO |
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $object) {
            if (
                $object->isFile() &&
                preg_match('(\.(js|css)$)i', $object->getFileName())
            ) {
                file_put_contents(
                    $object->getRealPath(),
                    preg_replace(
                        '(^//#\s*sourceMappingURL.*$)mi',
                        '',
                        file_get_contents($object->getRealPath()) ?: ''
                    ) ?? ''
                );
            }
        }
    }
    public function frontendSVG(): void
    {
        $base = $this->cnf->getString('BASEDIR') . '/public/assets';

        // svg local download
        $file = $base . '/static/fomantic-ui-css/semantic.min.css';
        $content = file_get_contents($file) ?: throw new RuntimeException();
        if (!is_dir($base . '/static/fomantic-ui-css/svg/')) {
            mkdir($base . '/static/fomantic-ui-css/svg/', 0775);
        }
        $content = preg_replace_callback(
            '(i\.flag\.[^{]+\{[^}]+\})i',
            function (array $m) use ($base) {
                return preg_replace_callback(
                    '(https://cdn.jsdelivr.net/gh/jdecked/twemoji@latest/assets/svg/[\da-z\-]+\.svg)i',
                    function (array $matches) use ($base) {
                        $f = (explode('/svg/', $matches[0])[1] ?? null);
                        if (!$f) {
                            return $matches[0];
                        }
                        if (!is_file($base . '/static/fomantic-ui-css/svg/' . $f)) {
                            $d = file_get_contents($matches[0]);
                            if (!$d) {
                                return $matches[0];
                            }
                            file_put_contents($base . '/static/fomantic-ui-css/svg/' . $f, $d);
                        }
                        return './svg/' . $f;
                    },
                    (string)$m[0]
                ) ?? '';
            },
            $content
        );
        file_put_contents($file, $content ?? throw new RuntimeException());
    }

    // long-running script
    public function mailer(): void
    {
        $dbc = $this->app->db();

        $run = true;
        /** @psalm-suppress all */
        pcntl_signal(SIGINT, function () use (&$run) {
            $run = false;
        });

        while ($run) {
            pcntl_signal_dispatch();

            /**
             * @psalm-suppress TypeDoesNotContainType
             * @phpstan-ignore-next-line
             */
            if (!$run) {
                break;
            }
            /**
             * @psalm-suppress RedundantCondition
             * @phpstan-ignore-next-line
             */
            while ($run && $dbc->one("SELECT 1 FROM mails WHERE started IS NOT NULL AND finished IS NULL")) {
                pcntl_signal_dispatch();
                usleep(1000000);
                $dbc->query(
                    "UPDATE mails SET started = NULL WHERE started IS NOT NULL AND finished IS NULL AND started < ?",
                    date('Y-m-d H:i:s', time() - 120)
                );
            }
            /**
             * @psalm-suppress TypeDoesNotContainType
             * @phpstan-ignore-next-line
             */
            if (!$run) {
                break;
            }
            /** @psalm-suppress RedundantCondition */
            do {
                pcntl_signal_dispatch();
                $task = $dbc->one(
                    $dbc->driverName() === 'oracle' ?
                        "SELECT * FROM mails WHERE started IS NULL ORDER BY priority DESC, added ASC LIMIT 1" :
                        "SELECT * FROM mails WHERE started IS NULL
                         ORDER BY priority DESC, added ASC FETCH FIRST 1 ROW ONLY"
                );
                if ($task) {
                    break;
                } else {
                    usleep(1000000);
                }
            } while ($run);
            pcntl_signal_dispatch();

            /**
             * @psalm-suppress TypeDoesNotContainType
             * @phpstan-ignore-next-line
             */
            if (!$run) {
                break;
            }
            if (!$task) {
                continue;
            }
            if (!filter_var($task['recipient'], FILTER_VALIDATE_EMAIL)) {
                $dbc->query(
                    "UPDATE mails SET started = ?, finished = ?, result = 'ERR' WHERE mail = ?",
                    [ date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $task['mail'] ]
                );
                continue;
            }

            $mailer = $this->cnf->getString('SMTP_CONNECTION') ?
                new \vakata\mail\driver\SMTPSender(
                    $this->cnf->getString('SMTP_CONNECTION'),
                    $this->cnf->getString('SMTP_USER'),
                    $this->cnf->getString('SMTP_PASSWORD'),
                ) :
                new \vakata\mail\driver\MailSender();

            $dbc->query(
                "UPDATE mails SET started = ? WHERE mail = ?",
                [ date('Y-m-d H:i:s'), $task['mail'] ]
            );

            $mail = \vakata\mail\Mail::fromString($task['content']);
            try {
                $mailer->send($mail);
                $dbc->query(
                    "UPDATE mails SET finished = ? WHERE mail = ?",
                    [ date('Y-m-d H:i:s'), $task['mail'] ]
                );
            } catch (\Exception) {
                $dbc->query(
                    "UPDATE mails SET started = NULL WHERE mail = ?",
                    [ $task['mail'] ]
                );
            }
        }
    }
    public function schema(): void
    {
        $this->cacheSchema();
        $schema = $this->app->db(true)->getSchema();
        $tables = [];
        $pivots = [];
        foreach ($schema->getTables() as $table) {
            $tables[] = $table->getName();
            foreach ($table->getRelations() as $relation) {
                $pivots[] = $relation->pivot?->getName();
            }
        }
        $tables = array_unique(array_filter($tables));
        $pivots = array_unique(array_filter($pivots));
        $base = $this->cnf->getString('BASEDIR') . '/app/classes/schema/';
        $name = 'schema';
        foreach ($tables as $table) {
            if (in_array($table, $pivots)) {
                continue;
            }
            $clss = implode('', array_map('ucfirst', array_filter(explode('_', $table)))) . "Entity";
            if (!is_file($base . $clss . '.php')) {
                file_put_contents(
                    $base . $clss . '.php',
                    <<<EOF
                    <?php

                    declare(strict_types=1);

                    namespace $name;

                    use vakata\database\schema\Entity;

                    class $clss extends Entity
                    {
                    }
                    
                    EOF
                );
            }
            $prop = [];
            foreach ($schema->getTable($table)->getFullColumns() as $column) {
                $type = '';
                if ($column->isNullable()) {
                    $type .= '?';
                }
                switch ($column->getBasicType()) {
                    case 'int':
                        $type .= 'int';
                        break;
                    case 'float':
                        $type .= 'int';
                        break;
                    default:
                        $type .= 'string';
                        break;
                }
                $prop[$column->getName()] = ' * @property ' . $type . ' $' . $column->getName();
            }
            foreach ($schema->getTable($table)->getRelations() as $relation) {
                $type = implode('', array_map('ucfirst', array_filter(explode('_', $relation->table->getName()))));
                $type .= "Entity";
                if ($relation->many) {
                    $type = '\\vakata\\collection\\Collection<int,' . $type . '>';
                } else {
                    $nullable = false;
                    foreach ($relation->keymap as $own => $remote) {
                        if (
                            $relation->self->getColumn($own)?->isNullable() ||
                            $relation->table->getColumn($remote)?->isNullable()
                        ) {
                            $nullable = true;
                        }
                    }
                    if ($nullable) {
                        $type = '?' . $type;
                    }
                }
                $prop[$relation->name] = ' * @property ' . $type . ' $' . $relation->name;
                if ($relation->pivot) {
                    $prop[$relation->name] .= ' via ' . $relation->pivot->getName();
                }
            }
            $cmnt = "/**\n" . implode("\n", $prop) . "\n */";
            $data = file_get_contents($base . $clss . '.php') ?: '';
            $data = preg_replace('(/\*\*.*?\nclass )is', "\nclass ", $data) ?? '';
            $data = preg_replace("(\n+class )", "\n\n" . $cmnt . "\nclass ", $data) ?? '';
            file_put_contents($base . $clss . '.php', $data);
        }
    }
    public function search(): void
    {
        $dbc = $this->app->db();
        $schema = $dbc->parseSchema()->getSchema();
        if ($dbc->driverName() !== 'postgre') {
            return;
        }
        $tables = [];
        foreach ($schema->getTables() as $table) {
            $tables[] = $table->getName();
        }
        $tables = array_unique($tables);
        foreach ($tables as $name) {
            $table = $schema->getTable($name);
            if ($table->getColumn('tsindex', true)) {
                if (!$this->cnf->getBool('FULLTEXT')) {
                    $dbc->query(
                        "ALTER TABLE {$table->getFullName()} DROP COLUMN tsindex"
                    );
                }
                continue;
            }
            if (!$this->cnf->getBool('FULLTEXT')) {
                continue;
            }
            $lang = $table->getColumn('lang') !== null &&
                $table->getRelation('languages') !== null &&
                $dbc->one("SELECT 1 FROM pg_ts_config WHERE cfgname = ?", ['bulgarian']);
            $columns = [];
            foreach ($table->getFullColumns() as $column) {
                if ($column->getBasicType() === 'text') {
                    $columns[] = $column->isNullable() ?
                        'coalesce(' . $column->getName() . ', \'\')::text' :
                        $column->getName() . '::text';
                }
            }
            if (count($columns)) {
                $dict = $lang ?
                    "(CASE lang
                        WHEN 1 THEN 'bulgarian'::regconfig
                        WHEN 2 THEN 'english'::regconfig
                        ELSE 'simple'::regconfig END)" :
                    "'simple'::regconfig";
                $expr = implode(' || \' \' || ', $columns);
                $dbc->query(
                    "ALTER TABLE {$table->getFullName()}
                     ADD tsindex tsvector GENERATED ALWAYS AS (to_tsvector({$dict}, {$expr})) STORED"
                );
                $dbc->query(
                    "CREATE INDEX tsindex_{$table->getName()}_idx ON {$table->getFullName()} USING GIN(tsindex)"
                );
            }
        }
        $this->cacheClean();
        $this->app->db(true);
    }

    public function ekatte(): void
    {
        if (false === $this->cnf->getBool('FEATURE_EKATTE')) {
            throw new RuntimeException('EKATTE feature is not enabled');
        }

        $dbc = $this->app->db();
        $schema = $dbc->parseSchema()->getSchema();

        // validate if tables exist
        $tables = [ 'regions', 'municipalities', 'cities', 'test' ];
        $missingTables = array_filter($tables, static function (string $table) use ($schema) {
            return false === $schema->hasTable($table);
        });

        if ($missingTables) {
            echo sprintf("Found missing tables: %s... skipping DB update\n", implode(', ', $missingTables));
        }

        $withUpdate = empty($missingTables);

        // region - download and save ekatte zip file to tmp dir
        $ekatteTmpDir = $this->cnf->getString('STORAGE_TMP') . '/ekatte';
        $ekatteZip = $ekatteTmpDir . '/ekatte.zip';
        $ekatteType = 'excel'; // can be 'excel' or 'json' (but we handle excel only)
        $downloadUrl = 'https://www.nsi.bg/nrnm/ekatte/zip/download?files_type=' . $ekatteType;

        if (!is_dir($ekatteTmpDir)) {
            @mkdir($ekatteTmpDir, 0777, true);
        }

        echo "Attempting to download ekatte.zip from nsi.bg...\n";
        if (
            !file_put_contents(
                $ekatteZip,
                file_get_contents($downloadUrl) ?: throw new RuntimeException('Cannot read zip file')
            )
        ) {
            throw new RuntimeException('Cannot save zip file');
        }
        echo "Successfully downloaded and saved ekatte.zip\n";
        // endregion

        // region - extract xlsx files from ekatte zip
        $zip = new \ZipArchive();
        if ($zip->open($ekatteZip) !== true) {
            throw new RuntimeException('Cannot load ZIP');
        }
        if (!$zip->extractTo($ekatteTmpDir)) {
            throw new RuntimeException('Cannot extract xlsx files from ZIP');
        }
        $zip->close();

        // function that validates and reads data from xlsx file
        $readXlsxFile = static function (string $xlsxFilename) use ($ekatteTmpDir): Reader {
            if (!file_exists($ekatteTmpDir . '/' . $xlsxFilename)) {
                throw new RuntimeException('File ' . $xlsxFilename . ' not found');
            }

            if (false === class_exists(Reader::class)) {
                throw new RuntimeException('Error: class "' . Reader::class . '" does not exist');
            }

            return Reader::fromFile($ekatteTmpDir . '/' . $xlsxFilename);
        };
        // endregion

        // region - read xlsx files and prepare sorted collections of regions, municipalities and cities
        echo "Starting xlsx extraction and processing...\n";

        $regions = (new Collection($readXlsxFile('ek_obl.xlsx')->getIterator()))
            ->filter(fn (array $row, int $n): bool => $n > 4) // skip initial info rows
            ->map(function (array $row): array {
                $region = Region::fromCode($row[2])?->value;

                return [
                    'region' => $region,
                    'code' => $row[2],
                    'name' => $row[3],
                    'name_en' => $row[4],
                    'pos' => $region, // should be same as region
                ];
            })
            ->sortBy(fn (array $a, array $b): int => $a['pos'] <=> $b['pos']) // sort regions by pos
            ->squash();

        // municipalities
        $municipalitiesMap = [];
        $municipalities = (new Collection($readXlsxFile('ek_obst.xlsx')->getIterator()))
            ->filter(fn (array $row, int $n): bool => $n > 4) // skip initial info rows
            ->map(function (array $row) use (&$municipalitiesMap): array {
                $municipality = $row[10]; // use pos as id
                $code = $row[0];

                // fill municipalities map for later usage
                $municipalitiesMap[$code] = $municipality;

                return [
                    'municipality' => $municipality,
                    'code' => $code,
                    'region' => Region::fromCode(substr($code, 0, 3))?->value,
                    'name' => $row[3],
                    'name_en' => $row[4],
                    'pos' => $row[10],
                ];
            })
            ->sortBy(fn (array $a, array $b): int => $a['pos'] <=> $b['pos']) // sort municipalities by pos
            ->squash();

        // cities
        $cities = (new Collection($readXlsxFile('ek_atte.xlsx')->getIterator()))
            ->filter(fn (array $row, int $n): bool => $n > 4) // skip initial info rows
            ->map(function (array $row) use (&$municipalitiesMap): array {
                $city = $row[0];
                $municipality = $municipalitiesMap[$row[6]];

                // update city-municipalities map
                $municipalitiesMap[$city] = $municipality;

                return [
                    'city'         => (int) $city,
                    'name'         => $row[2],
                    'name_en'      => $row[3],
                    'type'         => CityType::fromEkAtteType($row[12])->value,
                    'municipality' => $municipality,
                    'pos'          => $row[17],
                    'parent'       => null,
                ];
            })
            ->squash();

        // add settlements to cities
        $settlementCities = (new Collection($readXlsxFile('ek_sobr.xlsx')->getIterator()))
            ->filter(fn (array $row, int $n): bool => $n > 4) // skip initial info rows
            ->map(function (array $row) use (&$municipalitiesMap): array {
                // get municipality from $row[4] - could be ekatte code (12345) or municipality code (ABC45)
                $ekatteOrMunicipalityCode = str_replace([ '(', ')' ], '', substr($row[4], 0, 7));

                return [
                    'city'         => (int) $row[0],
                    'name'         => $row[2],
                    'name_en'      => $row[3],
                    'type'         => CityType::fromEkSobrType($row[1])->value,
                    'municipality' => $municipalitiesMap[$ekatteOrMunicipalityCode],
                    'pos'          => 9999,
                    'parent'       => null,
                ];
            })
            ->toArray();

        /** @psalm-suppress InvalidArgument */
        $cities = $cities->extend($settlementCities);

        // add custom cities to cities
        $customCities = [];
        $sofiaCity = 68134;
        $customSofiaCities = [
            [ 'city' => 96181, 'name' => 'София, кв. Бояна', 'name_en' => 'Sofia, kv. Boyana' ],
            [ 'city' => 96610, 'name' => 'София, кв. Княжево', 'name_en' => 'Sofia, kv. Knyazhevo' ],
            [ 'city' => 96623, 'name' => 'София, кв. Горна Баня', 'name_en' => 'Sofia, kv. Gorna Banya' ],
            [ 'city' => 97015, 'name' => 'София, кв. Симеоново', 'name_en' => 'Sofia, kv. Simeonovo' ],
            [ 'city' => 97029, 'name' => 'София, кв. Драгалевци', 'name_en' => 'Sofia, kv. Dragalevci' ],
            [ 'city' => 97032, 'name' => 'София, кв. Суходол', 'name_en' => 'Sofia, kv. Suhodol' ],
            [ 'city' => 97046, 'name' => 'София, кв. Филиповци', 'name_en' => 'Sofia, kv. Filipovci' ],
            [ 'city' => 97063, 'name' => 'София, кв. Република', 'name_en' => 'Sofia, kv. Republika' ],
            [ 'city' => 97077, 'name' => 'София, кв. Илиенци', 'name_en' => 'Sofia, kv. Ilienci' ],
            [ 'city' => 97080, 'name' => 'София, кв. Требич', 'name_en' => 'Sofia, kv. Trebich' ],
            [ 'city' => 97094, 'name' => 'София, кв. Бенковски', 'name_en' => 'Sofia, kv. Benkovski' ],
            [ 'city' => 97149, 'name' => 'София, ж.к. Филиповци', 'name_en' => 'Sofia, zh.k. Filipovci' ],
            [ 'city' => 97152, 'name' => 'София, кв. Чепинско Шосе', 'name_en' => 'Sofia, kv. Chepinsko Shose' ],
            [ 'city' => 98017, 'name' => 'София, кв. Левски', 'name_en' => 'Sofia, kv. Levski' ],
            [ 'city' => 98065, 'name' => 'София, кв. Челопечене', 'name_en' => 'Sofia, kv. Chelopechene' ],
            [ 'city' => 98096, 'name' => 'София, кв. Сеславци', 'name_en' => 'Sofia, kv. Seslavci' ],
            [ 'city' => 98106, 'name' => 'София, кв. Враждебна', 'name_en' => 'Sofia, kv. Vrazhdebna' ],
            [ 'city' => 98114, 'name' => 'София, кв. Кремиковци', 'name_en' => 'Sofia, kv. Kremikovci' ],
            [ 'city' => 98123, 'name' => 'София, кв. Ботунец', 'name_en' => 'Sofia, kv. Botunec' ],
            [ 'city' => 99053, 'name' => 'София, кв. Обеля', 'name_en' => 'Sofia, kv. Obelya' ],
            [ 'city' => 99139, 'name' => 'София, кв. Горубляне', 'name_en' => 'Sofia, kv. Gorublyane' ],
        ];

        foreach ($customSofiaCities as $customSofiaCity) {
            $customCities[] = array_merge($customSofiaCity, [
                'type'         => CityType::CITY_CUSTOM->value,
                'municipality' => $municipalitiesMap[$sofiaCity],
                'parent'       => $sofiaCity,
                'pos'          => 9999,
            ]);
        }
        // add more custom cities here...

        /** @psalm-suppress PossiblyInvalidArgument */
        $cities = $cities->extend(Collection::from($customCities)->toArray());

        // sort all cities
        $cities->sortBy(fn (array $a, array $b): int => $a['pos'] <=> $b['pos']);
        // endregion

        // region - write sql inserts for regions, municipalities and cities for each db driver
        $drivers = [ 'postgre', 'mysql', 'sqlite', 'oracle' ];

        $getMigrationDataFile = function (string $driver) use ($drivers): string {
            if (false === in_array($driver, $drivers)) {
                throw new RuntimeException('Driver ' . $driver . ' is not supported');
            }

            $migrationDataFile = sprintf(
                '%s/%s/webadmin/ekatte/000/data.sql',
                $this->cnf->getString('STORAGE_DATABASE'),
                $driver
            );

            if (false === file_exists($migrationDataFile)) {
                throw new RuntimeException('File ' . $migrationDataFile . ' not found');
            }

            return $migrationDataFile;
        };

        // reset migration data file for each driver
        foreach ($drivers as $driver) {
            file_put_contents($getMigrationDataFile($driver), '');
        }

        $writeToFiles = static function (string $sql) use ($drivers, $getMigrationDataFile): void {
            foreach ($drivers as $driver) {
                file_put_contents($getMigrationDataFile($driver), $sql, FILE_APPEND);
            }
        };

        // prepare db regions, municipalities and cities for checks
        $dbRegions = $withUpdate ? $dbc->all('SELECT * FROM regions', null, 'region') : null;
        $dbMunicipalities = $withUpdate ? $dbc->all('SELECT * FROM municipalities', null, 'municipality') : null;
        $dbCities = $withUpdate ? $dbc->all('SELECT * FROM cities', null, 'city') : null;

        $added = [];
        $changed = [];

        $regions->each(function (array $region) use ($writeToFiles, &$added, &$changed, &$dbRegions): void {
            $writeToFiles(
                sprintf(
                    "INSERT INTO regions (region, code, name, name_en, pos) VALUES (%d, '%s', '%s', '%s', %d);\n",
                    $region['region'],
                    $region['code'],
                    $region['name'],
                    $region['name_en'],
                    $region['pos'],
                )
            );

            // check if region exists in db and if it has changes
            $dbRegion = $dbRegions[$region['region']] ?? null;
            if ($dbRegion === null) {
                $added[] = [ 'entity' => 'regions', 'data' => $region ];
            } elseif ($diff = array_diff($dbRegion, $region)) {
                $changed[] = [ 'entity' => 'regions', 'data' => $region, 'diff' => $diff ];
            }

            // remove region from db list so that only missing remain at the end
            if (isset($dbRegions[$region['region']])) {
                unset($dbRegions[$region['region']]);
            }
        });
        $writeToFiles("\n"); // add empty row

        $municipalities->each(
            function (array $municipality) use ($writeToFiles, &$added, &$changed, &$dbMunicipalities) {
                $writeToFiles(
                    sprintf(
                    // phpcs:ignore Generic.Files.LineLength
                        "INSERT INTO municipalities (municipality, code, name, name_en, region, pos) VALUES (%d, '%s', '%s', '%s', %d, %d);\n",
                        $municipality['municipality'],
                        $municipality['code'],
                        $municipality['name'],
                        $municipality['name_en'],
                        $municipality['region'],
                        $municipality['pos'],
                    )
                );

                // check if municipality exists in db and if it has changes
                $dbMunicipality = $dbMunicipalities[$municipality['municipality']] ?? null;
                if ($dbMunicipality === null) {
                    $added[] = [ 'entity' => 'municipalities', 'data' => $municipality ];
                } elseif ($diff = array_diff($dbMunicipality, $municipality)) {
                    $changed[] = [ 'entity' => 'municipalities', 'data' => $municipality, 'diff' => $diff ];
                }

                // remove municipality from db list so that only missing remain at the end
                if (isset($dbMunicipalities[$municipality['municipality']])) {
                    unset($dbMunicipalities[$municipality['municipality']]);
                }
            }
        );
        $writeToFiles("\n"); // add empty row

        $cities->each(function (array $city) use ($writeToFiles, &$added, &$changed, &$dbCities) {
            $writeToFiles(
                sprintf(
                // phpcs:ignore Generic.Files.LineLength
                    "INSERT INTO cities (city, name, name_en, type, municipality, pos, parent) VALUES (%d, '%s', '%s', %d, %d, %d, %s);\n",
                    $city['city'],
                    $city['name'],
                    $city['name_en'],
                    $city['type'],
                    $city['municipality'],
                    $city['pos'],
                    $city['parent'] ?? 'NULL', // @phpstan-ignore-line
                )
            );

            // check if city exists in db and if it has changes
            $dbCity = $dbCities[$city['city']] ?? null;
            if ($dbCity === null) {
                $added[] = [ 'entity' => 'cities', 'data' => $city ];
            } elseif ($diff = array_diff($dbCity, $city)) {
                $changed[] = [ 'entity' => 'cities', 'data' => $city, 'diff' => $diff ];
            }

            // remove city from db list so that only missing remain at the end
            if (isset($dbCities[$city['city']])) {
                unset($dbCities[$city['city']]);
            }
        });
        // endregion

        // fill missing list
        $missing = [];
        if ($dbRegions) {
            foreach ($dbRegions as $region) {
                $missing[] = [ 'entity' => 'regions', 'data' => $region ];
            }
        }
        if ($dbMunicipalities) {
            foreach ($dbMunicipalities as $municipality) {
                $missing[] = [ 'entity' => 'municipalities', 'data' => $municipality ];
            }
        }
        if ($dbCities) {
            foreach ($dbCities as $city) {
                $missing[] = [ 'entity' => 'cities', 'data' => $city ];
            }
        }

        // region update DB
        if ($withUpdate) {
            if ($added || $changed || $missing) {
                $dbc->begin();

                try {
                    echo "Changes found:\n";

                    foreach ($added as $data) {
                        $dbc->table($data['entity'])->insert($data['data']);

                        echo sprintf(
                            " - new in %s: %s\n",
                            $data['entity'],
                            json_encode($data['data'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                        );
                    }

                    foreach ($changed as $data) {
                        $columnId = match ($data['entity']) {
                            'regions' => 'region',
                            'municipalities' => 'municipality',
                            'cities' => 'city',
                        };
                        $dbc->table($data['entity'])
                            ->filter($columnId, $data['data'][$columnId])
                            ->update($data['data']);

                        echo sprintf(
                            " - changed in %s: %s (old: %s)\n",
                            $data['entity'],
                            json_encode($data['data'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            json_encode($data['diff'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        );
                    }

                    foreach ($missing as $data) {
                        echo sprintf(
                            " - missing %s: %s\n",
                            $data['entity'],
                            json_encode($data['data'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                        );
                    }

                    if ($added || $changed) {
                        echo "Applied DB changes\n";
                    }

                    $dbc->commit();
                } catch (\Throwable $e) {
                    $dbc->rollback();
                    throw $e;
                }
            } else {
                echo "No changes found\n";
            }
        }

        echo "Successfully finished xlsx processing\n";
        // endregion
    }
}
