<?php

declare(strict_types=1);

namespace webadmin\modules\site\templates;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDException;

/**
 * @extends CRUDController<\schema\TemplatesEntity,TemplatesService>
 */
class TemplatesController extends CRUDController
{
    public function getCreate(Request $request): Response
    {
        try {
            if (!$this->module->canCreate()) {
                throw new CRUDException('crud.create.notallowed');
            }
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        $form = $this->forms->base();

        $referer = parse_url($request->getHeaderLine('Referer'), PHP_URL_QUERY);
        $referer = $referer ? Request::fixedQueryParams($referer) : [];
        $multiTypes = ['checkboxes', 'files', 'images', 'multipleselect', 'tags', 'tree'];
        $invalidTypes = ['comments', 'hidden', 'password'];
        foreach ($referer as $k => $v) {
            $type = $form->hasField((string)$k) ? $form->getField((string)$k)->getType() : null;
            if (!$type || strpos((string)$k, '.') !== false || in_array($type, $invalidTypes)) {
                unset($referer[$k]);
                continue;
            }
            if (is_array($v) && !in_array($type, $multiTypes)) {
                $referer[$k] = array_values($v)[0] ?? '';
            }
        }

        $form = $this->forms->create($this->session->del($this->moduleName . '.create') ?? $referer);
        $data = $form->getContext('data');
        $templates = $this->service->baseTemplates();
        $zones = $this->service->template($data['base'] ?? array_keys($templates)[0])->getZones();
        $zones[] = 'main';
        $zones = array_unique($zones);
        $forms = $this->widgetForms([], $zones);
        $form->getField('widgets')->setOption(
            'form',
            implode(
                '',
                array_map(
                    function ($v, $k) {
                        return '<div data-serialize="' . $k . '" ' .
                            ($k === 'widget_main' ? 'class="widget_main"' : '') . '>' . $v . '</div>';
                    },
                    $forms,
                    array_keys($forms)
                )
            )
        );

        return (new Response())->setBody(
            $this->render(
                'create',
                [
                    'form'       => $form,
                    'title'      => $this->moduleName . '.titles.create',
                    'icon'       => 'plus',
                    'breadcrumb' => $this->moduleName . '.breadcrumb.create',
                    'back'       => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }
    public function getUpdate(Request $request): Response
    {
        try {
            if (!$this->module->canUpdate()) {
                throw new CRUDException('crud.update.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        $form = $this->forms->update($entity, $this->session->del($this->moduleName . '.update') ?? []);
        $templates = $this->service->baseTemplates();
        $zones = $this->service->template((string)($entity->base ?? array_keys($templates)[0]))->getZones();
        $zones[] = 'main';
        $zones = array_unique($zones);
        $forms = $this->widgetForms(json_decode($entity->widgets ?? '[]', true) ?? [], $zones);
        $form->getField('widgets')->setOption(
            'form',
            implode(
                '',
                array_map(
                    function ($v, $k) {
                        return '<div data-serialize="' . $k . '" ' .
                            ($k === 'widget_main' ? 'class="widget_main"' : '') . '>' . $v . '</div>';
                    },
                    $forms,
                    array_keys($forms)
                )
            )
        );

        return (new Response())->setBody(
            $this->render(
                'update',
                [
                    'form' => $form,
                    'pkey' => $this->service->id($entity),
                    'entity' => $entity,
                    'title' => $this->moduleName . '.titles.update',
                    'name' => $this->service->name($entity),
                    'icon' => 'pencil',
                    'breadcrumb' => $this->moduleName . '.breadcrumb.update',
                    'back' => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }
    public function postRedraw(Request $request): Response
    {
        $data = [];
        $post = $request->getPost();
        foreach ($post as $k => $v) {
            $post[$k] = $v && $v[0] === '{' ? (json_decode($v, true) ?? []) : $v;
        }
        /** @psalm-suppress all */
        $zones = $this->service->template((string)$post['base'])->getZones();
        $zones[] = 'main';
        $zones = array_unique($zones);
        $entity = null;
        if ($request->getUrl()->getSegment(2)) {
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        }
        $widgets = json_decode($entity->widgets ?? '[]', true) ?? [];
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
        $data = $this->widgetForms(
            array_merge($widgets, $newWidgets, ['widget_main' => $post['widget_main']]),
            $zones
        );
        $form = (int)$request->getUrl()->getSegment(2) ?
            $this->forms->update($this->service->read($request->getUrl()->getSegment(2)), $request->getPost()) :
            $this->forms->create($request->getPost());
        $data['zones'] = $this->views->render('webadmin::field/checkboxes', [ 'field' => $form->getField('zones') ]);
        $data['zones'] = preg_replace('(</div>$)', '', explode('data-serialize="zones">', $data['zones'])[1]);
        return (new Response())
            ->setContentTypeByExtension('json')
            ->setBody(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
    protected function widgetForms(array $data, array $zones): array
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
            $name = preg_replace('(^widget_)', '', (string)$k);
            $name = explode('__', $name ?? '', 3);
            $v = is_array($v) ? $v : (json_decode($v, true) ?? []);
            $temp[$k] = $this->service->widget((string)$name[0], $v)
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
        return array_combine(
            array_keys($temp),
            array_map(
                function (Form $v, int|string $k) use ($zones) {
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
                        '<span class="widget-title">' .
                            $this->intl->get(preg_replace('(__\d+$)', '', $k) ?? '') .
                        '</span>' .
                        $this->views->render('webadmin::form', ['form' => $v]);
                },
                $temp,
                array_keys($temp)
            )
        );
    }
}
