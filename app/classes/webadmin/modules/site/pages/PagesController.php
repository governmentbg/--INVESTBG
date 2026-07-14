<?php

declare(strict_types=1);

namespace webadmin\modules\site\pages;

use webadmin\App;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\http\Uri as Url;
use vakata\views\Views;
use webadmin\components\html\Form;
use webadmin\components\html\Field;
use vakata\config\Config;
use vakata\user\User;
use vakata\intl\Intl;
use vakata\phptree\Node;
use webadmin\modules\site\WidgetProviderInterface;

class PagesController
{
    protected PagesService $service;
    protected Form $settingsForm;
    protected Form $permissionsForm;

    public function __construct(PagesService $service, Intl $intl, User $user)
    {
        if (!$user->site) {
            throw new \Exception('No site for user');
        }
        $this->service = $service;
        $this->settingsForm = (new Form())
            ->addField(new Field('text', ['name' => 'url'], ['label' => 'pages.fields.url']))
            ->addField(new Field('text', ['name' => 'redirect'], ['label' => 'pages.fields.redirect']))
            ->addField(new Field('text', ['name' => 'title_abbr'], ['label' => 'pages.fields.abbr']))
            ->addField(new Field('text', ['name' => 'title_short'], ['label' => 'pages.fields.short']))
            ->addField(new Field('text', ['name' => 'title_full'], ['label' => 'pages.fields.long']))
            ->addField(
                new Field(
                    'select',
                    ['name' => 'menu'],
                    ['label' => 'pages.settings.menu', 'values' => $this->service->menus() ]
                )
            )
            ->addField(
                new Field('checkbox', ['name' => 'sitemap'], ['label' => 'pages.settings.sitemap', 'nobr' => 1])
            )
            ->addField(
                new Field('checkbox', ['name' => 'parentmenu'], ['label' => 'pages.settings.parentmenu', 'nobr' => 1])
            )
            ->addField(
                new Field('checkbox', ['name' => 'breadcrumb'], ['label' => 'pages.settings.inbreadcrumb', 'nobr' => 1])
            )
            ->addField(
                new Field(
                    'checkbox',
                    ['name' => 'nocache'],
                    [
                        'label' => 'pages.fields.cache',
                        'nobr' => 0
                    ]
                )
            )
            ->addField(
                (new Field('json', ['name' => 'meta'], ['label' => '']))
                    ->setOption(
                        'form',
                        (new Form())
                            ->addField(new Field('text', ['name' => 'tag'], ['label' => 'pages.settings.metatag']))
                            ->addField(new Field('text', ['name' => 'value'], ['label' => 'pages.settings.metavalue']))
                    )
            )
            ->addField(new Field('text', ['name' => 'clss'], ['label' => 'pages.settings.clss']))
            ->setLayout([
                'pages.settings.paths',
                ['url', 'redirect'],
                //'pages.settings.names',
                //['title_abbr', 'title_short'],
                //['title_full'],
                'pages.settings.settings',
                ['menu'],
                ['sitemap', 'parentmenu', 'breadcrumb'],
                'pages.settings.indexing',
                ['meta'],
                'pages.settings.advanced',
                ['nocache', 'clss']
            ]);
        $this->permissionsForm = (new Form())
            ->addField(
                new Field(
                    'tree',
                    ['name' => 'editors'],
                    [
                        'label' => 'pages.fields.editors',
                        'multiple' => true,
                        'values' => $this->service->permissionOptions($intl),
                        'plugins' => ['checkbox']
                    ]
                )
            )
            ->addField(
                new Field(
                    'tree',
                    ['name' => 'publishers'],
                    [
                        'label' => 'pages.fields.publishers',
                        'multiple' => true,
                        'values' => $this->service->permissionOptions($intl),
                        'plugins' => ['checkbox']
                    ]
                )
            );
    }

