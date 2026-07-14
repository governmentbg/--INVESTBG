<?php

declare(strict_types=1);

namespace webadmin\modules\administration\users;

use schema\UserGroupsProvisionalEntity;
use schema\UsersEntity;
use webadmin\modules\common\crud\CRUDException;
use vakata\database\DBInterface;
use vakata\user\User;
use vakata\user\UserManagementInterface;
use vakata\user\Provider;
use vakata\authentication\AuthenticationInterface;
use vakata\authentication\Manager;
use webadmin\modules\common\crud\CRUDServiceVersioned;
use vakata\authentication\password\PasswordDatabase;
use vakata\config\Config;
use vakata\database\schema\Entity;
use vakata\database\schema\TableQueryMapped;
use vakata\user\GroupInterface;

/**
 * @extends CRUDServiceVersioned<\schema\UsersEntity>
 */
class UsersService extends CRUDServiceVersioned
{
    protected UserManagementInterface $usrm;
    protected AuthenticationInterface $auth;
    protected ?string $vtable = 'versions';
    protected bool $cms = false;
    protected int $superadmin = 0;

    public function __construct(
        UsersModule $module,
        UserManagementInterface $usrm,
        AuthenticationInterface $auth,
        Config $config,
        DBInterface $db,
        User $user
    ) {
        parent::__construct($module, $db, $user);
        $this->usrm = $usrm;
        $this->auth = $auth;
        $this->cms = $config->get('FEATURE_CMS');
        $this->superadmin = (int)$config->get('GROUP_ADMINS');
    }

    protected function entities(): TableQueryMapped
    {
        $repository = $this->db->entities(\schema\UsersEntity::class)
            ->with('user_groups.grps');
        if (!$this->user->hasPermission('users/master')) {
            $repository->filter(
                'organization.org',
                array_merge(['' => 0], array_keys($this->user->organization))
            );
            $repository->filter('usr', $this->user->getID(), true);
        }
        return $repository;
    }
    public function listQuery(): TableQueryMapped
    {
        return parent::listQuery()
            ->limitOnMainTable(true)
            ->sort('name')
            ->columns([ 'name', 'mail', 'grps.name' ]);
    }
    public function isMaster(): bool
    {
        return $this->user->hasPermission('users/master');
    }
    public function canImpersonate(UsersEntity $user): bool
    {
        return $this->user->hasPermission('users/impersonate') &&
            !$user->disabled &&
            (int)$user->usr !== (int)$this->user->getID();
    }
    public function getAvailableGroups(): array
    {
        $groups = [];
        if ($this->user->hasPermission('users/master')) {
            foreach ($this->usrm->groups() as $group) {
                $groups[(int)$group->getID()] = $group->getName();
            }
        } else {
            foreach ($this->user->getGroups() as $group) {
                $groups[$group->getID()] = $group->getName();
            }
            foreach (
                $this->db->col(
                    "SELECT grp FROM user_groups_provisional WHERE usr = ?",
                    [$this->user->getID()]
                ) as $grp
            ) {
                try {
                    $temp = $this->usrm->getGroup((string)$grp);
                    $groups[$temp->getID()] = $temp->getName();
                } catch (\Exception) {
                }
            }
        }
        if (
            $this->superadmin &&
            isset($groups[$this->superadmin]) &&
            !$this->user->inGroup((string)$this->superadmin)
        ) {
            unset($groups[$this->superadmin]);
        }
        return $groups;
    }
    public function getAvailableLangs(): array
    {
        return $this->user->hasPermission('users/languages') ?
            $this->db->rows("SELECT l.lang, l.local FROM languages l ORDER BY l.local")->toArray('lang', 'local') :
            $this->user->languages;
    }
    public function getAvailableSites(): array
    {
        return $this->db->rows("SELECT site, name FROM sites ORDER BY name")->toArray('site', 'name');
    }
    public function getAuthenticationMethods(): array
    {
        if ($this->auth instanceof Manager) {
            $methods = [];
            foreach ($this->auth->getProviders(true) as $provider) {
                $methods[] = (new \ReflectionClass($provider))->getShortName();
            }
            return $methods;
        }
        return [ (new \ReflectionClass($this->auth))->getShortName() ];
    }
    public function getAuthenticationMethod(string $name): ?AuthenticationInterface
    {
        if ($this->auth instanceof Manager) {
            foreach ($this->auth->getProviders(true) as $provider) {
                if ((new \ReflectionClass($provider))->getShortName() === $name) {
                    return $provider;
                }
            }
            return null;
        }
        if ((new \ReflectionClass($this->auth))->getShortName() === $name) {
            return $this->auth;
        }
        return null;
    }

