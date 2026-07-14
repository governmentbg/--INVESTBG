<?php

declare(strict_types=1);

namespace webadmin\modules\administration\config;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\http\Uri as Url;
use webadmin\components\html\Form as Form;
use webadmin\components\html\Field as Field;
use vakata\views\Views;
use vakata\intl\Intl;

class ConfigController
{
    protected ConfigService $service;

    public function __construct(ConfigService $service)
    {
        $this->service = $service;
    }
    public function getIndex(Response $res, Views $views, Intl $intl): Response
    {
        $views->addFolder('config', __DIR__ . '/views');
        $config = $this->service->getConfig();

        $form = new Form();
        $layout = [];
        $layout[] = [
            'b:' . $intl('config.hvarname') . ':2',
            'b:' . $intl('config.hcurrent'),
            'b:' . $intl('config.hdatabase')
        ];
        $layout[] = '';
        foreach ($config as $k => $c) {
            $form->addField(
                new Field(
                    'checkbox',
                    [ 'name' => 'config[' . $k . '][override]', 'value' => $c['override'] ],
                    [ 'label' => $k, 'nobr' => true, 'notranslate' => true ]
                )
            );
            $form->addField(
                new Field(
                    'text',
                    [ 'name' => 'config[' . $k . '][value]', 'value' => $c['value'], 'disabled' => true ],
                    [  ]
                )
            );
            $form->addField(
                new Field(
                    'text',
                    [ 'name' => 'config[' . $k . '][db]', 'value' => $c['db'] ],
                    [ 'label' => '' ]
                )
            );
            $form->addField(
                new Field(
                    'text',
                    [ 'name' => 'config[' . $k . '][value]', 'value' => $c['value'] ],
                    [ 'label' => '' ]
                )
            );
            $layout[] = [ 'config[' . $k . '][override]:2', 'config[' . $k . '][value]', 'config[' . $k . '][db]' ];
            $layout[] = [ $intl('config.decription.' . $k) ];
        }
        return $res->setBody(
            $views->render('config::index', [
                'form' => $form->setLayout($layout),
                'writable' => $this->service->writable()
            ])
        );
    }
    public function postIndex(Request $req, Response $res, Url $url): Response
    {
        if (is_array($req->getPost('config'))) {
            $this->service->setConfig($req->getPost('config'));
        }
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
}
