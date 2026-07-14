<?php

declare(strict_types=1);

namespace webadmin\modules\common\crud;

use RuntimeException;
use vakata\database\schema\Entity;
use vakata\di\DIContainer;
use vakata\http\Request;
use vakata\http\Response;
use webadmin\api\API;
use webadmin\api\APIException;
use webadmin\components\html\Form;
use webadmin\components\html\Table;
use webadmin\api\Endpoint;
use webadmin\api\Entity as APIEntity;
use webadmin\api\Error;
use webadmin\api\Parameter;
use webadmin\api\Field;
use webadmin\modules\ModulesContainer;
use webadmin\modules\VisualModule;

/**
 * @template T of \vakata\database\schema\Entity
 * @template S of CRUDServiceInterface<T>
 * @implements CRUDModuleInterface<T,S>
 * @SuppressWarnings("PHPMD.NumberOfChildren")
 */
abstract class CRUDModule extends VisualModule implements CRUDModuleInterface
{
    /**
     * @param string $name
     * @param string $slug
     * @param string $icon
     * @param string $color
     * @param string $parent
     * @param ?class-string $controller
     * @param string $table
     * @param class-string<S> $service
     * @return void
     */
    public function __construct(
        protected DIContainer $container,
        protected string $name,
        protected string $slug,
        protected string $icon = 'cog',
        protected string $color = 'teal',
        protected string $parent = '',
        protected string $table = '',
        protected ?string $controller = namespace\CRUDController::class,
        protected string $service = namespace\CRUDService::class,
        protected ?string $views = null
    ) {
        if (!$this->table) {
            $this->table = strtolower(
                preg_replace('(Module$)', '', basename(str_replace('\\', '/', get_class($this)))) ?? ''
            );
        }
        $this->service = $this->service === namespace\CRUDService::class && $this->hasHistory() ?
            namespace\CRUDServiceVersioned::class :
            $this->service;
        parent::__construct($container, $name, $slug, $icon, $color, $parent, $controller);
    }
    public function getTable(): string
    {
        return $this->table;
    }
    public function getViews(): ?string
    {
        return $this->views;
    }

    /**
     * @param Table $table
     * @return Table
     */
    public function listingCallback(Table $table): Table
    {
        return $table;
    }
    /**
     * @param Form $form
     * @return Form
     */
    public function formCallback(Form $form): Form
    {
        return $form;
    }

    public function canCreate(): bool
    {
        return true;
    }
    public function canRead(): bool
    {
        return false;
    }
    public function canUpdate(): bool
    {
        return true;
    }
    public function canDelete(): bool
    {
        return true;
    }
    public function canCopy(): bool
    {
        return false;
    }
    public function hasHistory(): bool
    {
        return false;
    }