    public function getIndex(Response $res, Url $url, Views $views): Response
    {
        $views->addFolder('pages', __DIR__ . '/views');
        $module = $url->getSegment(0);
        return $res->setBody(
            $views->render(
                'pages::index',
                [
                    'name'              => $module,
                    'languages'         => $this->service->availableLanguages(),
                    'structPermission'  => $this->service->canChangeStructure(),
                    'publishPermission' => $this->service->canPublish(),
                    'changePermission'  => $this->service->canChangePermissions(),
                    'widgetPermission'  => $this->service->canChangeWidgets(),
                    'permissions'       => $this->permissionsForm,
                    'settings'          => $this->settingsForm,
                    'widgetsForm'       => '',
                    'templates'         => $this->service->templates(),
                    'widgets'           => $this->service->widgets()
                ]
            )
        );
    }

    protected function nodeToArray(Node $node): array
    {
        return [
            'id'        => $node->id,
            'text'      => $node->data['title'] ?? ' ',
            'li_attr'   => [
                'class' => isset($node->data['hidden']) && (int)$node->data['hidden'] ? 'tree-hidden' : ''
            ],
            'a_attr'   => [
                'title' => $node->id,
                'class' => isset($node->data['stale']) && $node->data['stale'] ? 'tree-stale' : ''
            ],
            'children'  => $node->hasChildren(),
            'icon'      => 'ui large file alternate outline icon',
            'data'      => [ ]
        ];
    }
    public function getNode(Request $req, Response $res): Response
    {
        try {
            $temp = (int)$req->getQuery('id') ?
                $this->service->getChildren($req->getQuery('id', 0, 'int'), $req->getQuery('lang', 1, 'int')) :
                $this->service->getRoots($req->getQuery('lang', 1, 'int'));
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        $rslt = [];
        foreach ($temp as $node) {
            $rslt[] = $this->nodeToArray($node);
        }
        return $res
            ->setBody(json_encode($rslt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function getNodes(Request $req, Response $res): Response
    {
        $ids  = array_filter(explode(',', $req->getQuery('id')));
        $lang = $req->getQuery('lang', 1, 'int');
        try {
            $nodes = $this->service->getNodes($ids, $lang);
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }

        $rslt = [];
        foreach ($nodes as $id => $children) {
            $temp = [];
            foreach ($children as $node) {
                $temp[] = $this->nodeToArray($node);
            }
            $rslt[$id] = $temp;
        }
        return $res
            ->setBody(json_encode($rslt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function getData(Request $req, Response $res, Views $views, Intl $intl): Response
    {
        $id   = (int)$req->getQuery('id');
        $lang = $req->getQuery('lang', 1, 'int');
        try {
            $node = $this->service->getNode($id, $lang);
        } catch (PagesException $e) {
            return $res->withStatus(403);
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        $data = count($node->data) ? $node->data : [ 'id' => $id, 'lang' => $lang, 'content' => '' ];
        $settings = json_decode($data['settings'] ?? '', true) ?? [];
        if (!isset($settings['url'])) {
            $settings['url'] = $data['url'] ?? '';
        }
        $settings['url'] = rtrim($settings['url'], '/*');
        $permissions = json_decode($data['permissions'] ?? '', true) ?? [];
        $content = json_decode($data['content'] ?? '', true) ?? [];
        $widgets = $content['widgets'] ?? [];
        if (!isset($widgets['widget_main'])) {
            $widgets = [ 'widget_main' => ['__hidden' => '0'] ] + $widgets;
        }
        $zones = [];
        $templates = $this->service->templates();
        if (!isset($data['template'])) {
            $data['template'] = $templates[0]['template'];
        }
        foreach ($templates as $template) {
            if ($data['template'] == $template['template']) {
                $zones = json_decode($template['zones'] ?? '[]', true);
            }
        }
        if (!is_array($zones)) {
            $zones = [];
        }
        $zones[] = 'main';
        $zones = array_unique($zones);

        unset($content['widgets']);
        $forms = $this->widgetsForms($widgets, [ 'id' => $id, 'lang' => $lang ]);
        $data['html'] = [
            'widgets' => implode(
                '',
                array_map(
                    function (Form $v, int|string $k) use ($views, $intl, $zones) {
                        $k = (string)$k;
                        $hidden = (int)$v->getField('__hidden')->getValue();

                        $zone = ($v->getField('__zone')->getValue() ?? 'main') ?: 'main';
                        $dropdown = '<div ' .
                            'class="ui widget-zone teal icon compact mini right floated pointing dropdown button">' .
                            '<i class="th icon"></i>' .
                            '<div class="menu">';
                        foreach ($zones as $z) {
                            $dropdown .= '<div class="' . ($z === $zone ? 'selected' : '') . ' item">' . $z . '</div>';
                        }
                        $dropdown .= '</div></div>';

                        return '<div data-serialize="' . $k . '" ' .
                            ($k === 'widget_main' ? 'class="widget_main"' : '') . '>' .
                            '<button class="ui widget-remove red icon compact mini right floated button">' .
                            '<i class="close icon"></i>' .
                            '</button>' .
                            '<button class="ui widget-toggle ' .
                                ($hidden ? 'orange' : 'teal') . ' icon compact mini right floated button">' .
                            '<i class="' . ($hidden ? 'eye slash' : 'eye') . ' icon"></i>' .
                            '</button>' .
                            $dropdown .
                            '<button class="ui widget-down teal icon compact mini right floated button">' .
                            '<i class="chevron down icon"></i>' .
                            '</button>' .
                            '<button class="ui widget-up teal icon compact mini right floated button">' .
                            '<i class="chevron up icon"></i>' .
                            '</button>' .
                            '<span class="widget-title">' . $intl(preg_replace('(__\d+$)', '', $k)) . '</span>' .
                            $views->render('webadmin::form', ['form' => $v]) .
                            '</div>';
                    },
                    $forms,
                    array_keys($forms)
                )
            ),
            'settings' => $views->render(
                'webadmin::form',
                ['form' => (clone $this->settingsForm)->populate($settings)]
            ),
            'permissions' => $views->render('webadmin::form', [
                'form' => (clone $this->permissionsForm)->populate($permissions)
            ]),
            'templates' => []
        ];
        foreach ($this->service->templates() as $template) {
            try {
                if ($data['template'] == $template['template']) {
                    $data['html']['templates'][$template['template']] = $views->render('webadmin::form', [
                        'form' => $this->service->template(
                            $template['base'],
                            $content[$template['template']] ?? [],
                            [ 'id' => $id, 'lang' => $lang ]
                        )
                    ]);
                }
            } catch (\Exception $e) {
                return $res->withStatus(403);
            }
        }
        return $res
            ->setBody(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function getSearch(Request $req, Response $res): Response
    {
        $query = $req->getQuery('str');
        $lang  = $req->getQuery('lang', 1, 'int');
        try {
            $ids = $this->service->searchParents($query, $lang);
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        return $res
            ->setBody(json_encode($ids, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postCreate(Request $req, Response $res): Response
    {

        try {
            $id = $this->service->createNode(
                (int)$req->getPost('id'),
                $req->getPost('pos'),
                $req->getPost('lang', 1, 'int'),
                $req->getPost('title')
            );
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        return $res
            ->setBody(
                json_encode([ 'id' => $id ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            )
            ->withHeader('Content-Type', 'application/json');
    }
    public function postMove(Request $req, Response $res): Response
    {
        try {
            $this->service->moveNode(
                (int)$req->getPost('id'),
                (int)$req->getPost('parent'),
                (int)$req->getPost('position')
            );
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        return $res
            ->setBody(json_encode([], JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postCopy(Request $req, Response $res): Response
    {
        try {
            $this->service->copyNode(
                (int)$req->getPost('id'),
                (int)$req->getPost('parent'),
                $req->getPost('position') !== null ? (int)$req->getPost('position') : null
            );
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        return $res
            ->setBody(json_encode([], JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postRemove(Request $req, Response $res): Response
    {
        try {
            $this->service->removeNode(
                (int)$req->getPost('id')
            );
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        return $res
            ->setBody(json_encode([], JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function getVersions(Request $req, Response $res): Response
    {
        try {
            $rslt = $this->service->nodeVersions(
                (int)$req->getQuery('id'),
                $req->getQuery('lang', 1, 'int')
            );
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        return $res
            ->setBody(json_encode($rslt, JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function getVersion(Request $req, Response $res, Views $views, Intl $intl): Response
    {
        try {
            $rslt = $this->service->nodeVersion(
                (int)$req->getQuery('id'),
                $req->getQuery('lang', 1, 'int'),
                (int)$req->getQuery('version')
            );
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        $settings = json_decode($rslt['settings'] ?? '', true) ?? [];
        $permissions = json_decode($rslt['permissions'] ?? '', true) ?? [];
        $content = json_decode($rslt['content'] ?? '', true) ?? [];
        $widgets = $content['widgets'] ?? [];
        unset($content['widgets']);
        $forms = $this->widgetsForms(
            $widgets,
            [ 'id' => (int)$req->getQuery('id'), 'lang' => $req->getQuery('lang', 1, 'int') ]
        );

        $zones = [];
        $templates = $this->service->templates();
        if (!isset($rslt['template'])) {
            $rslt['template'] = $templates[0]['template'];
        }
        foreach ($templates as $template) {
            if ($rslt['template'] == $template['template']) {
                $zones = json_decode($template['zones'] ?? '[]', true);
            }
        }
        if (!is_array($zones)) {
            $zones = [];
        }
        $zones[] = 'main';
        $zones = array_unique($zones);

        $rslt['html'] = [
            'widgets' => implode(
                '',
                array_map(
                    function (Form $v, int|string $k) use ($views, $intl, $zones) {
                        $k = (string)$k;
                        $hidden = (int)$v->getField('__hidden')->getValue();

                        $zone = ($v->getField('__zone')->getValue() ?? 'main') ?: 'main';
                        $dropdown = '<div ' .
                            'class="ui widget-zone teal icon compact mini right floated pointing dropdown button">' .
                            '<i class="th icon"></i>' .
                            '<div class="menu">';
                        foreach ($zones as $z) {
                            $dropdown .= '<div class="' . ($z === $zone ? 'selected' : '') . ' item">' . $z . '</div>';
                        }
                        $dropdown .= '</div></div>';

                        return '<div data-serialize="' . $k . '" ' .
                            ($k === 'widget_main' ? 'class="widget_main"' : '') . '>' .
                            '<button class="ui widget-remove red icon compact mini right floated button">' .
                            '<i class="close icon"></i>' .
                            '</button>' .
                            '<button class="ui widget-toggle ' .
                                ($hidden ? 'orange' : 'teal') . ' icon compact mini right floated button">' .
                            '<i class="' . ($hidden ? 'eye slash' : 'eye') . ' icon"></i>' .
                            '</button>' .
                            $dropdown .
                            '<button class="ui widget-down teal icon compact mini right floated button">' .
                            '<i class="chevron down icon"></i>' .
                            '</button>' .
                            '<button class="ui widget-up teal icon compact mini right floated button">' .
                            '<i class="chevron up icon"></i>' .
                            '</button>' .
                            '<span class="widget-title">' . $intl(preg_replace('(__\d+$)', '', $k)) . '</span>' .
                            $views->render('webadmin::form', ['form' => $v]) .
                            '</div>';
                    },
                    $forms,
                    array_keys($forms)
                )
            ),
            'settings' => $views->render('webadmin::form', [
                'form' => (clone $this->settingsForm)->populate($settings)
            ]),
            'permissions' => $views->render('webadmin::form', [
                'form' => (clone $this->permissionsForm)->populate($permissions)
            ]),
            'templates' => []
        ];
        foreach ($templates as $template) {
            if ($rslt['template'] == $template['template']) {
                $rslt['html']['templates'][$template['template']] = $views->render('webadmin::form', [
                    'form' => $this->service->template(
                        $template['base'],
                        $content[$template['template']] ?? [],
                        [ 'id' => (int)$req->getQuery('id'), 'lang' => $req->getQuery('lang', 1, 'int') ]
                    )
                ]);
            }
        }
        return $res
            ->setBody(json_encode($rslt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postRename(Request $req, Response $res): Response
    {
        try {
            $this->service->renameNode(
                (int)$req->getPost('id'),
                $req->getPost('lang', 1, 'int'),
                $req->getPost('title', '')
            );
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        return $res
            ->setBody(json_encode("OK", JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postToggle(Request $req, Response $res): Response
    {
        try {
            if ((int)$req->getPost('hidden', '0')) {
                $this->service->hideNode(
                    (int)$req->getPost('id'),
                    $req->getPost('lang', 1, 'int')
                );
            } else {
                $this->service->showNode(
                    (int)$req->getPost('id'),
                    $req->getPost('lang', 1, 'int')
                );
            }
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        return $res
            ->setBody(json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postSave(Request $req, Response $res): Response
    {
        try {
            $this->service->saveData(
                (int)$req->getPost('id'),
                $req->getPost('lang', 1, 'int'),
                $req->getPost(),
                (bool)$req->getPost('publish', 0, 'int'),
                (bool)$req->getPost('preview', 0, 'int')
            );
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        return $res
            ->setBody(json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function getPreview(Response $res, Url $url, Config $config): Response
    {
        $purl = '';
        $this->service->buildPreview();
        try {
            $rslt = $this->service->nodeVersions(
                (int)$url->getSegment(3),
                (int)$url->getSegment(2),
                true
            );
            $token = null;
            if (!(int)$url->getSegment(4)) {
                $purl = $rslt[0]['url'];
                $token = (new \vakata\jwt\JWT([
                    'id' => (int)$url->getSegment(3),
                    'version' => (int)$rslt[0]['version'],
                    'lang' => (int)$url->getSegment(2)
                ]))
                    ->setExpiration('+30 minutes')
                    ->sign($config->get('PUBLIC_SIGNATUREKEY'))
                    ->toString($config->get('PUBLIC_ENCRYPTIONKEY'));
            } else {
                foreach ($rslt as $v) {
                    if ((int)$url->getSegment(4) === $v['version']) {
                        $purl = $v['url'];
                        $token = (new \vakata\jwt\JWT([
                            'id' => (int)$url->getSegment(3),
                            'version' => (int)$v['version'],
                            'lang' => (int)$url->getSegment(2)
                        ]))
                            ->setExpiration('+30 minutes')
                            ->sign($config->get('PUBLIC_SIGNATUREKEY'))
                            ->toString($config->get('PUBLIC_ENCRYPTIONKEY'));
                        break;
                    }
                }
            }
            if (!$token) {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        $prefix = $config->getString('PUBLIC_URL') ?
            $config->getString('PUBLIC_URL') :
            'https://' . $this->service->domain();
        $prefix = rtrim($prefix, '/');
        return $res->withStatus(303)->withHeader('Location', $prefix . '/' . trim($purl, '/*') . '?preview=' . $token);
    }

    public function postRedraw(Request $req, Response $res, Views $views, Intl $intl): Response
    {
        $post = [];
        foreach ($req->getPost() as $k => $v) {
            $post[(string)$k] = $v && $v[0] === '{' ? (json_decode($v, true) ?? []) : $v;
        }
        $id   = $req->getPost('id', 0, 'int');
        $lang = $req->getPost('lang', 1, 'int');
        try {
            $node = $this->service->getNode($id, $lang);
        } catch (PagesException $e) {
            return $res->withStatus(403);
        } catch (\Exception $e) {
            return $res
                ->withStatus(400)
                ->withHeader(
                    'X-Log',
                    'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
                );
        }
        $data = count($node->data) ? $node->data : [ 'id' => $id, 'lang' => $lang, 'content' => '' ];
        $settings = json_decode($data['settings'] ?? '', true) ?? [];
        $permissions = json_decode($data['permissions'] ?? '', true) ?? [];
        $content = json_decode($data['content'] ?? '', true) ?? [];
        $widgets = $content['widgets'] ?? [];
        unset($content['widgets']);
        $data = [
            'settings' => $views->render('webadmin::form', [
                'form' => (clone $this->settingsForm)
                    ->populate(array_merge($settings, $post['settings'] ?? []))
            ]),
            'permissions' => $views->render('webadmin::form', [
                'form' => (clone $this->permissionsForm)
                    ->populate(array_merge($permissions, $post['permissions'] ?? []))
            ]),
        ];

        $zones = [];
        foreach ($this->service->templates() as $template) {
            if ($post['template'] == $template['template']) {
                $data[$template['template']] = $views->render('webadmin::form', [
                    'form' => $this->service->template(
                        $template['base'],
                        array_merge($content[$template['template']] ?? [], $post[$template['template']] ?? []),
                        [ 'id' => $id, 'lang' => $lang ]
                    )
                ]);
                $zones = json_decode($template['zones'] ?? '[]', true);
            }
        }
        if (!is_array($zones)) {
            $zones = [];
        }
        $zones[] = 'main';
        $zones = array_unique($zones);

        $newWidgets = [];
        $w = $this->service->widgets();
        foreach ($post as $k => $v) {
            foreach ($w as $vv) {
                /** @psalm-suppress RedundantCast */
                if (strpos((string)$k, $vv) === 0) {
                    $newWidgets[$k] = $v;
                }
            }
        }
        $forms = $this->widgetsForms(
            array_merge($widgets, $newWidgets),
            [ 'id' => $id, 'lang' => $lang ]
        );
        $data = $data +
            array_combine(
                array_keys($forms),
                array_map(
                    function (Form $v, int|string $k) use ($views, $intl, $zones) {
                        $k = (string)$k;
                        $hidden = (int)$v->getField('__hidden')->getValue();

                        $zone = ($v->getField('__zone')->getValue() ?? 'main') ?: 'main';
                        $dropdown = '<div ' .
                            'class="ui widget-zone teal icon compact mini right floated pointing dropdown button">' .
                            '<i class="th icon"></i>' .
                            '<div class="menu">';
                        foreach ($zones as $z) {
                            $dropdown .= '<div class="' . ($z === $zone ? 'selected' : '') . ' item">' . $z . '</div>';
                        }
                        $dropdown .= '</div></div>';

                        return '' .
                            '<button class="ui widget-remove red icon compact mini right floated button">' .
                            '<i class="close icon"></i>' .
                            '</button>' .
                            '<button class="ui widget-toggle ' .
                                ($hidden ? 'orange' : 'teal') . ' icon compact mini right floated button">' .
                            '<i class="' . ($hidden ? 'eye slash' : 'eye') . ' icon"></i>' .
                            '</button>' .
                            $dropdown .
                            '<button class="ui widget-down teal icon compact mini right floated button">' .
                            '<i class="chevron down icon"></i>' .
                            '</button>' .
                            '<button class="ui widget-up teal icon compact mini right floated button">' .
                            '<i class="chevron up icon"></i>' .
                            '</button>' .
                            '<span class="widget-title">' . $intl(preg_replace('(__\d+$)', '', $k)) . '</span>' .
                            $views->render('webadmin::form', ['form' => $v]);
                    },
                    $forms,
                    array_keys($forms)
                )
            );
        return $res
            ->setBody(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,Form>
     */
    public function widgetsForms(array $data = [], array $context = []): array
    {
        $temp = [];
        foreach ($data as $k => $v) {
            if ($k === 'widget_main') {
                $temp[$k] = (new Form())
                    ->setLayout(['widget_main'])
                    ->addField(
                        new Field('hidden', ['name' => '__hidden', 'value' => $data['widget_main']['__hidden'] ?? '0'])
                    )
                    ->addField(new Field('hidden', ['name' => '__zone', 'value' => 'main' ]));
                continue;
            }
            $name = preg_replace('(^widget_)', '', $k);
            $name = explode('__', $name ?? '', 3);
            $v = is_array($v) ? $v : (json_decode($v, true) ?? []);
            $temp[$k] = $this->service->widget((string)$name[0], $v, $context)
                ->addField(new Field('hidden', ['name' => '__hidden', 'value' => $v['__hidden'] ?? '0']))
                ->addField(new Field('hidden', ['name' => '__zone', 'value' => $v['__zone'] ?? 'main']));
        }
        if (!isset($temp['widget_main'])) {
            $temp['widget_main'] = (new Form())
                ->setLayout(['widget_main'])
                ->addField(
                    new Field('hidden', ['name' => '__hidden', 'value' => $data['widget_main']['__hidden'] ?? '0'])
                )
                ->addField(new Field('hidden', ['name' => '__zone', 'value' => 'main' ]));
        }
        return $temp;
    }
}
