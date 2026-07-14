<?php

declare(strict_types=1);

namespace webadmin\modules\administration\uploads;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use webadmin\modules\common\crud\CRUDController;

/**
 * @extends CRUDController<\schema\UploadsEntity,UploadsService>
 */
class UploadsController extends CRUDController
{
    public function getDownload(Request $request): Response
    {
        $link = $this->service->getFileLink($request->getUrl()->getSegment(2));
        return (new Response(303))
            ->withHeader('Location', $request->getUrl()->get($link));
    }
}
