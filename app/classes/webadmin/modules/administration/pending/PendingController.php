<?php

declare(strict_types=1);

namespace webadmin\modules\administration\pending;

use webadmin\components\html\Field;
use webadmin\components\html\Form;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDException;

/**
 * @extends CRUDController<\schema\UserPendingEntity,PendingService>
 */
class PendingController extends CRUDController
{
    public function getUser(Request $request): Response
    {
        try {
            if (!$this->service->isUserAdmin()) {
                throw new CRUDException('crud.update.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        $form = new Form();
        $form->setContext('type', 'user');
        $form->addField(
            new Field(
                'module',
                [ 'name' => 'user' ],
                [
                    'label' => $this->module->getName() . '.columns.user',
                    'url' => 'users',
                    'id' => 'usr',
                    'multiple' => false
                ]
            )
        );
        $form = $this->module->formCallback($form);

        return (new Response())->setBody(
            $this->render(
                'user',
                [
                    'form'       => $form,
                    'user'       => $entity,
                    'back'       => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }
    public function postUser(Request $request): Response
    {
        try {
            if (!$this->service->isUserAdmin()) {
                throw new CRUDException('crud.update.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            if ($request->getPost('user_add')) {
                $link = $this->service->existingUser($request->getPost('user', 0, 'int'), $entity);
            } else {
                $link = $this->service->newUser($entity);
            }
        } catch (CRUDException $e) {
            $this->session->set($this->moduleName . '.update', $request->getPost());
            $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
            return $this->exceptionResponse($request, $e);
        }
        return (new Response(303))->withHeader(
            'Location',
            $request->getUrl()->linkTo($link)
        );
    }
}