    public function getService(): CRUDServiceInterface
    {
        $params = [];
        $params['module'] = $this;
        return $this->container->instance($this->service, $params);
    }
    /**
     * @return CRUDFormsInterface<T>
     */
    /** @psalm-suppress all */
    public function getForms(): CRUDFormsInterface
    {
        $params = [];
        $params['module'] = $this;
        $params['service'] = $this->getService();
        /** @psalm-suppress all */
        // @phpstan-ignore-next-line
        return $this->container->instance(CRUDForms::class, $params);
    }
    public function getController(): object
    {
        if (!$this->controller) {
            throw new RuntimeException('Controller missing');
        }

        $params = [];
        $params['module'] = $this;
        $params['service'] = $this->getService();
        $params['forms'] = $this->getForms();
        return $this->container->instance($this->controller, $params);
    }
    protected function getAPI(CRUDEntityDefinition $definition, API $api): CRUDAPI
    {
        return new CRUDAPI($definition, $api);
    }
    public function getEndpoints(API $api): void
    {
        /** @var S $service */
        $service = $this->getService();
        $definition = $service->definition();

        $crudAPI = $this->getAPI($definition, $api);
        $forms = $this->getForms();
        $form = $this->formCallback($forms->base());

        /** @var \vakata\intl\Intl */
        $intl = $this->container->get(\vakata\intl\Intl::class);

        $title = $intl->get($this->getName() . '.title');

        if (!$api->hasTag($intl->get($title))) {
            $api->addTag($title, $intl->get($this->getName() . '.description'));
        }

        $requestEntity = $crudAPI->request($form);
        $table = $crudAPI->list(
            $forms->listing([]),
            $requestEntity,
            $service->listQuery()
                ->schema()
        );
        $responseEntity = $crudAPI->response(
            $api->getComponent($definition->getName()),
            $service->readQuery()
                ->schema()
        );

        $list = (new Endpoint($this->getName() . '/list', '/' . $this->getSlug()))
            ->enableGet()
            ->setCallback(function (Request $request) use ($service): Response {
                $query = $request->getQuery();

                if (isset($query['l'])) {
                    $query['l'] = max(0, min((int)$query['l'], 100));
                    if (!(int)$query['l']) {
                        unset($query['l']);
                    }
                }
                $params = array_merge([], $query);
                if (!isset($params['p'])) {
                    $params['p'] = 1;
                }
                if (!isset($params['l'])) {
                    $params['l'] = 25;
                }
                if (isset($params['q']) && is_string($params['q']) && !strlen($params['q'])) {
                    unset($params['q']);
                }
                $entities = $service->list($params);

                return (new Response(200))
                    ->setContentTypeByExtension('json')
                    ->setBody(
                        json_encode([
                            'count'         => count($entities),
                            'page'          => $params['p'],
                            'perpage'       => $params['l'],
                            'entities'      => $entities->collection()
                        ]) ?: ''
                    );
            })
            ->setResponse(
                (new APIEntity())
                    ->addField(new Field('count', 'integer', 'int64'))
                    ->addField(new Field('page', 'integer', 'int64'))
                    ->addField(new Field('perpage', 'integer', 'int64'))
                    ->addField(new Field('entities', 'array', null, false, null, $table['entity']))
            )
            ->addTag($title);

        foreach ($table['params'] as $param) {
            $list->addQueryParam($param);
        }

        $api->addEndpoint($list);

        if ($this->canCreate()) {
            $api->addEndpoint(
                (new Endpoint($this->getName() . '/create', '/' . $this->getSlug()))
                    ->enablePost()
                    ->setCallback(function (Request $request) use ($service, $forms): Response {
                        $data = $request->getPost();
                        $errors = $forms->create()->getValidator()->run($data);
                        if (count($errors)) {
                            foreach ($errors as $k => $v) {
                                if (!$v['message']) {
                                    $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                                }
                            }
                            throw (new APIException("validation", 400))->setErrors($errors);
                        }

                        $entity = $service->create($request->getPost());

                        return (new Response(200))
                            ->setContentTypeByExtension('json')
                            ->setBody(json_encode($entity) ?: '');
                    })
                    ->setRequest($requestEntity)
                    ->setResponse($responseEntity)
                    ->addTag($title)
                    ->addError($api->getError('400'))
                    ->addError($api->getError('403'))
                    ->addError($api->getError('500'))
            );
        }
        if ($this->canRead()) {
            $read = (
                new Endpoint(
                    $this->getName() . '/read',
                    '/' . $this->getSlug() . '/' .
                    implode(
                        '|',
                        array_map(
                            function (string $key): string {
                                return '{' . $key . '}';
                            },
                            $definition->getPrimaryKey()
                        )
                    )
                )
            )
                ->enableGet()
                ->setCallback(function (Request $request) use ($service): Response {
                    $entity = $service->read($request->getUrl()->getSegment(2));

                    return (new Response(200))
                        ->setContentTypeByExtension('json')
                        ->setBody(json_encode($entity) ?: '');
                })
                ->setResponse($responseEntity)
                ->addTag($title)
                ->addError($api->getError('400'))
                ->addError($api->getError('403'))
                ->addError($api->getError('404'))
                ->addError($api->getError('500'));

            foreach ($definition->getPrimaryKey() as $key) {
                if ($definition->getColumn($key)) {
                    /** @psalm-suppress PossiblyNullReference */
                    $type = API::getTypeAndFormat($definition->getColumn($key)->getBasicType());
                    $read->addPathParam(new Parameter($key, $type['type'], $type['format'], true));
                }
            }

            $api->addEndpoint($read);
        }
        if ($this->canUpdate()) {
            $update = (
                new Endpoint(
                    $this->getName() . '/update',
                    '/' . $this->getSlug() . '/' .
                    implode(
                        '|',
                        array_map(
                            function (string $key): string {
                                return '{' . $key . '}';
                            },
                            $definition->getPrimaryKey()
                        )
                    )
                )
            )
                ->enablePost()
                ->enablePut()
                ->setCallback(function (Request $request) use ($service, $forms): Response {
                    $data = $request->getPost();
                    $entity = $service->read($request->getUrl()->getSegment(2));
                    $errors = $forms->update($entity, $data)->getValidator()->run($data);

                    if (count($errors)) {
                        foreach ($errors as $k => $v) {
                            if (!$v['message']) {
                                $errors[$k]['message'] = 'validation.' . $v['key'] . '.' . $v['rule'];
                            }
                        }
                        throw (new CRUDException("validation", 400))->setErrors($errors);
                    }
                    $entity = $service->update($service->id($entity), $data);

                    return (new Response(200))
                        ->setContentTypeByExtension('json')
                        ->setBody(json_encode($entity) ?: '');
                })
                ->setRequest($requestEntity)
                ->setResponse($responseEntity)
                ->addTag($title)
                ->addError($api->getError('400'))
                ->addError($api->getError('403'))
                ->addError($api->getError('404'))
                ->addError($api->getError('500'));

            foreach ($definition->getPrimaryKey() as $key) {
                if ($definition->getColumn($key)) {
                    /** @psalm-suppress PossiblyNullReference */
                    $type = API::getTypeAndFormat($definition->getColumn($key)->getBasicType());
                    $update->addPathParam(new Parameter($key, $type['type'], $type['format'], true));
                }
            }

            $api->addEndpoint($update);
        }
        if ($this->canDelete()) {
            $delete = (
                new Endpoint(
                    $this->getName() . '/delete',
                    '/' . $this->getSlug() . '/' .
                    implode(
                        '|',
                        array_map(
                            function (string $key): string {
                                return '{' . $key . '}';
                            },
                            $definition->getPrimaryKey()
                        )
                    )
                )
            )
                ->enableDelete()
                ->setCallback(function (Request $request) use ($service): Response {
                    $entity = $service->read($request->getUrl()->getSegment(2));

                    $service->delete($entity);

                    return (new Response(200))
                        ->setContentTypeByExtension('json');
                })
                ->setResponse(new APIEntity())
                ->addTag($title)
                ->addError($api->getError('400'))
                ->addError($api->getError('403'))
                ->addError($api->getError('404'))
                ->addError($api->getError('500'));

            foreach ($definition->getPrimaryKey() as $key) {
                if ($definition->getColumn($key)) {
                    /** @psalm-suppress PossiblyNullReference */
                    $type = API::getTypeAndFormat($definition->getColumn($key)->getBasicType());
                    $delete->addPathParam(new Parameter($key, $type['type'], $type['format'], true));
                }
            }

            $api->addEndpoint($delete);
        }
    }
}
