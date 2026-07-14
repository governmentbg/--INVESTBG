<?php

declare(strict_types=1);

namespace zni\modules\companies;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use webadmin\modules\common\crud\CRUDController;

/**
 * @extends CRUDController<\schema\CompaniesEntity,CompaniesService>
 */
class CompaniesController extends CRUDController
{
    public function postCheckEIK(Request $request): Response
    {
        $eik = $request->getPost('id');

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
}
