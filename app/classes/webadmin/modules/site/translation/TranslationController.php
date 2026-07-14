<?php

declare(strict_types=1);

namespace webadmin\modules\site\translation;

use vakata\http\Uri as Url;
use vakata\http\Request as Request;
use vakata\http\Response as Response;
use vakata\views\Views;
use vakata\session\SessionInterface as Session;

class TranslationController
{
    protected TranslationService $translations;

    public function __construct(TranslationService $translations)
    {
        $this->translations = $translations;
    }
    public function getIndex(Request $req, Response $res, Views $views): Response
    {
        $views->addFolder('pubtranslation', __DIR__ . '/views');
        $langs = $this->translations->getLanguages();
        $lang = $req->getQuery('lang', array_keys($langs)[0] ?? '');
        if ($req->getQuery('download')) {
            $data = $this->translations->getTranslations($lang);
            return $res
                ->withHeader('Content-Disposition', 'attachment; filename=' . $lang . '.json')
                ->setBody(
                    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: ''
                );
        }
        return $res->setBody(
            $views->render('pubtranslation::index', [
                'langs' => $langs,
                'lang' => $lang,
                'data' => $this->translations->getTranslations($lang)
            ])
        );
    }
    public function postIndex(Request $req, Response $res, Session $sess, Url $url): Response
    {
        $langs = $this->translations->getLanguages();
        $lang = $req->getQuery('lang', array_keys($langs)[0] ?? '');
        $data = array_combine($req->getPost('keys') ?? [], $req->getPost('values') ?? []) ?: [];
        try {
            $this->translations->setTranslations($lang, $data);
            $sess->set('success', 'translation.success');
        } catch (\Exception $e) {
            $sess->set('success', 'translation.fail');
        }
        return $res->withStatus(303)->withHeader('Location', (string)$url);
    }
    public function postStore(Request $req, Response $res, Session $sess): Response
    {
        $langs = $this->translations->getLanguages();
        $lang = $req->getQuery('lang', array_keys($langs)[0] ?? '');
        try {
            $this->translations->store($lang);
            $sess->set('success', 'translation.success');
        } catch (\Exception $e) {
            $sess->set('success', 'translation.fail');
        }
        return $res->withStatus(200);
    }
}
