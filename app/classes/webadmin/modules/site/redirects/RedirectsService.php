<?php

declare(strict_types=1);

namespace webadmin\modules\site\redirects;

use vakata\database\DBInterface;
use webadmin\modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\user\User;
use webadmin\modules\common\crud\CRUDException;

/**
 * @extends CRUDService<\schema\RedirectsEntity>
 */
class RedirectsService extends CRUDService
{
    public function __construct(RedirectsModule $module, DBInterface $db, User $user)
    {
        if (!$user->site) {
            throw new CRUDException('No site configured for user');
        }
        parent::__construct($module, $db, $user);
    }
    protected function entities(): TableQueryMapped
    {
        return parent::entities()->filter('site', $this->user->site);
    }
    protected function processUrl(string $url): string
    {
        $url = parse_url($url, PHP_URL_PATH);
        if (!$url) {
            $url = '';
        }
        return trim($url, ' /');
    }
    public function create(array $data = []): Entity
    {
        $data['site'] = $this->user->site;
        $data['url_from'] = $this->processUrl($data['url_from'] ?? '');
        $data['url_to'] = $data['url_to'] ?? '';
        return parent::create($data);
    }
    public function update(mixed $id, array $data = []): Entity
    {
        unset($data['site']);
        $data['url_from'] = $this->processUrl($data['url_from'] ?? '');
        $data['url_to'] = $data['url_to'] ?? '';
        return parent::update($id, $data);
    }
}