    public function create(array $data = []): Entity
    {
        $user = null;
        // if (!isset($data['nusername']) || !strlen($data['nusername'])) {
        //     throw new CRUDException('modules.users.username.required');
        // }
        // if (!isset($data['npassword']) || !strlen($data['npassword'])) {
        //     throw new CRUDException('modules.users.password.required');
        // }
        $methods = $this->getAuthenticationMethods();
        if (in_array('PasswordDatabase', $methods) && isset($data['auth_username']) && strlen($data['auth_username'])) {
            try {
                $user = $this->usrm->getUserByProviderID('PasswordDatabase', $data['auth_username']);
            } catch (\Exception) {
            }
            if ($user) {
                throw new CRUDException('modules.users.duplicateusername');
            }
        }
        if (
            in_array('Certificate', $methods) &&
            isset($data['auth_certificate']) &&
            strlen($data['auth_certificate'])
        ) {
            try {
                $user = $this->usrm->getUserByProviderID('Certificate', $data['auth_certificate']);
            } catch (\Exception) {
            }
            if ($user) {
                throw new CRUDException('modules.users.duplicatecertificate');
            }
        }
        if (
            in_array('CertificateAdvanced', $methods) &&
            isset($data['auth_certificate2']) &&
            strlen($data['auth_certificate2'])
        ) {
            try {
                $user = $this->usrm->getUserByProviderID('CertificateAdvanced', $data['auth_certificate2']);
            } catch (\Exception) {
            }
            if ($user) {
                throw new CRUDException('modules.users.duplicatecertificate');
            }
        }
        if (in_array('LDAP', $methods) && isset($data['auth_ldap']) && strlen($data['auth_ldap'])) {
            try {
                $user = $this->usrm->getUserByProviderID('LDAP', $data['auth_ldap']);
            } catch (\Exception) {
            }
            if ($user) {
                throw new CRUDException('modules.users.duplicateusername');
            }
        }
        if (in_array('SMTP', $methods) && isset($data['auth_smtp']) && strlen($data['auth_smtp'])) {
            try {
                $user = $this->usrm->getUserByProviderID('SMTP', $data['auth_smtp']);
            } catch (\Exception) {
            }
            if ($user) {
                throw new CRUDException('modules.users.duplicateusername');
            }
        }

        $entity = parent::create($data);

        // get the user instance
        $user = $this->usrm->getUser((string)$entity->usr);

        // add password (if applicable)
        if (
            in_array('PasswordDatabase', $methods) &&
            isset($data['auth_username']) &&
            strlen($data['auth_username']) &&
            isset($data['auth_password']) &&
            strlen($data['auth_password'])
        ) {
            $temp = $this->getAuthenticationMethod('PasswordDatabase');
            if ($temp instanceof PasswordDatabase) {
                $data['auth_password'] = $temp->hash($data['auth_password']);
            }
            $user->addProvider(new Provider('PasswordDatabase', $data['auth_username'], '', $data['auth_password']));
        }
        // add certificate (if applicable)
        if (
            in_array('Certificate', $methods) &&
            isset($data['auth_certificate']) &&
            strlen($data['auth_certificate'])
        ) {
            $user->addProvider(new Provider('Certificate', $data['auth_certificate']));
        }
        if (
            in_array('CertificateAdvanced', $methods) &&
            isset($data['auth_certificate2']) &&
            strlen($data['auth_certificate2'])
        ) {
            $user->addProvider(new Provider('CertificateAdvanced', $data['auth_certificate2']));
        }
        // add AD user (if applicable)
        if (in_array('LDAP', $methods) && isset($data['auth_ldap']) && strlen($data['auth_ldap'])) {
            $user->addProvider(new Provider('LDAP', $data['auth_ldap']));
        }
        // add SMTP server user (if applicable)
        if (in_array('SMTP', $methods) && isset($data['auth_smtp']) && strlen($data['auth_smtp'])) {
            $user->addProvider(new Provider('SMTP', $data['auth_smtp']));
        }
        // handle groups
        if (!isset($data['grps']) || !$data['grps'] || !is_array($data['grps'])) {
            $data['grps'] = [];
        }
        if (isset($data['main_grp'])) {
            array_unshift($data['grps'], $data['main_grp']);
        }
        $data['grps'] = array_unique($data['grps']);
        $available = $this->getAvailableGroups();
        foreach ($data['grps'] as $grp) {
            // only add to groups that are available to the current user (the creator)
            if (isset($available[(int)$grp])) {
                $user->addGroup($this->usrm->getGroup((string)$grp));
            }
        }
        if (count($data['grps'])) {
            $user->setPrimaryGroup($this->usrm->getGroup((string)array_values($data['grps'])[0]));
        }
        // persist all changes
        $this->usrm->saveUser($user);
        // provisional groups
        if (!isset($data['grpsp']) || !$data['grpsp'] || !is_array($data['grpsp'])) {
            $data['grpsp'] = [];
        }
        $data['grpsp'] = array_unique($data['grpsp']);
        foreach ($data['grpsp'] as $grp) {
            // only add to groups that are available to the current user (the creator)
            if (isset($available[(int)$grp])) {
                $this->db->table('user_groups_provisional')->insert([
                    'usr' => $user->getID(),
                    'grp' => (int)$grp,
                    'created' => date('Y-m-d H:i:s')
                ]);
            }
        }
        // add user organizations
        if (!isset($data['org'])) {
            $data['org'] = [];
        }
        // the current user's (creator) organizations
        $org = array_keys($this->user->organization);
        $org[] = 0;
        // if the user is not a master - remove all organizations not in his personal list
        if (!$this->user->hasPermission('users/master')) {
            foreach ($data['org'] as $k => $v) {
                if (!in_array($v, $org) || !(int)$v) {
                    unset($data['org'][$k]);
                }
            }
        }
        if (count(array_keys($this->user->organization)) === 1) {
            $data['org'] = [array_keys($this->user->organization)[0]];
        }
        if (count($data['org'])) {
            $nodes = $this->db->rows(
                "SELECT * FROM organization WHERE org IN (??) ORDER BY lft",
                [$data['org']]
            )
            ->toArray('org');
            // only save top level nodes
            $new = [];
            foreach ($nodes as $k => $v) {
                if (!isset($nodes[(int)$v['pid']])) {
                    $new[] = $k;
                }
            }
            foreach ($new as $v) {
                $this->db->table('user_organizations')->insert([
                    'usr' => $user->getID(),
                    'org' => (int)$v
                ]);
            }
        }
        if (!isset($data['langs']) || !is_array($data['langs'])) {
            $data['langs'] = [];
        }
        if ($this->cms) {
            if (count($data['langs'])) {
                $available = $this->getAvailableLangs();
                foreach ($data['langs'] as $lang) {
                    if (isset($available[$lang])) {
                        $this->db->table('user_lang')->insert([
                            'usr' => $user->getID(),
                            'lang' => $lang
                        ]);
                    }
                }
            }
            if (!isset($data['_sites']) || !is_array($data['_sites'])) {
                $data['_sites'] = [];
            }
            if (count($data['_sites'])) {
                $available = $this->getAvailableSites();
                foreach ($data['_sites'] as $site) {
                    if (isset($available[$site])) {
                        $this->db->table('user_site')->insert([
                            'usr' => $user->getID(),
                            'site' => $site
                        ]);
                    }
                }
            }
        }
        $this->journal('Създаване на потребител', 'info', $entity->usr);
        $this->version($entity, 0, true);
        return $entity;
    }
    public function update(mixed $id, array $data = []): Entity
    {
        $entity = parent::update($id, $data);
        $entity = $this->read($id);
        $methods = $this->getAuthenticationMethods();
        if (in_array('PasswordDatabase', $methods) && isset($data['auth_username']) && strlen($data['auth_username'])) {
            $user = null;
            try {
                $user = $this->usrm->getUserByProviderID('PasswordDatabase', $data['auth_username']);
            } catch (\Exception) {
            }
            if ($user && (int)$user->getID() !== (int)$entity->usr) {
                throw new CRUDException('modules.users.duplicateusername');
            }
        }
        if (
            in_array('Certificate', $methods) &&
            isset($data['auth_certificate']) &&
            strlen($data['auth_certificate'])
        ) {
            $user = null;
            try {
                $user = $this->usrm->getUserByProviderID('Certificate', $data['auth_certificate']);
            } catch (\Exception) {
            }
            if ($user && (int)$user->getID() !== (int)$entity->usr) {
                throw new CRUDException('modules.users.duplicatecertificate');
            }
        }
        if (
            in_array('CertificateAdvanced', $methods) &&
            isset($data['auth_certificate2']) &&
            strlen($data['auth_certificate2'])
        ) {
            $user = null;
            try {
                $user = $this->usrm->getUserByProviderID('CertificateAdvanced', $data['auth_certificate2']);
            } catch (\Exception) {
            }
            if ($user && (int)$user->getID() !== (int)$entity->usr) {
                throw new CRUDException('modules.users.duplicatecertificate');
            }
        }
        if (in_array('LDAP', $methods) && isset($data['auth_ldap']) && strlen($data['auth_ldap'])) {
            $user = null;
            try {
                $user = $this->usrm->getUserByProviderID('LDAP', $data['auth_ldap']);
            } catch (\Exception) {
            }
            if ($user && (int)$user->getID() !== (int)$entity->usr) {
                throw new CRUDException('modules.users.duplicateusername');
            }
        }
        if (in_array('SMTP', $methods) && isset($data['auth_smtp']) && strlen($data['auth_smtp'])) {
            $user = null;
            try {
                $user = $this->usrm->getUserByProviderID('SMTP', $data['auth_smtp']);
            } catch (\Exception) {
            }
            if ($user && (int)$user->getID() !== (int)$entity->usr) {
                throw new CRUDException('modules.users.duplicateusername');
            }
        }

        // get the user instance
        $user = $this->usrm->getUser((string)$entity->usr);
        // populate all fields
        // otherwise if the user was loaded in memory beforehand it will not have the updated fields
        foreach ($data as $k => $v) {
            if ($user->get((string)$k, chr(0)) !== chr(0) || !is_null($v)) {
                $user->set((string)$k, $v);
            }
        }
        // change password if needed
        if (in_array('PasswordDatabase', $methods)) {
            $password = array_values(array_filter($user->getProviders(), function ($provider) {
                return $provider->getProvider() === 'PasswordDatabase' && $provider->enabled();
            }))[0] ?? null;
            $used = $password && (!isset($data['auth_password']) || !strlen($data['auth_password'])) ?
                $password->getUsed() : null;
            $hash = true;
            if (isset($data['auth_username']) && strlen($data['auth_username'])) {
                if ($password && $password->getID() !== $data['auth_username']) {
                    if (!isset($data['auth_password']) || !strlen($data['auth_password'])) {
                        $data['auth_password'] = $password->getData();
                        $hash = false;
                    }
                    $user->deleteProvider($password);
                    $password = null;
                }
                if (isset($data['auth_password']) && strlen($data['auth_password'])) {
                    $temp = $this->getAuthenticationMethod('PasswordDatabase');
                    if ($hash && $temp instanceof PasswordDatabase) {
                        $data['auth_password'] = $temp->hash($data['auth_password']);
                    }
                    if (!$password) {
                        $password = new Provider(
                            'PasswordDatabase',
                            $data['auth_username'],
                            '',
                            $data['auth_password'],
                            'now',
                            $used && (int)$used ? date('Y-m-d H:i:s', (int)$used) : null
                        );
                        $user->addProvider($password);
                    } else {
                        $password->setData($data['auth_password']);
                        if ($used && (int)$used) {
                            $password->setUsed(date('Y-m-d H:i:s', (int)$used));
                        }
                    }
                }
            } else {
                if ($password) {
                    $user->deleteProvider($password);
                }
            }
        }
        // add certificate (if applicable)
        if (in_array('Certificate', $methods)) {
            $provider = array_values(array_filter($user->getProviders(), function ($provider) {
                return $provider->getProvider() === 'Certificate' && $provider->enabled();
            }))[0] ?? null;
            if (isset($data['auth_certificate']) && strlen($data['auth_certificate'])) {
                if (!$provider || $data['auth_certificate'] !== $provider->getID()) {
                    if ($provider) {
                        $user->deleteProvider($provider);
                    }
                    $user->addProvider(new Provider('Certificate', $data['auth_certificate']));
                }
            } else {
                if ($provider) {
                    $user->deleteProvider($provider);
                }
            }
        }
        if (in_array('CertificateAdvanced', $methods)) {
            $provider = array_values(array_filter($user->getProviders(), function ($provider) {
                return $provider->getProvider() === 'CertificateAdvanced' && $provider->enabled();
            }))[0] ?? null;
            if (isset($data['auth_certificate2']) && strlen($data['auth_certificate2'])) {
                if (!$provider || $data['auth_certificate2'] !== $provider->getID()) {
                    if ($provider) {
                        $user->deleteProvider($provider);
                    }
                    $user->addProvider(new Provider('CertificateAdvanced', $data['auth_certificate2']));
                }
            } else {
                if ($provider) {
                    $user->deleteProvider($provider);
                }
            }
        }
        // add certificate (if applicable)
        if (in_array('LDAP', $methods)) {
            $provider = array_values(array_filter($user->getProviders(), function ($provider) {
                return $provider->getProvider() === 'LDAP' && $provider->enabled();
            }))[0] ?? null;
            if (isset($data['auth_ldap']) && strlen($data['auth_ldap'])) {
                if (!$provider || $data['auth_ldap'] !== $provider->getID()) {
                    if ($provider) {
                        $user->deleteProvider($provider);
                    }
                    $user->addProvider(new Provider('LDAP', $data['auth_ldap']));
                }
            } else {
                if ($provider) {
                    $user->deleteProvider($provider);
                }
            }
        }
        // add certificate (if applicable)
        if (in_array('SMTP', $methods)) {
            $provider = array_values(array_filter($user->getProviders(), function ($provider) {
                return $provider->getProvider() === 'SMTP' && $provider->enabled();
            }))[0] ?? null;
            if (isset($data['auth_smtp']) && strlen($data['auth_smtp'])) {
                if (!$provider || $data['auth_smtp'] !== $provider->getID()) {
                    if ($provider) {
                        $user->deleteProvider($provider);
                    }
                    $user->addProvider(new Provider('SMTP', $data['auth_smtp']));
                }
            } else {
                if ($provider) {
                    $user->deleteProvider($provider);
                }
            }
        }
        // handle groups
        if (!isset($data['grps']) || !$data['grps'] || !is_array($data['grps'])) {
            $data['grps'] = [];
        }
        if (isset($data['main_grp'])) {
            array_unshift($data['grps'], $data['main_grp']);
        }
        $data['grps'] = array_unique($data['grps']);
        $available = $this->getAvailableGroups();
        foreach ($user->getGroups() as $group) {
            // only remove groups that are available to the currently editing user
            if (!in_array($group->getID(), $data['grps']) && isset($available[(int)$group->getID()])) {
                $user->deleteGroup($group);
            }
        }
        foreach ($data['grps'] as $grp) {
            // only add to groups that are available to the current user (the creator)
            if (isset($available[(int)$grp]) && !$user->inGroup((string)$grp)) {
                $user->addGroup($this->usrm->getGroup((string)$grp));
            }
        }
        if (count($data['grps'])) {
            $user->setPrimaryGroup($this->usrm->getGroup((string)array_values($data['grps'])[0]));
        }
        // persist all changes
        $this->usrm->saveUser($user);
        // handle groups provisional
        if (!isset($data['grpsp']) || !$data['grpsp'] || !is_array($data['grpsp'])) {
            $data['grpsp'] = [];
        }
        $data['grpsp'] = array_unique($data['grpsp']);
        foreach ($data['grpsp'] as $group) {
            // only remove groups that are available to the currently editing user
            if (!in_array($group, $data['grpsp']) && isset($available[(int)$group])) {
                $this->db->query(
                    "DELETE FROM user_groups_provisional WHERE usr = ? AND grp = ?",
                    [ $entity->usr, $group ]
                );
            }
        }
        $pgrps = $entity->user_groups_provisional
            ->clone()
            ->map(function (UserGroupsProvisionalEntity $v): int {
                return $v->grp;
            })
            ->toArray();
        foreach ($data['grpsp'] as $grp) {
            // only add to groups that are available to the current user (the creator)
            if (isset($available[(int)$grp]) && !in_array($grp, $pgrps)) {
                $this->db->query(
                    "INSERT INTO user_groups_provisional (usr, grp, created) VALUES (?, ?, ?)",
                    [ $entity->usr, $grp, date('Y-m-d H:i:s') ]
                );
            }
        }
        // add user organizations
        if (!isset($data['org'])) {
            $data['org'] = [];
        }
        // the current user's (creator) organizations
        $org = array_keys($this->user->organization);
        $org[] = 0;
        if (!$this->user->hasPermission('users/master')) {
            // if the user is not a master - remove all organizations not in his personal list
            foreach ($data['org'] as $k => $v) {
                if (!in_array($v, $org) || !(int)$v) {
                    unset($data['org'][$k]);
                }
            }
            // clean all organizations the editor has access to
            $this->db->table('user_organizations')
                ->filter('usr', $user->getID())
                ->filter('org', $org)
                ->delete();
        } else {
            $this->db->table('user_organizations')
                ->filter('usr', $user->getID())
                ->delete();
        }
        if (count(array_keys($this->user->organization)) === 1) {
            $data['org'] = [array_keys($this->user->organization)[0]];
        }
        if (count($data['org'])) {
            $nodes = $this->db->rows(
                "SELECT * FROM organization WHERE org IN (??) ORDER BY lft",
                [$data['org']]
            )
            ->toArray('org');
            // only save top level nodes
            $new = [];
            foreach ($nodes as $k => $v) {
                if (!isset($nodes[(int)$v['pid']])) {
                    $new[] = $k;
                }
            }
            foreach ($new as $v) {
                $this->db->table('user_organizations')->insert([
                    'usr' => $user->getID(),
                    'org' => (int)$v
                ]);
            }
        }
        if (!isset($data['langs']) || !is_array($data['langs'])) {
            $data['langs'] = [];
        }
        if ($this->cms) {
            $available = $this->getAvailableLangs();
            foreach ($available as $k => $v) {
                if (!in_array($k, $data['langs'])) {
                    $this->db->table('user_lang')
                        ->filter('usr', $user->getID())
                        ->filter('lang', $k)
                        ->delete();
                }
            }
            foreach ($data['langs'] as $lang) {
                if (
                    isset($available[$lang]) &&
                    !$this->db->val("SELECT usr FROM user_lang WHERE usr = ? AND lang = ?", [ $user->getID(), $lang ])
                ) {
                    $this->db->table('user_lang')->insert([
                        'usr' => $user->getID(),
                        'lang' => $lang
                    ]);
                }
            }
            if (!isset($data['_sites']) || !is_array($data['_sites'])) {
                $data['_sites'] = [];
            }
            $available = $this->getAvailableSites();
            foreach ($available as $k => $v) {
                if (!in_array($k, $data['_sites'])) {
                    $this->db->table('user_site')
                        ->filter('usr', $user->getID())
                        ->filter('site', $k)
                        ->delete();
                }
            }
            foreach ($data['_sites'] as $site) {
                if (
                    isset($available[$site]) &&
                    !$this->db->val("SELECT usr FROM user_site WHERE usr = ? AND site = ?", [ $user->getID(), $site ])
                ) {
                    $this->db->table('user_site')->insert([
                        'usr' => $user->getID(),
                        'site' => $site
                    ]);
                }
            }
        }
        $this->journal('Редакция на потребител', 'info', $entity->usr);
        $this->version($entity, 1, true);
        return $entity;
    }
    public function toArray(Entity $entity, bool $relations = false): array
    {
        $arr = parent::toArray($entity, $relations);
        $user = $this->usrm->getUser((string)$entity->usr);
        $arr['main_grp'] = $user->getPrimaryGroup()?->getID();
        $arr['grps'] = array_filter(
            array_map(
                function (GroupInterface $v): mixed {
                    return $v->getID();
                },
                $user->getGroups()
            ),
            function ($v) use ($arr) {
                return (int)$v !== (int)$arr['main_grp'];
            }
        );
        $arr['grpsp'] = $this->db->col("SELECT grp FROM user_groups_provisional WHERE usr = ?", [$entity->usr]);
        $methods = $this->getAuthenticationMethods();
        foreach ($user->getProviders() as $provider) {
            if (
                in_array('PasswordDatabase', $methods) &&
                $provider->getProvider() === 'PasswordDatabase' &&
                $provider->enabled()
            ) {
                $arr['auth_username'] = $provider->getID();
            }
            if (
                in_array('Certificate', $methods) &&
                $provider->getProvider() === 'Certificate' &&
                $provider->enabled()
            ) {
                $arr['auth_certificate'] = $provider->getID();
            }
            if (
                in_array('CertificateAdvanced', $methods) &&
                $provider->getProvider() === 'CertificateAdvanced' &&
                $provider->enabled()
            ) {
                $arr['auth_certificate2'] = $provider->getID();
            }
            if (in_array('LDAP', $methods) && $provider->getProvider() === 'LDAP' && $provider->enabled()) {
                $arr['auth_ldap'] = $provider->getID();
            }
            if (in_array('SMTP', $methods) && $provider->getProvider() === 'SMTP' && $provider->enabled()) {
                $arr['auth_smtp'] = $provider->getID();
            }
        }
        $temp = [];
        foreach ($entity->organization as $o) {
            /** @psalm-suppress PossiblyNullPropertyFetch */
            $temp[] = $o->org;
        }
        $arr['org'] = $temp;
        if ($this->cms) {
            $arr['langs'] = $this->db->col("SELECT lang FROM user_lang WHERE usr = ?", [$entity->usr]);
            $arr['_sites'] = $this->db->col("SELECT site FROM user_site WHERE usr = ?", [$entity->usr]);
        }
        return $arr;
    }
    public function delete(mixed $id): void
    {
        throw new \Exception('Not allowed', 400);
    }
    public function userOrganizations(): array
    {
        return $this->user->organization;
    }
    public function hasCMS(): bool
    {
        return $this->cms;
    }
    public function kick(string $id): void
    {
        $user = $this->usrm->getUser($id);
        $user->set('sessions', null);
        $this->usrm->saveUser($user);
    }

    public function journal(string $message, string $lvl, int $id, array $context = []): void
    {
        $this->db->table('log_system')->insert([
            'module'  => $this->module->getName(),
            'module_id'  => $id,
            'message' => $message,
            'lvl' => $lvl,
            'context' => json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'created' => date('Y-m-d H:i:s'),
            'usr' => $this->user->getID()
        ]);
    }
}
