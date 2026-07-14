<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use vakata\http\Request as Request;
use vakata\http\Response as Response;
use webadmin\components\html\Form as Form;
use webadmin\components\html\Field as Field;
use vakata\views\Views;
use vakata\collection\Collection;
use base\components\files\Files;
use vakata\spreadsheet\Reader;
use vakata\intl\Intl;
use DateTime;
use vakata\config\Config;
use vakata\database\schema\Entity;
use vakata\session\Native;
use vakata\session\SessionInterface;
use vakata\spreadsheet\Writer;
use vakata\spreadsheet\writer\XLSXWriter;
use webadmin\modules\common\crud\CRUDModuleInterface;

/**
 * @template T of Entity
 * @template S of CRUDServiceInterface<T>
 * @SuppressWarnings("PHPMD.NumberOfChildren")
 */
class CRUDController
{
    /** @var CRUDModuleInterface<T,S> $module */
    protected CRUDModuleInterface $module;
    /** @var S $service */
    protected CRUDServiceInterface $service;
    /** @var CRUDFormsInterface<T> $forms */
    protected CRUDFormsInterface $forms;
    protected Views $views;
    protected Intl $intl;
    protected SessionInterface $session;
    protected string $moduleName;

    /**
     * @param CRUDModuleInterface<T,S> $module
     * @param S $service
     * @param CRUDFormsInterface<T> $forms
     * @param Views $views
     * @param Intl $intl
     */
    public function __construct(
        CRUDModuleInterface $module,
        CRUDServiceInterface $service,
        CRUDFormsInterface $forms,
        Views $views,
        Intl $intl,
        ?SessionInterface $session = null
    ) {
        $this->module     = $module;
        $this->service    = $service;
        $this->forms      = $forms;
        $this->views      = $views;
        $this->intl       = $intl;
        $this->session    = $session ?? new Native();
        $this->moduleName = $this->module->getName();
    }

    protected function render(string $name, array $params): string
    {
        if (!strpos($name, '::')) {
            if (
                $this->views->exists($this->moduleName  . '::' . $name)
            ) {
                $name = $this->moduleName  . '::' . $name;
            } else {
                $name = 'crud::' . $name;
            }
        }
        return $this->views->render($name, $params);
    }
    protected function exceptionResponse(Request $request, CRUDException $e): Response
    {
        $redirect = $request->getUrl()->__toString();
        $errors = Collection::from($e->getErrors())->pluck('message')->toArray();
        if ($e instanceof CRUDNotFoundException) {
            $errors[] = $this->moduleName  . '.messages.notfound';
            $redirect = $request->getUrl()->linkTo(
                $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
            );
        }
        if (!count($errors)) {
            $errors[] = $e->getMessage();
        }
        $this->session->set('error', $errors);
        return (new Response(303))->withHeader('Location', $redirect);
    }

