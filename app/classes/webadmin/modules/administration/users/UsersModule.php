<?php

declare(strict_types=1);

namespace webadmin\modules\administration\users;

use schema\UsersEntity;
use vakata\collection\Collection;
use webadmin\components\html\Button;
use webadmin\components\html\Field;
use webadmin\components\html\Form;
use webadmin\components\html\HTML;
use webadmin\components\html\Table;
use webadmin\components\html\TableColumn;
use webadmin\modules\common\crud\CRUDModule;
use webadmin\modules\common\crud\CRUDServiceInterface;
use vakata\di\DIContainer;
use webadmin\api\APIProviderInterface;
use webadmin\modules\PermissionsModuleInterface;

/**
 * @extends CRUDModule<\schema\UsersEntity,UsersService>
 */
class UsersModule extends CRUDModule implements PermissionsModuleInterface, APIProviderInterface
{
    public const string NAME = 'users';

    public function __construct(DIContainer $container, string $slug = '')
    {
        parent::__construct(
            $container,
            self::NAME,
            $slug,
            'user',
            'orange',
            'administration',
            'users',
            namespace\UsersController::class,
            namespace\UsersService::class,
            __DIR__ . '/views'
        );
    }
    public function permissions(): array
    {
        return [ 'users/impersonate', 'users/master', 'users/languages' ];
    }
    public function canDelete(): bool
    {
        return false;
    }
    public function hasHistory(): bool
    {
        return true;
    }
    public function listingCallback(Table $table): Table
    {
        $service = $this->getService();
        $groups = $service->getAvailableGroups();
        $table
            ->removeColumn('usr')
            ->removeColumn('tfa')
            ->removeColumn('disabled')
            ->removeColumn('avatar')
            ->removeColumn('push')
            ->removeColumn('sessions')
            ->removeColumn('avatar_data')
            ->removeColumn('data');
        $table
            ->getColumn('name')
                ->setMap(function (mixed $value, UsersEntity $user) {
                    if ($user->avatar_data) {
                        return new HTML('<img class="ui avatar image" src="' . $user->avatar_data . '"> ' . $value);
                    } else {
                        return new HTML('' .
                            '<span class="ui grey circular label user-td-label">' .
                                '<i class="ui user icon"></i>' .
                            '</span> ' . $value);
                    }
                });
        $table
            ->addColumn(
                (new TableColumn('user_groups.grp'))
                    ->setMap(function (mixed $k, UsersEntity $row) use ($groups) {
                        $tags = [];
                        foreach ($row->user_groups as $group) {
                            if ($group && isset($groups[$group->grp])) {
                                $tags[] = '<span class="ui horizontal label">' . $groups[$group->grp] . '</span>';
                            }
                        }
                        return new HTML(implode('', $tags));
                    })
                    ->setSortable(false)
                    ->setFilter(
                        (new Form())
                            ->addField(new Field(
                                "multipleselect",
                                [ 'name' => 'user_groups.grp[]' ],
                                [ 'label' => $this->name . '.filters.groups', 'values' => $groups ]
                            ))
                    )
            );
        foreach ($table->getRows() as $v) {
            $operations = $v->getOperations(true);
            $temp = [];
            if ($service->canImpersonate($v->getData())) {
                $temp['impersonate'] = (new Button('impersonate'))
                    ->setLabel($this->name . '.operations.impersonate')
                    ->setIcon('user')
                    ->setClass('skip mini purple icon button')
                    ->setAttr('href', $this->slug . '/impersonate/' . $v->getAttr('id'));
            }
            $temp['kick'] = (new Button('kick'))
                ->setLabel($this->name . '.operations.kick')
                ->setIcon('sign out alternate')
                ->setClass('skip mini red icon button')
                ->setAttr('href', $this->slug . '/kick/' . $v->getAttr('id'));
            $temp['update'] = $operations['update'];
            $temp['history'] = $operations['history']->show();
            if (isset($operations['log'])) {
                $temp['log'] = $operations['log']->show()->addClass('skip');
            }
            $v->setOperations($temp);
            if ($v->getData()->disabled) {
                $v->addClass('error');
            }
        }
        return $table;
    }
    public function formCallback(Form $form): Form
    {
        $service = $this->getService();
        $layout = [
            'acc:open:' . $this->name . '.data',
            [ 'name', 'mail' ],
            [ 'disabled', 'tfa' ],
            'acc:' . $this->name . '.additional',
            [ 'data' ],
            'acc:' . $this->name . '.groups',
            [ 'main_grp' ],
            [ 'grps' ]
        ];
        $form
            ->removeField('usr')
            ->removeField('push')
            ->removeField('sessions')
            ->removeField('avatar')
            ->removeField('avatar_data');
        $form
            ->getField('disabled')
            ->setType('select')
            ->setOption('translate', true)
            ->setOption('values', ['yes', 'no']);
        $form
            ->getField('data')
            ->setOption('label', '')
            ->setType('json')
            ->setOption(
                'form',
                (new Form())
                    ->addField(new Field('text', ['name' => 'key'], ['label' => 'users.fields.key']))
                    ->addField(new Field('text', ['name' => 'value'], ['label' => 'users.fields.value']))
            );
        $form->getField('tfa')->setType('select')->setOption('translate', true)->setOption('values', ['no', 'yes']);
        $groups = $service->getAvailableGroups();

        if ($service->isMaster()) {
            $form
                ->addField(
                    new Field(
                        'checkboxes',
                        [ 'name' => 'grpsp' ],
                        [ 'label' => $this->name . '.columns.groupsp', 'grid' => 4, 'values' => $groups ]
                    )
                );
            $layout[] = ['grpsp'];
        }

        $orig = $service->userOrganizations();
        $orgs = Collection::from($orig)
            ->map(function (array $v) use ($orig) {
                return [
                    'id' => $v['org'],
                    'text' => $v['title'],
                    'parent' => (int)$v['pid'] && isset($orig[$v['pid']]) ? (int)$v['pid'] : '#',
                    'icon' => $v['rgt'] - $v['lft'] > 1 ? 'ui icon cubes' : 'ui icon cube'
                ];
            })
            ->values()
            ->toArray();
        if (count($orgs) > 1) {
            $form
                ->addField(
                    new Field(
                        'tree',
                        [ 'name' => 'org' ],
                        [
                            'label' => '', //$module . '.columns.organization',
                            'values' => $orgs,
                            'multiple' => true,
                            'plugins' => ['checkbox']
                        ]
                    )
                );
            $layout[] = $this->name . '.organization';
            $layout[] = ['org'];
        }

        $layout[] = 'acc:' . $this->name . '.authentication';
        $curr = count($layout);
        $methods = $service->getAuthenticationMethods();
        if (in_array('PasswordDatabase', $methods)) {
            $layout[] = [ 'auth_username', 'auth_password' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_username', 'autocomplete' => 'off' ],
                        [ 'label' => $this->name . '.columns.username' ]
                    )
                )
                ->addField(
                    new Field(
                        'password',
                        [
                            'name' => 'auth_password',
                            'autocomplete' => 'new-password',
                            'placeholder' => 'users.onlyentertochange'
                        ],
                        [ 'label' => $this->name . '.columns.password' ]
                    )
                );
        }
        if (in_array('Certificate', $methods)) {
            $layout[] = [ 'auth_certificate' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_certificate' ],
                        [ 'label' => $this->name . '.columns.auth_certificate' ]
                    )
                );
        }
        if (in_array('CertificateAdvanced', $methods)) {
            $layout[] = [ 'auth_certificate2' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_certificate2' ],
                        [ 'label' => $this->name . '.columns.auth_certificate2' ]
                    )
                );
        }
        if (in_array('LDAP', $methods)) {
            $layout[] = [ 'auth_ldap' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_ldap' ],
                        [ 'label' => $this->name . '.columns.auth_ldap' ]
                    )
                );
        }
        if (in_array('SMTP', $methods)) {
            $layout[] = [ 'auth_smtp' ];
            $form
                ->addField(
                    new Field(
                        'text',
                        [ 'name' => 'auth_smtp' ],
                        [ 'label' => $this->name . '.columns.auth_smtp' ]
                    )
                );
        }
        if (count($layout) === $curr) {
            unset($layout[count($layout) - 1]);
        }

        $form
            ->addField(
                new Field(
                    'select',
                    [ 'name' => 'main_grp' ],
                    [ 'label' => $this->name . '.columns.main_grp', 'values' => $groups ]
                )
            )
            ->addField(
                new Field(
                    'checkboxes',
                    [ 'name' => 'grps' ],
                    [ 'label' => $this->name . '.columns.groups', 'grid' => 4, 'values' => $groups ]
                )
            );
        if ($service->hasCMS()) {
            $layout[] = 'acc:CMS';
            $langs = $service->getAvailableLangs();
            if (count($langs)) {
                $form->addField(
                    new Field(
                        'checkboxes',
                        [ 'name' => 'langs' ],
                        [ 'label' => $this->name . '.columns.langs', 'grid' => 4, 'values' => $langs ]
                    )
                );
                $layout[] = $this->name . '.langs';
                $layout[] = ['langs'];
            }
            $sites = $service->getAvailableSites();
            if (count($sites)) {
                $form->addField(
                    new Field(
                        'checkboxes',
                        [ 'name' => '_sites' ],
                        [ 'label' => $this->name . '.columns.sites', 'grid' => 4, 'values' => $sites ]
                    )
                );
                $layout[] = $this->name . '.sites';
                $layout[] = ['_sites'];
            }
        }
        $form->setLayout($layout);
        if ($form->hasValidator()) {
            $validator = $form->getValidator();
            $validator->required('name', 'required');
            $validator->required('mail', 'required')->mail('mail');
            $validator->remove('avatar_data');
            $form->setValidator($validator);
        }
        if ($form->getContext('type') === 'create') {
            if ($form->hasField('auth_password')) {
                $form->getField('auth_password')->setAttr('placeholder', '');
            }
        }
        $entity = $form->getContext('entity', null);
        if ($entity) {
            $form->populate($service->toArray($entity));
        }
        $form->populate($form->getContext('data', []));
        return $form;
    }
}
