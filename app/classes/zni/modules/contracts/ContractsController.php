<?php

declare(strict_types=1);

namespace zni\modules\contracts;

use zni\enums\ReportStatus;
use webadmin\components\html\Form;
use vakata\http\Request as Request;
use webadmin\components\html\Field;
use vakata\http\Response as Response;
use zni\modules\contracts\ContractsService;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDNotFoundException;

/**
 * @extends CRUDController<\schema\ContractsEntity,ContractsService>
 */
class ContractsController extends CRUDController
{
    public function getRead(Request $request): Response
    {
        $this->service->journal('Преглед на договор', 'info', (int)$request->getUrl()->getSegment(2));
        return parent::getRead($request);
    }
    public function postCheckEIK(Request $request): Response
    {
        $eik = $request->getPost('bulstat');

        if (!$eik) {
            return new Response(200, (string) json_encode(['error' => 'Няма въведен ЕИК']));
        }

        $data = $this->service->checkEIK($eik);

        if (!$data) {
            return new Response(200, (string) json_encode(['error' => 'Няма данни в търговския регистър']));
        }

        $regionData = $this->service->getMunicipalitiesAndRegionByCity((int)$data['city']);

        if ($regionData && is_array($regionData)) {
            $data['location'] = [
                'region'       => [
                    'id'   => $regionData['region']['id'] ?? null,
                    'name' => $regionData['region']['name'] ?? ''
                ],
                'municipality' => [
                    'id'   => $regionData['municipality']['id'] ?? null,
                    'name' => $regionData['municipality']['name'] ?? ''
                ],
                'city'         => [
                    'id'   => $regionData['city']['id'] ?? null,
                    'name' => $regionData['city']['name'] ?? ''
                ],
            ];
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return new Response(500, 'Грешка при сериализиране на данните');
        }

        return new Response(200, $json);
    }
    public function getCopyContract(Request $request): Response
    {
        $intl = $this->intl;
        $service = $this->service;

        if (!$this->module->canCreate()) {
            throw new CRUDException('crud.create.notallowed');
        }

        try {
            $full = $this->service->getContractFullInfo(
                (int)$request->getUrl()->getSegment(index: 2)
            );

            $contract   = $full['contract'] ?? [];
            $report     = $full['report'] ?? null;
            $workplaces = $full['workplaces'] ?? [];
            $kid = $full['kid'] ?? [];

            $formFactory = require __DIR__ . '/views/contractForm.php';

            $form = $formFactory($contract, $report, $workplaces, $kid, $intl, $service);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        return (new Response())->setBody(
            $this->render('copyContract', [
                'form' => $form,
                'back' => $request->getUrl()->linkTo(
                    $this->session->get($this->moduleName . '.index', $this->module->getSlug())
                )
            ])
        );
    }
    public function postCopyContract(Request $request): Response
    {
        try {
            $parsedBody = (array) ($request->getParsedBody() ?? []);
            $this->service->createCopyContract($parsedBody);
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        return (new Response(303))->withHeader('Location', $request->getUrl()->linkTo());
    }
}
