<?php

declare(strict_types=1);

namespace webadmin\modules\site\templates;

use vakata\database\DBInterface;
use webadmin\modules\common\crud\CRUDService;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use webadmin\components\html\Form;
use vakata\user\User;
use webadmin\modules\ModulesContainer;
use webadmin\modules\site\TemplateInterface;

/**
 * @extends CRUDService<\schema\TemplatesEntity>
 */
class TemplatesService extends CRUDService
{
    protected ModulesContainer $mc;

    public function __construct(
        TemplatesModule $module,
        ModulesContainer $mc,
        DBInterface $db,
        User $user
    ) {
        parent::__construct($module, $db, $user);
        $this->mc = $mc;
    }

    public function listQuery(): TableQueryMapped
    {
        /** @var TableQueryMapped<\schema\TemplatesEntity> */
        return parent::listQuery()
            ->columns([ 'base', 'name', 'is_default' ]);
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
        if (!isset($data['child_default']) || !$data['child_default']) {
            $data['child_default'] = null;
        }
        if (!isset($data['widgets']) || !$data['widgets']) {
            $data['widgets'] = null;
        }
        if (!isset($data['zones']) || !is_array($data['zones'])) {
            $data['zones'] = [];
        }
        $data['zones'][] = 'main';
        $data['zones'] = json_encode(array_unique($data['zones']));
        return parent::create($data);
    }
    public function update(mixed $id, array $data = []): Entity
    {
        if (!isset($data['child_default']) || !$data['child_default']) {
            $data['child_default'] = null;
        }
        if (!isset($data['widgets']) || !$data['widgets']) {
            $data['widgets'] = null;
        }
        if (!isset($data['zones']) || !is_array($data['zones'])) {
            $data['zones'] = [];
        }
        $data['zones'][] = 'main';
        $data['zones'] = json_encode(array_unique($data['zones']));
        $this->db->query("UPDATE {$this->table->getFullName()} SET widgets = NULL WHERE template = ?", $id['template']);
        return parent::update($id, $data);
    }
    /**
     * @return array<string>
     */
    public function baseTemplates(): array
    {
        $templates = $this->mc->getTemplates();
        return array_combine($templates, $templates);
    }
    /**
     * @return array<int,string>
     */
    public function templates(): array
    {
        return $this->db->rows(
            "SELECT template, name FROM {$this->table->getFullName()} ORDER BY name"
        )->toArray('template', 'name');
    }
    public function template(string $name): TemplateInterface
    {
        return $this->mc->getTemplate($name);
    }
    /**
     * @return array<string,string>
     */
    public function widgets(): array
    {
        $widgets = $this->mc->getWidgets();
        return array_combine($widgets, $widgets);
    }
    public function widget(string $name, array $data = [], array $context = []): Form
    {
        return $this->mc->getWidget($name)->getForm($data, $context);
    }
}