    protected function normalizeParams(array $query): array
    {
        if (isset($query['l'])) {
            $query['l'] = max(0, min((int)$query['l'], 100));
            if (!(int)$query['l']) {
                unset($query['l']);
            }
        }
        $params = $query;
        if (!isset($params['p'])) {
            $params['p'] = 1;
        }
        if (!isset($params['l'])) {
            $params['l'] = 25;
        }
        if (isset($params['q']) && is_string($params['q']) && !strlen($params['q'])) {
            unset($params['q']);
        }
        return $params;
    }
    public function getIndex(Request $request): Response
    {
        $params = $this->normalizeParams($request->getQuery());
        $entities = $this->service->list($params);
        $table = $this->forms->listing($entities, $params, count($entities));
        if (!$request->isAjax()) {
            $this->session->set($this->moduleName  . '.index', $request->getUrl()->self());
        }
        return (new Response())->setBody(
            $this->render(
                'index',
                [
                    'module'     => $this->module,
                    'params'     => $params,
                    'table'      => $table,
                    'created'    => $this->session->get('success') === $this->moduleName . '.messages.created',
                    'updated'    => $this->session->get('success') === $this->moduleName . '.messages.update',
                ]
            )
        );
    }
    public function postIndex(Request $request, Config $config): Response
    {
        $query = $request->getQuery();
        if (isset($query['l']) && $query['l'] === 'all') {
            unset($query['l']);
        }
        $params = $query;
        $data = $request->getPost();
        if (!isset($data['current_page_only'])) {
            $params['p'] = 1;
            $params['l'] = 'all';
        }
        $format = $data['format'] ?? '';
        $entities = $this->service->list($params);
        $fields = Collection::from($this->forms->base()->getFields())
            ->filter(function (Field $v) {
                return $v->getType() !== 'hidden';
            })
            ->mapKey(function (Field $v) {
                return $v->getName();
            })
            ->map(function (Field $v) {
                return $this->intl->get($v->getOption('label', $this->moduleName . '.' . $v->getName()));
            })
            ->toArray();
        $columns = [];
        if (!isset($data['all_columns']) && isset($data['columns'])) {
            foreach (array_filter(explode(',', $data['columns'])) as $column) {
                if (isset($fields[$column])) {
                    $columns[$column] = $fields[$column];
                }
            }
        } else {
            $columns = $fields;
        }

        $response = new Response();
        foreach (Writer::headers($format, 'export.' . $format) as $k => $v) {
            $response = (new Response())->withHeader((string)$k, (string)$v);
        }
        return $response
            ->withCallback(function () use ($format, $columns, $entities, $config) {
                // excel:true is for the csv writer Excel compatibility
                $writer = Writer::toBrowser(
                    $format,
                    [ 'temp' => $config->get('STORAGE_TMP'), 'excel' => true, 'defaultSheet' => 'Sheet1' ]
                );
                $driver = $writer->getDriver();
                if ($driver instanceof XLSXWriter) {
                    $driver->addHeaderRow(array_values($columns));
                } else {
                    $driver->addRow(array_values($columns));
                }
                $writer
                    ->fromIterable(
                        Collection::from($entities->getIterator())
                            ->map(function (Entity $v) use ($columns) {
                                $row = [];
                                foreach (array_keys($columns) as $column) {
                                    $row[$column] = $v->{$column} ?? null;
                                }
                                return $row;
                            })
                    );
            });
    }

