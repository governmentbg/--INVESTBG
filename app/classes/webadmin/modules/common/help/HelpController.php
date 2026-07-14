<?php

declare(strict_types=1);

namespace webadmin\modules\common\help;

use vakata\http\Request as Request;
use vakata\http\Response as Response;

class HelpController
{
    private HelpService $service;

    public function __construct(HelpService $service)
    {
        $this->service = $service;
    }
    public function postIndex(Request $req, Response $res): Response
    {
        try {
            $this->service->set($req->getPost('url'), $req->getPost('helper_content'));
            return $res->withStatus(200);
        } catch (\Exception $e) {
            return $res->withStatus(500);
        }
    }
}
