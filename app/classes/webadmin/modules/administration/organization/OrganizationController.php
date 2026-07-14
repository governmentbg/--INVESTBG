<?php

declare(strict_types=1);

namespace webadmin\modules\administration\organization;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\views\Views;
use webadmin\components\html\Form;
use webadmin\components\html\Field;

class OrganizationController
{
    protected OrganizationService $service;

    public function __construct(OrganizationService $service)
    {
        $this->service = $service;
    }
    public function getIndex(Response $res, Views $views): Response
    {
        $views->addFolder('organization', __DIR__ . '/views');
        return $res->setBody(
            $views->render(
                'organization::index',
                [
                    'roots' => $this->service->getRoots()
                ]
            )
        );
    }
    public function getForm(Request $req, Response $res, Views $views): Response
    {
        try {
            $temp = $this->service->getNode(1, $req->getQuery('org', '1', 'int'));
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }
        $form = (new Form())
            ->addField(
                (new Field('json', ['name' => 'data']))
                    ->setOption('min', 1)
                    ->setOption(
                        'form',
                        (new Form())
                            ->addField(new Field('text', ['name' => 'key'], ['label' => 'organization.fields.key']))
                            ->addField(new Field('text', ['name' => 'value'], ['label' => 'organization.fields.value']))
                    )
            );
        return $res
            ->setBody(
                $views->render(
                    'webadmin::form',
                    ['form' => $form->populate(json_decode($temp->properties ?? '', true))]
                )
            );
    }
    public function postForm(Request $req, Response $res): Response
    {
        $data = $req->getPost();
        try {
            $this->service->getNode(1, $req->getQuery('org', '1', 'int'));
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }
        $this->service->setData(1, $req->getQuery('org', '1', 'int'), $data);
        return $res;
    }
    public function getNode(Response $res): Response
    {
        try {
            $temp = [ $this->service->getNode(1, 1) ];
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }
        $rslt = [];
        foreach ($temp as $node) {
            $rslt[] = [
                'id'        => $node->org,
                'text'      => $node->title,
                'children'  => $node->hasChildren(),
                'icon'      => $node->hasChildren() ? 'ui cubes icon' : 'ui cube icon',
                'data'      => []
            ];
        }
        return $res
            ->setBody(json_encode($rslt, JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postNode(Request $req, Response $res): Response
    {
        try {
            $temp = (int)$req->getPost('id') ?
                $this->service->getChildren($req->getPost('root', 0, 'int'), $req->getPost('id', 0, 'int')) :
                [ $this->service->getNode($req->getPost('root', 0, 'int'), $req->getPost('root', 0, 'int')) ];
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }
        $rslt = [];
        foreach ($temp as $node) {
            $rslt[] = [
                'id'        => $node->org,
                'text'      => $node->title,
                'children'  => $node->hasChildren(),
                'icon'      => $node->hasChildren() ? 'ui cubes icon' : 'ui cube icon',
                'data'      => []
            ];
        }
        return $res
            ->setBody(json_encode($rslt, JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postCreate(Request $req, Response $res): Response
    {
        try {
            $id = $this->service->createNode(
                $req->getPost('root', 0, 'int'),
                $req->getPost('id', 0, 'int'),
                $req->getPost('position') !== null ? (int)$req->getPost('position') : null
            );
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }
        return $res
            ->setBody(json_encode([ 'id' => $id ], JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postMove(Request $req, Response $res): Response
    {
        try {
            $this->service->moveNode(
                $req->getPost('root', 0, 'int'),
                $req->getPost('id', 0, 'int'),
                $req->getPost('parent', 0, 'int'),
                $req->getPost('position', 0, 'int')
            );
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }
        return $res
            ->setBody(json_encode([], JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postCopy(Request $req, Response $res): Response
    {
        try {
            $id = $this->service->copyNode(
                $req->getPost('root', 0, 'int'),
                $req->getPost('id', 0, 'int'),
                $req->getPost('parent', 0, 'int'),
                $req->getPost('position')
            );
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }
        return $res
            ->setBody(json_encode([ 'id' => $id ], JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postRemove(Request $req, Response $res): Response
    {
        $usr = $this->service->checkForUsers(
            $req->getPost('root', 0, 'int'),
            $req->getPost('id', 0, 'int')
        );
        if (count($usr)) {
            return $res
                ->setBody(json_encode($usr, JSON_THROW_ON_ERROR))
                ->withStatus(400);
        }
        try {
            $this->service->removeNode(
                $req->getPost('root', 0, 'int'),
                $req->getPost('id', 0, 'int')
            );
        } catch (\Exception $e) {
            return $res->withStatus(500);
        }
        return $res
            ->setBody(json_encode([], JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function postRename(Request $req, Response $res): Response
    {
        try {
            $this->service->renameNode(
                $req->getPost('root', 0, 'int'),
                $req->getPost('id', 0, 'int'),
                $req->getPost('title', '')
            );
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }
        return $res
            ->setBody(json_encode([], JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function getNodes(Request $req, Response $res): Response
    {
        $ids  = array_filter(explode(',', $req->getQuery('id')));
        try {
            $nodes = $this->service->getNodes($req->getQuery('root', 0, 'int'), $ids);
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }

        $rslt = [];
        foreach ($nodes as $id => $children) {
            $temp = [];
            foreach ($children as $node) {
                $temp[] = [
                    'id'        => $node->org,
                    'text'      => $node->title,
                    'children'  => $node->hasChildren(),
                    'icon'      => $node->hasChildren() ? 'ui cubes icon' : 'ui cube icon',
                    'data'      => []
                ];
            }
            $rslt[$id] = $temp;
        }
        return $res
            ->setBody(json_encode($rslt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
    public function getSearch(Request $req, Response $res): Response
    {
        $query = $req->getQuery('str');
        try {
            $ids = $this->service->searchParents($query);
        } catch (\Exception $e) {
            return $res->withStatus(400);
        }
        return $res
            ->setBody(json_encode($ids, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
            ->withHeader('Content-Type', 'application/json');
    }
}
