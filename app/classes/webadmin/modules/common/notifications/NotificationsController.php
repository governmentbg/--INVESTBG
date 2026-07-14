<?php

declare(strict_types=1);

namespace webadmin\modules\common\notifications;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDException;

/**
 * @extends CRUDController<\schema\NotificationsEntity,NotificationsService>
 */
class NotificationsController extends CRUDController
{
    public function getCreate(Request $request): Response
    {
        if (!count($this->service->getAvailableRecipients())) {
            throw new \Exception('Not implemented', 404);
        }
        return parent::getCreate($request);
    }
    public function postCreate(Request $request): Response
    {
        if (!count($this->service->getAvailableRecipients())) {
            throw new \Exception('Not implemented', 404);
        }
        return parent::postCreate($request);
    }

    public function getRead(Request $request): Response
    {
        try {
            if (!$this->module->canRead()) {
                throw new CRUDException('crud.read.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        if ($request->getQuery('follow')) {
            if (strlen($entity->link)) {
                return (new Response(303))->withHeader(
                    'Location',
                    strpos($entity->link, '//') !== false ? $entity->link : $request->getUrl()->linkTo($entity->link)
                );
            } else {
                return (new Response(303))->withHeader(
                    'Location',
                    $request->getUrl()->linkTo($this->module->getSlug() . '/read/' . $entity->notification)
                );
            }
        }
        return parent::getRead($request);
    }
    public function postRead(Request $request): Response
    {
        try {
            if (!$this->module->canRead()) {
                throw new CRUDException('crud.read.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        try {
            $data = $request->getPost();
            $data['thread'] = $entity->thread ?? $entity->notification;
            $this->service->create($data);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        $this->session->del($this->module->getName() . '.update');
        $this->session->set('success', $this->module->getName() . '.messages.update');
        return (new Response())->withHeader('Location', (string)$request->getUri());
    }
    public function getAjax(Request $request): Response
    {
        $result = $this->service->getNotifications();
        $result = array_map(function (array $v) use ($request): array {
            $v['link'] = $request->getUrl()->linkTo('notifications/read/' . $v['notification'], ['follow' => '1']);
            try {
                $v['sent'] = date('d.m.Y H:i', strtotime($v['sent']) ?: 0);
            } catch (\Exception $e) {
                $v['sent'] = '';
            }
            return $v;
        }, $result);
        return (new Response())
            ->setContentTypeByExtension('json')
            ->setBody(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '');
    }
}
