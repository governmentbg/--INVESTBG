INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Password', '{ "minLength" : 8, "doNotMatchUser" : true, "doNotContainUser" : true, "minStrength" : 3, "doNotUseBad" : 2500, "doNotUseSame" : true, "changeEvery" : "10 years", "changeFirst" : true, "allowPlainText" : false }', 1, 0, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Certificate', '{}', 2, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('CertificateAdvanced', '{ "ocsp" : true, "crl" : true, "selfsigned" : false, "roots" : { "identifier" : "-----BEGIN CERTIFICATE----..." } }', 3, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('LDAP', '{ "host" : "127.0.0.1", "base" : "DC=host,DC=ad", "user" : null, "pass" : null, "attr" : "name,mail,userPrincipalName,distinguishedName" } ', 4, 1, '{"ip":["10.0.0.0/16"]}');
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('SMTP', '{ "host" : "127.0.0.1" }', 5, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('StampIT', '{ "callbackUrl" : "...", "public" : "oVGQjtygtjdK067X5limM8Cs5w2yiub0", "private" : "7pdKy3cQp9I3MMCpeq7A49qFRIMfyLCg", "permissions" : "pid,name,mail,organization" }', 6, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('AzureAD', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null, "tenant" : "" }', 7, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Facebook', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 8, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Github', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 9, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Google', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 10, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('LinkedIn', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 11, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('Microsoft', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 12, 1, null);
INSERT INTO authentication (authenticator, settings, position, disabled, conditions) VALUES ('AppleID', '{ "teamID" : "", "keyID" : "", "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 12, 1, null);

INSERT INTO users (name, mail, tfa, disabled, avatar, avatar_data) VALUES ('Администратор', 'admin@local.tld', 0, 0, null, null);

INSERT INTO permissions (perm, created) VALUES ('dashboard/errors', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('errors', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('groups', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('journal', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('log', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('log/viewraw', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('mail', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('authentication', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('maildb', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('modules', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('organization', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('permissions', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('settings', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('translation', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('uploads', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('users', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('pending', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('users/impersonate', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('users/master', CURRENT_TIMESTAMP);
INSERT INTO permissions (perm, created) VALUES ('config', CURRENT_TIMESTAMP);

INSERT INTO organization (lft, rgt, lvl, pid, pos, title) VALUES (1, 2, 0, NULL, 0, 'Корен');

INSERT INTO grps (name, created) VALUES ('Супер администратори', CURRENT_TIMESTAMP);
INSERT INTO grps (name, created) VALUES ('Обикновени', CURRENT_TIMESTAMP);

INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'dashboard/errors', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'errors', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'groups', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'journal', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'log', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'log/viewraw', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'mail', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'maildb', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'authentication', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'organization', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'permissions', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'settings', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'translation', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'uploads', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'users', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'pending', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'modules', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'users/impersonate', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'users/master', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'config', CURRENT_TIMESTAMP);

INSERT INTO user_groups (usr, grp, main, created) VALUES (1, 1, 1, CURRENT_TIMESTAMP);
INSERT INTO user_groups (usr, grp, main, created) VALUES (1, 2, 0, CURRENT_TIMESTAMP);

INSERT INTO user_organizations (usr, org) VALUES (1, 1);

INSERT INTO user_providers (provider, id, usr, name, data, created, used) VALUES ('PasswordDatabase', 'admin', 1, '', '$2y$10$98aIL6pV51r.HlzwIbJ7aeCUL9R8C0CmdtMeIc66VRGk8lA8O8k2.', CURRENT_TIMESTAMP, NULL);

INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('dashboard', 'dashboard', 1, '\webadmin\modules\common\dashboard\DashboardModule', 0);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('users', 'users', 1, '\webadmin\modules\administration\users\UsersModule', 109);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('pending', 'pending', 1, '\webadmin\modules\administration\pending\PendingModule', 110);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('organization', 'organization', 1, '\webadmin\modules\administration\organization\OrganizationModule', 111);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('groups', 'groups', 1, '\webadmin\modules\administration\groups\GroupsModule', 212);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('permissions', 'permissions', 1, '\webadmin\modules\administration\permissions\PermissionsModule', 213);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('translation', 'translation', 1, '\webadmin\modules\administration\translation\TranslationModule', 214);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('mail', 'mail', 0, '\webadmin\modules\administration\mail\MailModule', 215);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('maildb', 'maildb', 1, '\webadmin\modules\administration\maildb\MailDBModule', 216);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('uploads', 'uploads', 1, '\webadmin\modules\administration\uploads\UploadsModule', 917);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('log', 'log', 1, '\webadmin\modules\administration\log\LogModule', 218);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('errors', 'errors', 1, '\webadmin\modules\administration\errors\ErrorsModule', 219);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('config', 'config', 1, '\webadmin\modules\administration\config\ConfigModule', 220);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('authentication', 'authentication', 1, '\webadmin\modules\administration\authentication\AuthenticationModule', 221);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('modules', 'modules', 1, '\webadmin\modules\administration\modules\ModulesModule', 222);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('journal', 'journal', 1, '\webadmin\modules\administration\journal\JournalModule', 108);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('settings', 'settings', 1, '\webadmin\modules\administration\settings\SettingsModule', 224);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('profile', 'profile', 1, '\webadmin\modules\common\profile\ProfileModule', 999);