    public function getRead(Request $request): Response
    {
        try {
            if (!$this->module->canRead()) {
                throw new CRUDNotFoundException('crud.read.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }

        $form = $this->forms->read($entity);
        return (new Response())->setBody(
            $this->render(
                'read',
                [
                    'form'       => $form,
                    'entity'     => $entity,
                    'pkey'       => $this->service->id($entity),
                    'title'      => $this->moduleName . '.titles.read',
                    'name'       => $this->service->name($entity),
                    'icon'       => 'eye',
                    'breadcrumb' => $this->moduleName . '.breadcrumb.read',
                    'back'       => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    ),
                    'update'     => $this->module->canUpdate() && $this->service->updatable($entity),
                    'delete'     => $this->module->canDelete() && $this->service->deletable($entity),
                    'history'    => $this->module->hasHistory()
                ]
            )
        );
    }
    public function getHistory(Request $request): Response
    {
        if (!($this->service instanceof CRUDServiceVersionedInterface)) {
            $this->session->set('error', $this->moduleName . '.messages.notfound');
            return (new Response(303))->withHeader(
                'Location',
                $request->getUrl()->linkTo(
                    $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                )
            );
        }

        try {
            if (!$this->module->hasHistory()) {
                throw new CRUDException('crud.history.notallowed');
            }
            /** @var T $entity */
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        $version = $request->getQuery('version', null, 'int');
        $versions = $this->service->versions($entity, $version);
        if (isset($version)) {
            $versions = array_values(array_reverse($versions));
            return (new Response())
                ->setContentTypeByExtension('json')
                ->setBody(
                    json_encode([
                        'curr' => $this->views->render(
                            'webadmin::form',
                            [
                                'form' => $this->forms->history(json_decode($versions[0]['entity'] ?? '[]', true))
                            ]
                        ),
                        'prev' => $version > 0 && isset($versions[1]) ?
                            $this->views->render(
                                'webadmin::form',
                                [
                                    'form' => $this->forms->history(json_decode($versions[1]['entity'] ?? '[]', true))
                                ]
                            ) :
                            ''
                    ]) ?: ''
                );
        }
        return (new Response())->setBody(
            $this->render(
                'history',
                [
                    'versions'   => Collection::from($versions)
                        ->map(function (array $v) {
                            /** @var array<string,mixed> $v */
                            return [
                                //'form'    => $this->getHistoryForm()->populate(json_decode($v['entity'])),
                                'author'  => $v['usr_name'],
                                'created' => ($temp = DateTime::createFromFormat('Y-m-d H:i:s', $v['created'])) ?
                                    $temp->format('d.m.Y H:i:s') : ''
                            ];
                        })
                        ->reverse()
                        ->toArray(),
                    'pkey'       => $this->service->id($entity),
                    'title'      => $this->moduleName . '.titles.history',
                    'icon'       => 'clock',
                    'breadcrumb' => $this->moduleName . '.breadcrumb.history',
                    'back'       => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }
    public function getCreate(Request $request): Response
    {
        try {
            if (!$this->module->canCreate() && $this->service->creatable()) {
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
                    'icon'       => 'plus',
                    'breadcrumb' => $this->moduleName . '.breadcrumb.create',
                    'back'       => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }
    public function postCreate(Request $request): Response
    {
        try {
            if (!$this->module->canCreate() && $this->service->creatable()) {
                throw new CRUDNotFoundException('crud.create.notallowed');
            }
            $data = $request->getPost();
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
            if (!$this->service->updatable($entity)) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
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
                    'icon' => 'pencil',
                    'breadcrumb' => $this->moduleName . '.breadcrumb.update',
                    'back' => $request->getUrl()->linkTo(
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
            if (!$this->service->updatable($entity)) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
            $errors = $this->forms->update($entity, $data)->getValidator()->run($data);
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
    public function postPartial(Request $request): Response
    {
        try {
            if (!$this->module->canUpdate()) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            $data = array_merge($this->service->toArray($entity), $request->getPost());
            if (!$this->service->updatable($entity)) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
            $errors = $this->forms->update($entity, $data)->getValidator()->run($data);
            if (count($errors)) {
                foreach ($errors as $k => $v) {
                    if (!$v['message']) {
                        $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                    }
                }
                throw (new CRUDException("validation", 400))->setErrors($errors);
            }
            $entity = $this->service->update($this->service->id($entity), $data);
        } catch (CRUDException) {
            return (new Response(400));
        }
        return (new Response(200));
    }
    public function getDelete(Request $request): Response
    {
        try {
            if (!$this->module->canDelete()) {
                throw new CRUDNotFoundException('crud.delete.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            if (!$this->service->deletable($entity)) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        $form = $this->forms->delete($entity);
        return (new Response())->setBody(
            $this->render(
                'delete',
                [
                    'form'       => $form,
                    'pkey'       => $this->service->id($entity),
                    'entity'     => $entity,
                    'name'       => $this->service->name($entity),
                    'title'      => $this->moduleName . '.titles.delete',
                    'icon'       => 'trash',
                    'breadcrumb' => $this->moduleName . '.breadcrumb.delete',
                    'back'       => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }
    public function postDelete(Request $request): Response
    {
        try {
            if (!$this->module->canDelete()) {
                throw new CRUDNotFoundException('crud.delete.notallowed');
            }
            $data = $request->getPost();
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            if (!$this->service->deletable($entity)) {
                throw new CRUDNotFoundException('crud.update.notallowed');
            }
            $errors = $this->forms->delete($entity)->getValidator()->run($data);
            if (count($errors)) {
                foreach ($errors as $k => $v) {
                    if (!$v['message']) {
                        $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                    }
                }
                throw (new CRUDException("validation", 400))->setErrors($errors);
            }
            $this->service->delete($this->service->id($entity));
        } catch (CRUDException $e) {
            $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
            return $this->exceptionResponse($request, $e);
        }
        $this->session->set('success', $this->moduleName . '.messages.deleted');
        $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
        return (new Response(303))->withHeader(
            'Location',
            $request->getUrl()->linkTo(
                $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
            )
        );
    }
    public function getCopy(Request $request): Response
    {
        try {
            if (!$this->module->canCopy()) {
                throw new CRUDNotFoundException('crud.copy.notallowed');
            }
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        $form = $this->forms->copy($entity, $this->session->del($this->moduleName . '.copy') ?? []);
        return (new Response())->setBody(
            $this->render(
                'copy',
                [
                    'form'       => $form,
                    'pkey'       => $this->service->id($entity),
                    'entity'     => $entity,
                    'name'       => $this->service->name($entity),
                    'title'      => $this->moduleName . '.titles.copy',
                    'icon'       => 'copy',
                    'breadcrumb' => $this->moduleName . '.breadcrumb.copy',
                    'back'       => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }
    public function postCopy(Request $request): Response
    {
        try {
            if (!$this->module->canCopy()) {
                throw new CRUDNotFoundException('crud.copy.notallowed');
            }
            $data = $request->getPost();
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            $errors = $this->forms->copy($entity, $data)->getValidator()->run($data);
            if (count($errors)) {
                foreach ($errors as $k => $v) {
                    if (!$v['message']) {
                        $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                    }
                }
                throw (new CRUDException("validation", 400))->setErrors($errors);
            }
            $this->service->create($data);
        } catch (CRUDException $e) {
            $this->session->set($this->moduleName . '.copy', $request->getPost());
            $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
            return $this->exceptionResponse($request, $e);
        }
        $this->session->del($this->moduleName . '.copy');
        $this->session->set('success', $this->moduleName . '.messages.copied');
        $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
        return (new Response(303))->withHeader(
            'Location',
            $request->getUrl()->linkTo(
                $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
            )
        );
    }
    public function getImport(Request $request): Response
    {
        $fields = Collection::from($this->forms->base()->getFields())
            ->filter(function (Field $v) {
                return $v->getType() !== 'hidden';
            })
            ->mapKey(function (Field $v) {
                return $v->getName();
            })
            ->map(function (Field $v): string {
                return $v->getOption('label', $this->moduleName . '.' . $v->getName());
            })
            ->toArray();
        return (new Response())->setBody(
            $this->render(
                'import',
                [
                    'form' => (new Form())
                        ->addField(new Field(
                            "file",
                            ['name' => 'import'],
                            ['label' => $this->moduleName . '.import' ]
                        )),
                    'fields' => $fields,
                    'title' => $this->moduleName . '.titles.import',
                    'icon' => 'upload',
                    'breadcrumb' => $this->moduleName . '.breadcrumb.import',
                    'errors' => $this->session->del('import_errors'),
                    'back' => $request->getUrl()->linkTo(
                        $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
                    )
                ]
            )
        );
    }
    public function postImport(Request $request, Files $files): Response
    {
        try {
            $data   = $request->getPost();
            if (!is_array($data) || !isset($data['import'])) {
                throw new CRUDException('Invalid input');
            }
            $file   = $files->get($data['import']);
            $name   = $file->name();
            $first  = true;
            $fields = Collection::from($this->forms->create()->getFields())
                ->mapKey(function (Field $v) {
                    return $v->getName();
                })
                ->map(function (Field $v) {
                    return $this->moduleName . '.columns.' . $v->getName();
                })
                ->toArray();
            $errors = [];
            foreach ((new Reader($file->path() ?: throw new \RuntimeException(), $file->ext())) as $k => $row) {
                if (isset($data['skip_first']) && $first) {
                    $first = false;
                    continue;
                }
                $first = false;
                $temp = [];
                foreach ($data['columns'] as $ind => $name) {
                    if (!$name || !isset($fields[$name])) {
                        continue;
                    }
                    $temp[(string)$name] = $row[$ind] ?? null;
                }
                try {
                    $this->service->create($temp);
                } catch (CRUDException $e) {
                    $temp = Collection::from($e->getErrors())
                        ->pluck('message')
                        ->values()
                        ->toArray();
                    if (!count($temp)) {
                        $temp[] = $e->getMessage();
                    }
                    $errors[$k] = $temp;
                    if (count($errors) >= 5) {
                        break;
                    }
                }
            }
            if (count($errors)) {
                $this->session->set('import_errors', $errors);
                return (new Response(303))->withHeader('Location', (string)$request->getUri());
            }
        } catch (CRUDException $e) {
            $errors = ['common.error.tryagain'];
            $errors = Collection::from($e->getErrors())->pluck('message')->toArray();
            if (!count($errors)) {
                $errors[] = $e->getMessage();
            }
            $this->session->set('error', $errors);
            $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
            return (new Response(303))->withHeader('Location', (string)$request->getUri());
        }
        $this->session->set('success', $this->moduleName . '.messages.imported');
        $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
        return (new Response(303))->withHeader(
            'Location',
            $request->getUrl()->linkTo(
                $this->session->get($this->moduleName  . '.index', $this->module->getSlug())
            )
        );
    }
    public function getJSON(Request $request): Response
    {
        $entity = $request->getQuery('id');
        if ($entity) {
            $entity = explode(',', $entity);
            $result = [];
            foreach ($entity as $e) {
                try {
                    $temp = $this->service->read($e);
                    $id   = $this->service->id($temp);
                    $result[] = [
                        'name' => $this->service->name($temp),
                        'value' => count($id) === 1 ? current($id) : $id
                    ];
                } catch (\Exception) {
                }
            }
            $result = [ 'success' => true, 'results' => $result ];
        } else {
            $params = $request->getQuery();
            $result = [];
            foreach ($this->service->list($params) as $v) {
                $id = $this->service->id($v);
                $result[] = [
                    'name' => $this->service->name($v),
                    'value' => count($id) === 1 ? current($id) : $id
                ];
            }
            $result = [ 'success' => true, 'results' => $result ];
        }
        return (new Response())
            ->setContentTypeByExtension('json')
            ->setBody(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '');
    }
    public function postRedraw(Request $request): Response
    {
        $form = (int)$request->getUrl()->getSegment(2) ?
            $this->forms->update($this->service->read($request->getUrl()->getSegment(2)), $request->getPost()) :
            $this->forms->create($request->getPost());
        return (new Response())->setBody(
            $this->views->render('webadmin::form', [ 'form' => $form ])
        );
    }
    public function getRelation(Request $request): Response
    {
        try {
            $entity = $this->service->read($request->getUrl()->getSegment(2));
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
        $relations = $this->service->definition()->getModules();
        $name = $request->getUrl()->getSegment(3);
        $modules = $relations[$name] ?? throw new CRUDException('Invalid relation');
        $relation = $this->service->definition()->getRelation($name);
        if (!$relation || !$relation->many) {
            throw new CRUDException('Invalid relation');
        }
        $module = $modules[$request->getUrl()->getSegment(4)] ?? throw new CRUDException('Invalid relation');
        $service = $module->getService();
        $forms = $module->getForms();
        $filter = [];
        if ($relation->pivot) {
            $rev = $relation->reverse();
            if (!$rev) {
                throw new CRUDException('Invalid relation');
            }
            foreach ($rev->pivot_keymap ?? [] as $local => $remote) {
                $filter[$rev->name . '.' . $remote] = $entity->{$remote};
            }
        } else {
            foreach ($relation->keymap as $local => $remote) {
                $filter[$remote] = $entity->{$local};
            }
        }
        switch ($request->getUrl()->getSegment(5)) {
            case 'update':
                $related = $service->read($request->getUrl()->getSegment(6));
                $form = $forms->update($related);
                return (new Response())->setBody(
                    $this->render(
                        'relation_form',
                        [
                            'pkey'       => $this->service->id($entity),
                            'entity'     => $entity,
                            'name'       => $this->service->name($entity),
                            'module'     => $this->module,
                            'relation'   => $module,
                            'form'       => $form,
                            'related'    => $related,
                            'relname'    => $service->name($related),
                            'relid'      => $service->id($related),
                            'operation'  => $request->getUrl()->getSegment(5),
                            'back'       => $request->getUrl()->getSegment(0) . '/' .
                                $request->getUrl()->getSegment(1) . '/' .
                                $request->getUrl()->getSegment(2) . '/' .
                                $request->getUrl()->getSegment(3) . '/' .
                                $request->getUrl()->getSegment(4)
                        ]
                    )
                );
            case 'delete':
                $related = $service->read($request->getUrl()->getSegment(6));
                $form = $forms->delete($related);
                return (new Response())->setBody(
                    $this->render(
                        'relation_form',
                        [
                            'pkey'       => $this->service->id($entity),
                            'entity'     => $entity,
                            'name'       => $this->service->name($entity),
                            'module'     => $this->module,
                            'relation'   => $module,
                            'form'       => $form,
                            'related'    => $related,
                            'relname'    => $service->name($related),
                            'relid'      => $service->id($related),
                            'operation'  => $request->getUrl()->getSegment(5),
                            'back'       => $request->getUrl()->getSegment(0) . '/' .
                                $request->getUrl()->getSegment(1) . '/' .
                                $request->getUrl()->getSegment(2) . '/' .
                                $request->getUrl()->getSegment(3) . '/' .
                                $request->getUrl()->getSegment(4)
                        ]
                    )
                );
            case 'read':
                $related = $service->read($request->getUrl()->getSegment(6));
                $form = $forms->read($related);
                return (new Response())->setBody(
                    $this->render(
                        'relation_form',
                        [
                            'pkey'       => $this->service->id($entity),
                            'entity'     => $entity,
                            'name'       => $this->service->name($entity),
                            'module'     => $this->module,
                            'relation'   => $module,
                            'form'       => $form,
                            'related'    => $related,
                            'relname'    => $service->name($related),
                            'relid'      => $service->id($related),
                            'operation'  => $request->getUrl()->getSegment(5),
                            'back'       => $request->getUrl()->getSegment(0) . '/' .
                                $request->getUrl()->getSegment(1) . '/' .
                                $request->getUrl()->getSegment(2) . '/' .
                                $request->getUrl()->getSegment(3) . '/' .
                                $request->getUrl()->getSegment(4)
                        ]
                    )
                );
            default:
                $query = $request->getQuery();
                if (isset($query['l'])) {
                    $query['l'] = max(0, min((int)$query['l'], 100));
                    if (!(int)$query['l']) {
                        unset($query['l']);
                    }
                }
                $params = $query;
                if (!isset($params['p'])) {
                    $params['p'] = 1;
                }
                if (!isset($params['l'])) {
                    $params['l'] = 25;
                }
                if (isset($params['q']) && is_string($params['q']) && !strlen($params['q'])) {
                    unset($params['q']);
                }
                $params = array_merge(
                    $params,
                    $filter
                );
                $entities = $service->list($params);
                $table = $forms->listing($entities, $params, count($entities));
                foreach ($table->getOperations() as $name => $operation) {
                    if ($name !== 'create') {
                        $operation->hide();
                    } else {
                        $operation->setAttr(
                            'href',
                            trim($request->getUrl()->getRealPath(), '/') . '/create'
                        );
                    }
                }
                foreach ($table->getRows() as $row) {
                    foreach ($row->getOperations() as $name => $operation) {
                        if (!in_array($name, [ 'read', 'update', 'delete' ])) {
                            $operation->hide();
                        } else {
                            $operation->setAttr(
                                'href',
                                trim($request->getUrl()->getRealPath(), '/') . '/' . $name . '/' . $row->getAttr('id')
                            );
                        }
                    }
                }
                return (new Response())->setBody(
                    $this->render(
                        'relation',
                        [
                            'pkey'       => $this->service->id($entity),
                            'entity'     => $entity,
                            'name'       => $this->service->name($entity),
                            'module'     => $this->module,
                            'table'      => $table,
                            'relation'   => $module
                        ]
                    )
                );
        }
    }
    public function postRelation(Request $request): Response
    {
        $this->session->set('removeLS', 'local:/' . trim($request->getUrl()->getPath(), '/'));
        try {
            $entity = $this->service->read($request->getUrl()->getSegment(2));
            $back = $request->getUrl()->getSegment(0) . '/' .
                $request->getUrl()->getSegment(1) . '/' .
                $request->getUrl()->getSegment(2) . '/' .
                $request->getUrl()->getSegment(3) . '/' .
                $request->getUrl()->getSegment(4);
            $relations = $this->service->definition()->getModules();
            $name = $request->getUrl()->getSegment(3);
            $modules = $relations[$name] ?? throw new CRUDException('Invalid relation');
            $relation = $this->service->definition()->getRelation($name);
            if (!$relation || !$relation->many) {
                throw new CRUDException('Invalid relation');
            }
            $module = $modules[$request->getUrl()->getSegment(4)] ?? throw new CRUDException('Invalid relation');
            $service = $module->getService();
            $filter = [];
            if ($relation->pivot) {
                $rev = $relation->reverse();
                if (!$rev) {
                    throw new CRUDException('Invalid relation');
                }
                foreach ($rev->pivot_keymap ?? [] as $local => $remote) {
                    $filter[$rev->name . '.' . $remote] = $entity->{$remote};
                }
            } else {
                foreach ($relation->keymap as $local => $remote) {
                    $filter[$remote] = $entity->{$local};
                }
            }
            switch ($request->getUrl()->getSegment(5)) {
                case 'create':
                    // only add relation
                    $related = $service->read($request->getPost('__nested_id'));
                    $entity->{$relation->name}->add($related);
                    $this->service->update($this->service->id($entity), []);
                    return (new Response(303))->withHeader('Location', $request->getUrl()->get($back));
                case 'delete':
                    // only remove relation
                    $related = $service->read($request->getUrl()->getSegment(6));
                    $entity->{$relation->name}->remove($related);
                    $this->service->update($this->service->id($entity), []);
                    return (new Response(303))->withHeader('Location', $request->getUrl()->get($back));
                case 'update':
                    $data = $request->getPost();
                    $related = $service->read($request->getUrl()->getSegment(6));
                    $forms = $module->getForms();
                    $errors = $forms->update($entity, $data)->getValidator()->run($data);
                    if (count($errors)) {
                        foreach ($errors as $k => $v) {
                            if (!$v['message']) {
                                $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                            }
                        }
                        throw (new CRUDException("validation", 400))->setErrors($errors);
                    }
                    $related = $service->update($service->id($related), $data);
                    return (new Response(303))->withHeader('Location', $request->getUrl()->get($back));
                default:
                    throw new CRUDException('Invalid relation method', 404);
            }
        } catch (CRUDException $e) {
            return $this->exceptionResponse($request, $e);
        }
    }
}
