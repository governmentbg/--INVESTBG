<?php

declare(strict_types=1);

namespace zni\modules\maintenanceReports;

use RuntimeException;
use vakata\config\Config;
use vakata\http\Request;
use vakata\http\Response;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDController;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDNotFoundException;
use zni\clients\RegixClient;

/**
 * @extends CRUDController<\schema\MaintenanceReportsEntity,MaintenanceReportService>
 */
class MaintenanceReportController extends CRUDController
{
    public function getRead(Request $request): Response
    {
        $this->service->journal(
            'Преглед на справка за поддържане на заетост',
            'info',
            (int)$request->getUrl()->getSegment(2)
        );
        return parent::getRead($request);
    }

    public function getCreate(Request $request): Response
    {
        try {
            if (!$this->module->canCreate()) {
                throw new CRUDNotFoundException('crud.create.notallowed');
            }
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        $form = $this->forms->base();

        $referer = parse_url($request->getHeaderLine('Referer'), PHP_URL_QUERY);
        $referer = $referer ? Request::fixedQueryParams($referer) : [];
        $multiTypes = [ 'checkboxes', 'files', 'images', 'multipleselect', 'tags', 'tree' ];
        $invalidTypes = [ 'comments', 'hidden', 'password' ];
        foreach ($referer as $k => $v) {
            $type = $form->hasField((string)$k) ? $form->getField((string)$k)->getType() : null;
            if (!$type || strpos((string)$k, '.') !== false || in_array($type, $invalidTypes)) {
                unset($referer[$k]);
                continue;
            }
            if (is_array($v) && !in_array($type, $multiTypes)) {
                $referer[$k] = array_values($v)[0] ?? '';
            }
        }

        $form = $this->forms->create($this->session->del($this->moduleName . '.create') ?? $referer);

        return (new Response())->setBody(
            $this->render(
                'create',
                [
                    'form'       => $form,
                    'title'      => $this->moduleName . '.titles.create',
                    'moduleName' => $this->moduleName,
                    'icon'       => 'plus',
                    'canCreate'  => $this->service->hasApproveReport((int)$form->getField('contract')->getValue('')),
                    'breadcrumb' => $this->moduleName . '.breadcrumb.create',
                    'back'       => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }

    public function postUpdate(Request $request): Response
    {
        try {
            if (!$this->module->canUpdate()) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
            $data = $request->getPost();
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            $validator = $this->forms->update($entity, $data)->getValidator();

            if ($this->service->isInvAdmin() && isset($data['submit_report'])) {
                $validator->required('pdf_sign', $this->intl->get('signed pdf is required'));
            }

            $errors = $validator->run($data);
            if (count($errors)) {
                foreach ($errors as $k => $v) {
                    if (!$v['message']) {
                        $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                    }
                }
                throw (new CRUDException("validation", 400))->setErrors($errors);
            }
            $entity = $this->service->update($this->service->id($entity), $data);
        } catch (CRUDException $e) {
            $this->session->set($this->moduleName . '.update', $request->getPost());
            $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
            return $this->exceptionResponse($request, $e);
        }
        $this->session->del($this->moduleName . '.update');
        $this->session->set('success', $this->moduleName . '.messages.update');
        $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
        return (new Response(303))->withHeader(
            'Location',
            (int)$request->getPost('redirect_to_id') ?
                $request->getUrl()->linkTo($this->module->getSlug(), $this->service->id($entity)) :
                $request->getUrl()->linkTo($this->session->get($this->moduleName  . '.index', $this->module->getSlug()))
        );
    }

    public function postCreate(Request $request): Response
    {
        try {
            if (!$this->module->canCreate()) {
                throw new CRUDNotFoundException('crud.create.notallowed');
            }
            $data = $request->getPost();
            if (!$this->service->hasApproveReport((int)$data['contract'])) {
                throw new CRUDException('maintenancereports.missing_approved_empl_report');
            }

            $errors = $this->forms->create()->getValidator()->run($data);
            if (count($errors)) {
                foreach ($errors as $k => $v) {
                    if (!$v['message']) {
                        $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                    }
                }
                throw (new CRUDException("validation", 400))->setErrors($errors);
            }
            $entity = $this->service->create($data);
        } catch (CRUDException $e) {
            $this->session->set($this->moduleName . '.create', $request->getPost());
            $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
            return $this->exceptionResponse($request, $e);
        }
        $this->session->del($this->moduleName . '.create');
        $this->session->set('success', $this->moduleName . '.messages.created');
        $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
        return (new Response(303))->withHeader(
            'Location',
            (int)$request->getPost('redirect_to_id') ?
               $request->getUrl()->linkTo($this->module->getSlug(), $this->service->id($entity)) :
               $request->getUrl()->linkTo($this->session->get($this->moduleName  . '.index', $this->module->getSlug()))
        );
    }

    public function getUpdate(Request $request): Response
    {
        try {
            if (!$this->module->canUpdate()) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        $form = $this->forms->update($entity, $this->session->del($this->moduleName . '.update') ?? []);

        return (new Response())->setBody(
            $this->render(
                'update',
                [
                    'form' => $form,
                    'pkey' => $this->service->id($entity),
                    'entity' => $entity,
                    'title' => $this->moduleName . '.titles.update',
                    'name' => $this->service->name($entity),
                    'moduleSlug' => $this->module->getSlug(),
                    'correctionAttempt' => $this->service->getCorrectionAttempt(),
                    'icon' => 'pencil',
                    'isInvAdmin' => $this->service->isInvAdmin(),
                    'isMir' => $this->service->isMIR(),
                    'isInv' => $this->service->isInv(),
                    'breadcrumb' => $this->moduleName . '.breadcrumb.update',
                    'back' => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }

    public function getCheck(Request $request): Response
    {
        try {
            if (!$this->module->canUpdate()) {
                throw new CRUDNotFoundException($this->intl->get('common.error.notallowed'));
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return (new Response())
                ->setContentTypeByExtension('json')
                ->setBody(json_encode([
                    'error' => 1,
                    'title' => $this->intl->get('maintenancereports.title.check_persons'),
                    'message' => $e->getMessage()
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '');
        }

        $persons = $this->service->getPersonsByMaintenanceReport($entity->mr, true);

        $result = [];

        foreach ($persons as $person) {
            if ($person['identifirer_type'] == 3) {
                $result[] = [
                    'person' => $person['empl_person'],
                    'message' => $this->intl->get('maintenancereports.cannot_check_person')
                ];
            } else {
                $type = (int)$person['identifirer_type'] === 1 ? 'EGN' : 'LNCH';

                $result[] = $this->service->getEETZ(
                    $person['identifirer'],
                    $type,
                    $entity->contracts->companies->id,
                    $person['employee_id'],
                    $person['workplace_empl']
                );
            }
        }

        $this->service->update($entity->mr, ['last_sync' => date('Y-m-d')]);

        return (new Response())
            ->setContentTypeByExtension('json')
            ->setBody(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '');
    }

    public function getDownloadReport(Request $request, User $user): Response
    {
        $entityId = (int)$request->getUrl()->getSegment(2);
        $entity = $this->service->read($entityId);

        $personData = $this->service->getPersonsByMaintenanceReport($entity->mr);

        $contract = $entity->contracts->toArray();

        $totalSalaries = 0;
        $totalSocialCosts = 0;

        foreach ($personData['positions'] as $position) {
            foreach ($position['persons'] as $person) {
                $totalSalaries += (float)($person['salary_amount'] ?? 0);
                $totalSocialCosts += (float)($person['refund_sum'] ?? 0);
            }
        }

        $submittedBy = $user->getData()['name'];

        $data = [
            'contract' => $contract,
            'company'  => $entity->contracts->companies->toArray(),
            'report'   => $entity->toArray(),
            'submitted_by' => trim($submittedBy),
            'current_date' => date('d.m.Y'),
            'jobs_count' => count($personData['positions']),
            'total_salaries_eur' => number_format($totalSalaries, 2, '.', ''),
            'total_social_costs_eur' => number_format($totalSocialCosts, 2, '.', ''),
        ];

        ob_start();
        require __DIR__ . '/views/sendReportPDF.php';
        $html = ob_get_clean();

        $pdf = new \TCPDF();
        $pdf->setCreator('e-INVESTBG');
        $pdf->setAuthor('e-INVESTBG');
        $pdf->setTitle('Справка за поддържане на заетост');
        $pdf->setMargins(10, 10, 10);
        $pdf->setAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->setFont('dejavusans', '', 10);
        $pdf->writeHTML($html ?: "", true, false, true, false, '');

        $filename = 'report_' . ($data['report']['mr'] ?? '') . '-'
            . ($data['report']['date_from'] ?? '') . '-' . ($data['report']['date_to'] ?? '') . '.pdf';
        $content = $pdf->Output($filename, 'S');

        return (new Response())
            ->withAddedHeader('Content-Type', 'application/pdf')
            ->withAddedHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }
}
