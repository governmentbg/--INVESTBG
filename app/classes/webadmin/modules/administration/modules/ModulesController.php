<?php

declare(strict_types=1);

namespace webadmin\modules\administration\modules;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\http\Uri as Url;
use webadmin\components\html\Form as Form;
use webadmin\components\html\Field as Field;
use vakata\views\Views;

class ModulesController
{
    protected ModulesService $service;

    public function __construct(ModulesService $service)
    {
        $this->service = $service;
    }
    public function getIndex(Response $res, Views $views): Response
    {
        $views->addFolder('modules', __DIR__ . '/views');
        $modules = $this->service->getModules();

        $form = new Form();
        $form->addField(
            new Field(
                'json',
                [
                    'name' => 'modules',
                    'value' => $modules
                ],
                [
                    'add'     => false,
                    'delete'  => false,
                    'reorder' => true,
                    'label'   => '',
                    'form'    => (new Form())
                        ->addClass('compact')
                        ->addField(
                            new Field(
                                'text',
                                [ 'name' => 'name', 'disabled' => true ],
                                [ 'label' => 'modules.columns.name' ]
                            )
                        )
                        ->addField(
                            new Field(
                                'text',
                                [ 'name' => 'slug' ],
                                [ 'label' => 'modules.columns.slug' ]
                            )
                        )
                        ->addField(
                            new Field(
                                'text',
                                [ 'name' => 'classname' ],
                                [ 'label' => 'modules.columns.classname' ]
                            )
                        )
                        ->addField(
                            new Field(
                                'checkbox',
                                [ 'name' => 'loaded' ],
                                [ 'nobr' => true, 'label' => 'modules.columns.loaded' ]
                            )
                        )
                        ->setLayout([
                            [
                                'name:2',
                                'slug:2',
                                'classname:4',
                                'loaded:2'
                            ]
                        ])
                ]
            )
        );
        return $res->setBody(
            $views->render('modules::index', [
                'form' => $form->setLayout([
                    'modules.columns.title',
                    ['modules']
                ])
            ])
        );
    }
    public function postIndex(Request $req, Response $res, Url $url): Response
    {
        $this->service->setModules(json_decode($req->getPost('modules'), true) ?? []);
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
}
