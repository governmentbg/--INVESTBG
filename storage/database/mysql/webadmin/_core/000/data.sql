/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (1, 'Password', '{ "minLength" : 8, "doNotMatchUser" : true, "doNotContainUser" : true, "minStrength" : 3, "doNotUseBad" : 2500, "doNotUseSame" : true, "changeEvery" : "10 years", "changeFirst" : true, "allowPlainText" : false }', 1, 0, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (2, 'Certificate', '{}', 2, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (3, 'CertificateAdvanced', '{ "ocsp" : true, "crl" : true, "selfsigned" : false, "roots" : { "identifier" : "-----BEGIN CERTIFICATE----..." } }', 3, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (4, 'LDAP', '{ "host" : "127.0.0.1", "base" : "DC=host,DC=ad", "user" : null, "pass" : null, "attr" : "name,mail,userPrincipalName,distinguishedName" } ', 4, 1, '{"ip":["10.0.0.0/16"]}');
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (5, 'SMTP', '{ "host" : "127.0.0.1" }', 5, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (6, 'StampIT', '{ "callbackUrl" : "...", "public" : "oVGQjtygtjdK067X5limM8Cs5w2yiub0", "private" : "7pdKy3cQp9I3MMCpeq7A49qFRIMfyLCg", "permissions" : "pid,name,mail,organization" }', 6, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (7, 'AzureAD', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null, "tenant" : "" }', 7, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (8, 'Facebook', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 8, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (9, 'Github', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 9, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (10, 'Google', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 10, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (11, 'LinkedIn', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 11, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (12, 'Microsoft', '{ "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 12, 1, null);
INSERT INTO `authentication` (`authentication`, `authenticator`, `settings`, `position`, `disabled`, `conditions`) VALUES (13, 'AppleID', '{ "teamID" : "", "keyID" : "", "callbackUrl" : "...", "public" : "...", "private" : "...", "permissions" : null }', 12, 1, null);

INSERT INTO `users` (`usr`, `name`, `mail`, `tfa`, `disabled`, `avatar`, `avatar_data`, `data`) VALUES (1, 'Администратор', 'admin@local.tld', 0, 0, null, null, '');

INSERT INTO `permissions` (`perm`, `created`) VALUES ('dashboard/errors', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('modules', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('errors', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('groups', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('journal', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('log', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('log/viewraw', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('mail', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('maildb', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('authentication', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('organization', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('permissions', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('settings', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('translation', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('uploads', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('users', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('pending', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('users/impersonate', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('users/master', NOW());
INSERT INTO `permissions` (`perm`, `created`) VALUES ('config', NOW());

INSERT INTO `organization` (`org`, `lft`, `rgt`, `lvl`, `pid`, `pos`, `title`) VALUES (1, 1, 2, 0, NULL, 0, 'Корен');

INSERT INTO `grps` (`grp`, `name`, `created`) VALUES (1, 'Супер администратори', NOW());
INSERT INTO `grps` (`grp`, `name`, `created`) VALUES (2, 'Обикновени', NOW());

INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'dashboard/errors', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'errors', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'groups', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'journal', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'log', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'log/viewraw', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'mail', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'maildb', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'authentication', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'organization', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'permissions', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'settings', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'translation', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'uploads', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'users', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'pending', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'modules', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'users/impersonate', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'users/master', NOW());
INSERT INTO `group_permissions` (`grp`, `perm`, `created`) VALUES (1, 'config', NOW());

INSERT INTO `user_groups` (`usr`, `grp`, `main`, `created`) VALUES (1, 1, 1, NOW());
INSERT INTO `user_groups` (`usr`, `grp`, `main`, `created`) VALUES (1, 2, 0, NOW());

INSERT INTO `user_organizations` (`usr`, `org`) VALUES (1, 1);

INSERT INTO `user_providers` (`provider`, `id`, `usr`, `name`, `data`, `created`, `used`) VALUES ('PasswordDatabase', 'admin', 1, '', '$2y$10$98aIL6pV51r.HlzwIbJ7aeCUL9R8C0CmdtMeIc66VRGk8lA8O8k2.', NOW(), NULL);

INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('dashboard', 'dashboard', 1, '\\webadmin\\modules\\common\\dashboard\\DashboardModule', 0);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('users', 'users', 1, '\\webadmin\\modules\\administration\\users\\UsersModule', 109);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('pending', 'pending', 1, '\\webadmin\\modules\\administration\\pending\\PendingModule', 110);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('organization', 'organization', 1, '\\webadmin\\modules\\administration\\organization\\OrganizationModule', 111);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('groups', 'groups', 1, '\\webadmin\\modules\\administration\\groups\\GroupsModule', 212);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('permissions', 'permissions', 1, '\\webadmin\\modules\\administration\\permissions\\PermissionsModule', 213);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('translation', 'translation', 1, '\\webadmin\\modules\\administration\\translation\\TranslationModule', 214);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('mail', 'mail', 0, '\\webadmin\\modules\\administration\\mail\\MailModule', 215);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('maildb', 'maildb', 1, '\\webadmin\\modules\\administration\\maildb\\MailDBModule', 216);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('uploads', 'uploads', 1, '\\webadmin\\modules\\administration\\uploads\\UploadsModule', 917);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('log', 'log', 1, '\\webadmin\\modules\\administration\\log\\LogModule', 218);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('errors', 'errors', 1, '\\webadmin\\modules\\administration\\errors\\ErrorsModule', 219);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('config', 'config', 1, '\\webadmin\\modules\\administration\\config\\ConfigModule', 220);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('authentication', 'authentication', 1, '\\webadmin\\modules\\administration\\authentication\\AuthenticationModule', 221);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('modules', 'modules', 1, '\\webadmin\\modules\\administration\\modules\\ModulesModule', 222);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('journal', 'journal', 1, '\\webadmin\\modules\\administration\\journal\\JournalModule', 123);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('settings', 'settings', 1, '\\webadmin\\modules\\administration\\settings\\SettingsModule', 224);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('profile', 'profile', 1, '\\webadmin\\modules\\common\\profile\\ProfileModule', 999);

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
