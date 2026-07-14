<?php

declare(strict_types=1);

namespace webadmin\modules\administration\maildb;

use vakata\http\Request;
use vakata\http\Uri as Url;
use vakata\http\Response as Response;
use webadmin\modules\common\crud\CRUDController;

/**
 * @extends CRUDController<\schema\MailsEntity,MailDBService>
 */
class MailDBController extends CRUDController
{
    public function getDownload(Request $req): Response
    {
        $mail = $this->service->read($req->getUrl()->getSegment(2));
        return (new Response())
            ->setBody($mail->content ?? '')
            ->withHeader('Content-Type', 'message/rfc822')
            ->withHeader('Content-Disposition', 'attachment; filename=dump.eml')
            ->withHeader('Content-Length', (string)strlen($mail->content ?? ''));
    }
}
