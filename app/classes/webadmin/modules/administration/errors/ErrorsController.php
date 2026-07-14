<?php

declare(strict_types=1);

namespace webadmin\modules\administration\errors;

use DateTime;
use vakata\views\Views;
use vakata\config\Config;
use vakata\http\Request;
use vakata\http\Response;

class ErrorsController
{
    protected ErrorsService $service;

    public function __construct(ErrorsService $service, protected Config $config)
    {
        $this->service = $service;
    }

    public function getIndex(Request $req, Response $res, Views $views): Response
    {
        $views->addFolder('errors', __DIR__ . '/views');

        $source = (int)$req->getQuery('source');
        if (!$source) {
            $source = 3;
        }
        $date = DateTime::createFromFormat('d.m.Y', $req->getQuery('date', date('d.m.Y')));
        if (!$date) {
            $date = new DateTime();
        }

        return $res->setBody(
            $views->render('errors::index', [
                'date'       => $date->format('d.m.Y'),
                'source'     => $source,
                'sources'    => [
                    'errors.source.all',
                    'errors.source.admin',
                    'errors.source.public'
                ],
                'picker'     => $this->config->getString('STORAGE_LOG_PUBLIC') !== '',
                'errors'     => $this->service->list($date, ($source & 1) > 0, ($source & 2) > 0)
            ])
        );
    }
}
