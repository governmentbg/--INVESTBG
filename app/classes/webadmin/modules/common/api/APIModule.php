<?php

declare(strict_types=1);

namespace webadmin\modules\common\api;

use RuntimeException;
use Throwable;
use vakata\collection\Collection;
use vakata\di\DIContainer;
use vakata\http\Request;
use vakata\http\Response;
use vakata\intl\Intl;
use vakata\views\Views;
use webadmin\components\router\Router;
use webadmin\modules\common\crud\CRUDException;
use webadmin\modules\common\crud\CRUDNotFoundException;
use webadmin\modules\VisualModule;

class APIModule extends VisualModule
{
    public const string NAME = 'api';
    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            static::NAME,
            $slug,
            'cogs',
            'teal',
            'other'
        );
    }
    public function inMenu(): bool
    {
        return false;
    }
    public function onDashboard(): bool
    {
        return false;
    }
    public function process(Request $request): Response
    {
        /** @var APIService $service */
        $service = $this->container->instance(APIService::class);
        /** @var Views $views */
        $views = $this->container->get(Views::class);
        $views->addFolder('api', __DIR__ . '/views');
        /** @var Intl $intl */
        $intl = $this->container->get(Intl::class);

        $api = $service->api(
            $request->getUrl()->get($this->getSlug()),
            self::$modules ?: throw new RuntimeException()
        );

        $router = (new Router())
            ->get(
                '/' . $this->getSlug(),
                function () use ($views, $api): Response {
                    return (new Response())
                        ->setBody(
                            $views->render(
                                'api::swagger',
                                [
                                    'api'   => $api
                                ]
                            )
                        );
                }
            );

        foreach ($api->getEndpoints() as $endpoint) {
            $router->add(
                $endpoint->getMethods(),
                '/' . $this->getSlug() . $endpoint->getPath(),
                $endpoint->getCallback(),
                $endpoint->getName()
            );
        }

        try {
            return $router->run($request);
        } catch (CRUDException $e) {
            $errors = Collection::from($e->getErrors())->pluck('message')->toArray();
            if ($e instanceof CRUDNotFoundException) {
                $code = 404;
                $errors[] = 'common.messages.notfound';
            } else {
                $code = $e->getCode() ?: 400;
            }
            if (!count($errors)) {
                $errors[] = $e->getMessage();
            }

            return (new Response($code))
                ->setContentTypeByExtension('json')
                ->setBody(
                    json_encode([
                        'errors' => array_map(
                            function (string $value) use ($intl): string {
                                return $intl->get($value);
                            },
                            $errors
                        )
                    ]) ?: ''
                );
        } catch (Throwable $e) {
            return (new Response(500))
                ->setContentTypeByExtension('json')
                    ->setBody(
                        json_encode([
                        'errors' => [ 'Internal Server Errror' ]
                        ]) ?: ''
                    );
        }
    }
}
