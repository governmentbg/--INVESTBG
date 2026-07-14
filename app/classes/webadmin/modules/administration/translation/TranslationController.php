<?php

declare(strict_types=1);

namespace webadmin\modules\administration\translation;

use webadmin\Jobs;
use vakata\http\Uri as Url;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\views\Views;
use vakata\collection\Collection;
use vakata\config\Config;
use vakata\database\DBInterface;
use vakata\session\SessionInterface as Session;

class TranslationController
{
    protected TranslationService $translations;
    /** @var array<string,string> $langs */
    protected array $langs;

    public function __construct(Config $config, Jobs $jobs, DBInterface $db)
    {
        $this->langs = Collection::from(
            /** @psalm-suppress all */
            explode(',', $config->getString('LANGUAGES'))
        )
            ->mapKey(function (string $v): string {
                return $v;
            })
            ->map(function (string $v) use ($config): string {
                return $config->getString('STORAGE_INTL') . '/' . $v . '.json';
            })
            ->toArray();
        $this->translations = new TranslationService($jobs, $db);
    }
    public function getIndex(Request $req, Response $res, Views $views): Response
    {
        $views->addFolder('translation', __DIR__ . '/views');
        $locale = $req->getQuery('lang') ?? '';
        if (!isset($this->langs[$locale])) {
            $locale = array_keys($this->langs)[0];
        }
        if ($req->getQuery('download')) {
            $data = $this->translations->getTranslations($locale, $this->langs[$locale] ?? '');
            return $res
                ->withHeader('Content-Disposition', 'attachment; filename=' . $locale . '.json')
                ->setBody(
                    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: ''
                );
        }
        return $res->setBody(
            $views->render('translation::index', [
                'langs' => $this->langs,
                'lang'  => $locale,
                'all'   => $req->getQuery('all', '0', 'int'),
                'data'  => $req->getQuery('all', '0', 'int') ?
                    $this->translations->getTranslations($locale, $this->langs[$locale] ?? '') :
                    $this->translations->getMissingTranslations($locale)
            ])
        );
    }
    public function postIndex(Request $req, Response $res, Session $sess, Url $url): Response
    {
        $locale = $req->getQuery('lang') ?? '';
        if (!isset($this->langs[$locale])) {
            $locale = array_keys($this->langs)[0];
        }
        $data = array_combine($req->getPost('keys') ?? [], $req->getPost('values') ?? []) ?: [];
        try {
            $this->translations->setTranslations(
                $locale,
                $this->langs[$locale] ?? '',
                $data,
                $req->getQuery('all', '0', 'int') === 1
            );
            $sess->set('success', 'translation.success');
        } catch (\Exception $e) {
            $sess->set('success', 'translation.fail');
        }
        return $res->withStatus(303)->withHeader('Location', $url->linkTo($url->getSegment(0)));
    }
    public function postStore(Request $req, Response $res, Session $sess): Response
    {
        $locale = $req->getQuery('lang') ?? '';
        if (!isset($this->langs[$locale])) {
            $locale = array_keys($this->langs)[0];
        }
        try {
            $this->translations->store($locale, $this->langs[$locale] ?? '');
            $sess->set('success', 'translation.success');
        } catch (\Exception $e) {
            $sess->set('success', 'translation.fail');
        }
        return $res->withStatus(200);
    }
    public function postMissing(Request $req, Response $res): Response
    {
        $locale = basename($req->getAttribute('locale'));
        if (!isset($this->langs[$locale])) {
            $locale = array_keys($this->langs)[0];
        }
        $data = array_combine($req->getPost('keys'), $req->getPost('values'));
        try {
            $this->translations->setTranslations(
                $locale,
                $this->langs[$locale] ?? '',
                $data
            );
            return $res->withStatus(200);
        } catch (\Exception $e) {
            return $res->withStatus(500);
        }
    }
}
