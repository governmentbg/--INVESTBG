<?php

declare(strict_types=1);

namespace webadmin\modules\administration\mail;

use DateTime;
use vakata\http\Uri as Url;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\views\Views;

class MailController
{
    protected MailService $service;

    public function __construct(MailService $service)
    {
        $this->service = $service;
    }
    public function getIndex(Request $req, Response $res, Views $views): Response
    {
        $views->addFolder('mail', __DIR__ . '/views');

        $date = DateTime::createFromFormat('d.m.Y', $req->getQuery('date', date('d.m.Y')));
        if (!$date) {
            $date = new DateTime();
        }

        return $res->setBody(
            $views->render('mail::index', [
                'date' => $date->format('d.m.Y'),
                'mail' => $this->service->list($date)
            ])
        );
    }
    public function getDownload(Response $res, Url $url): Response
    {
        $mail = $this->service->mail($url->getSegment(2));
        if (!$mail) {
            return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
        }
        return $res
            ->setBody($mail)
            ->withHeader('Content-Type', 'message/rfc822')
            ->withHeader('Content-Disposition', 'attachment; filename=dump.eml')
            ->withHeader('Content-Length', (string)strlen($mail));
    }
}
