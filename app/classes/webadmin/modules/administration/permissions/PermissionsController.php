<?php

declare(strict_types=1);

namespace webadmin\modules\administration\permissions;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\http\Uri as Url;
use webadmin\components\html\Form as Form;
use webadmin\components\html\Field as Field;
use vakata\views\Views;
use vakata\intl\Intl;

class PermissionsController
{
    protected PermissionsService $service;

    public function __construct(PermissionsService $service)
    {
        $this->service = $service;
    }
    public function getIndex(Response $res, Views $views, Intl $intl): Response
    {
        $views->addFolder('permissions', __DIR__ . '/views');
        $methods = $this->service->getAvailablePermissions();
        $permissions = $this->service->getStoredPermissions();

        $form = new Form();
        $layout = [['t:' . $intl('permissions.module') . ':4', 't:' . $intl('permissions.additional') . ':12']];
        foreach ($methods as $name => $values) {
            if ($name !== 'dashboard') {
                $form->addField(
                    new Field(
                        'checkbox',
                        [
                            'name' => 'modules[' . $name . ']',
                            'value' => in_array($name, $permissions)
                        ],
                        [
                            'nobr'   => true,
                            'label'  => $name . ".title", //'permissions.module'//$name . ".title"
                        ]
                    )
                );
            }
            $values = array_filter($values, function (string $v) use ($name) {
                return $v !== $name;
            });
            /** @phpstan-ignore-next-line */
            if (count($values)) {
                if ($name !== 'dashboard') {
                    $layout[] = ['modules[' . $name . ']:4', 'permissions[' . $name . ']:12'];
                } else {
                    $layout[] = ['t:b:' . $intl($name . ".title") . ':4', 'permissions[' . $name . ']:12'];
                }
                $translated = [];
                /** @phpstan-ignore-next-line */
                foreach ($values as $k => $v) {
                    if (ctype_digit((string)$k)) {
                        $translated[$v] = $intl('permission.' . $v);
                    } else {
                        $translated[$k] = $intl($v);
                    }
                }
                $form->addField(
                    new Field(
                        'checkboxes',
                        [
                            'name' => 'permissions[' . $name . ']',
                            'value' => $permissions
                        ],
                        [
                            'reset'  => false,
                            'inline' => true,
                            'nolabel' => true,
                            'grid'   => 4,
                            'values' => $translated,
                            'label'  => 'permissions.additional'//$name . ".title"
                        ]
                    )
                );
            } else {
                $layout[] = ['modules[' . $name . ']'];
            }
        }
        $form->setLayout($layout);
        return $res->setBody(
            $views->render('permissions::index', [
                'form' => $form
            ])
        );
    }
    public function postIndex(Request $req, Response $res, Url $url): Response
    {
        $permissions = [];
        $temp = $req->getPost('modules', []);
        if (is_array($temp)) {
            foreach ($temp as $module => $enabled) {
                if ((int)$enabled) {
                    $permissions[] = $module;
                }
            }
        }
        $temp = $req->getPost('permissions', []);
        if (is_array($temp)) {
            foreach ($temp as $module => $perms) {
                if (is_array($perms)) {
                    foreach ($perms as $permission) {
                        $permissions[] = $permission;
                    }
                }
            }
        }
        $this->service->setPermissions($permissions);
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
}
