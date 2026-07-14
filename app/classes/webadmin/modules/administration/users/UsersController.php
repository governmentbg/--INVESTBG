<?php

declare(strict_types=1);

namespace webadmin\modules\administration\users;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDException;
use vakata\jwt\JWT;

/**
 * @extends CRUDController<\schema\UsersEntity,UsersService>
 */
class UsersController extends CRUDController
{
    public function getRead(Request $request): Response
    {
        $this->service->journal('Преглед на потребител', 'info', (int)$request->getUrl()->getSegment(2));
        return parent::getRead($request);
    }
    public function getImpersonate(Request $request): Response
    {
        try {
            if (!$this->module->canUpdate()) {
                throw new CRUDException('crud.update.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        return (new Response())->setBody(
            $this->render('impersonate', [
                'user' => $entity,
                'back' => $request->getUrl()->linkTo(
                    $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                )
            ])
        );
    }
    public function postImpersonate(Request $request, JWT $token): Response
    {
        try {
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            $token->setClaim('impersonate', $entity->usr);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        return (new Response(303))->withHeader('Location', $request->getUrl()->linkTo());
    }
    public function getKick(Request $request): Response
    {
        try {
            if (!$this->module->canUpdate()) {
                throw new CRUDException('crud.update.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        return (new Response())->setBody(
            $this->render('kick', [
                'user' => $entity,
                'back' => $request->getUrl()->linkTo(
                    $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                )
            ])
        );
    }
    public function postKick(Request $request): Response
    {
        try {
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            $this->service->kick((string)$entity->usr);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        return (new Response(303))->withHeader(
            'Location',
            $request->getUrl()->linkTo($this->session->get($this->moduleName  . '.index', $this->module->getSlug()))
        );
    }
}
