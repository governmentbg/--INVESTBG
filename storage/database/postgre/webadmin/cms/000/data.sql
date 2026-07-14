INSERT INTO permissions (perm, created) VALUES ('languages', NOW());
INSERT INTO permissions (perm, created) VALUES ('news', NOW());
INSERT INTO permissions (perm, created) VALUES ('news/publish', NOW());
INSERT INTO permissions (perm, created) VALUES ('news/publish_own', NOW());
INSERT INTO permissions (perm, created) VALUES ('galleries', NOW());
INSERT INTO permissions (perm, created) VALUES ('galleries/publish', NOW());
INSERT INTO permissions (perm, created) VALUES ('galleries/publish_own', NOW());
INSERT INTO permissions (perm, created) VALUES ('pages', NOW());
INSERT INTO permissions (perm, created) VALUES ('pages/permissions', NOW());
INSERT INTO permissions (perm, created) VALUES ('pages/administrative', NOW());
INSERT INTO permissions (perm, created) VALUES ('pages/publish', NOW());
INSERT INTO permissions (perm, created) VALUES ('pages/structure', NOW());
INSERT INTO permissions (perm, created) VALUES ('redirects', NOW());
INSERT INTO permissions (perm, created) VALUES ('sites', NOW());
INSERT INTO permissions (perm, created) VALUES ('users/languages', NOW());
INSERT INTO permissions (perm, created) VALUES ('templates', NOW());
INSERT INTO permissions (perm, created) VALUES ('menus', NOW());
INSERT INTO permissions (perm, created) VALUES ('ptranslation', NOW());

INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'users/languages', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'languages', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'news', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'news/publish', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'news/publish_own', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'galleries', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'galleries/publish', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'galleries/publish_own', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'pages', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'pages/permissions', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'pages/administrative', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'pages/publish', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'pages/structure', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'redirects', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'sites', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'templates', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'menus', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'ptranslation', NOW());

INSERT INTO tree_struct (lft, rgt, lvl, pid, pos) VALUES (1, 6, 0, NULL, 0);
INSERT INTO tree_struct (lft, rgt, lvl, pid, pos) VALUES (2, 5, 1, 1, 0);
INSERT INTO tree_struct (lft, rgt, lvl, pid, pos) VALUES (3, 4, 2, 2, 0);

INSERT INTO sites (name, domains, tree, disabled, dflt) VALUES ('localhost', 'localhost', 3, 0, 1);

INSERT INTO languages (code, name, local) VALUES ('bg', 'Български', 'Български');
INSERT INTO languages (code, name, local) VALUES ('en', 'English', 'Английски');

INSERT INTO site_domain (site, domain) VALUES (1, 'localhost');

INSERT INTO site_lang (site, lang) VALUES (1, 1);
INSERT INTO site_lang (site, lang) VALUES (1, 2);

INSERT INTO templates (name, base, is_default) VALUES ('Страница', 'page', 1);
INSERT INTO templates (name, base, is_default) VALUES ('Начало', 'homepage', 0);
INSERT INTO templates (name, base, is_default) VALUES ('Търсене', 'searchpage', 0);
INSERT INTO templates (name, base, is_default) VALUES ('Новини', 'news', 0);
INSERT INTO templates (name, base, is_default) VALUES ('Галерии', 'gallery', 0);

INSERT INTO tree_data (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (1, 1, 1, NULL, NOW(), 1, 'Сайтове', 0, 'bgsitesroot', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (1, 2, 1, NULL, NOW(), 1, 'Sites', 0, 'ensitesroot', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (2, 1, 1, NULL, NOW(), 1, 'localhost', 0, '', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (2, 2, 1, NULL, NOW(), 1, 'localhost', 0, '', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (3, 1, 1, NULL, NOW(), 1, 'Начало', 0, '', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (3, 2, 1, NULL, NOW(), 1, 'Home', 0, 'en', '', '{}', '{}', NULL, 1, NULL, 1);

INSERT INTO tree_data_pub (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (1, 1, 1, NULL, NOW(), 1, 'Сайтове', 0, 'bgsitesroot', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data_pub (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (1, 2, 1, NULL, NOW(), 1, 'Sites', 0, 'ensitesroot', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data_pub (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (2, 1, 1, NULL, NOW(), 1, 'localhost', 0, '', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data_pub (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (2, 2, 1, NULL, NOW(), 1, 'localhost', 0, '', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data_pub (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (3, 1, 1, NULL, NOW(), 1, 'Начало', 0, '', '', '{}', '{}', NULL, 1, NULL, 1);
INSERT INTO tree_data_pub (id, lang, version, from_version, created, usr, title, hidden, url, redirect, settings, content, permissions, template, menu, published) VALUES (3, 2, 1, NULL, NOW(), 1, 'Home', 0, 'en', '', '{}', '{}', NULL, 1, NULL, 1);

INSERT INTO user_lang (usr, lang) VALUES (1, 1);
INSERT INTO user_lang (usr, lang) VALUES (1, 2);

INSERT INTO user_site (usr, site) VALUES (1, 1);

INSERT INTO menus (name, site, lang, is_default, items) VALUES ('Основно меню', 1, 1, 1, '[{"type":"1","name":"","href":"","depth":""}]');
INSERT INTO menus (name, site, lang, is_default, items) VALUES ('Main menu', 1, 2, 1, '[{"type":"1","name":"","href":"","depth":""}]');

INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('pages', 'pages', 1, '\webadmin\modules\site\pages\PagesModule', 1);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('news', 'news', 1, '\webadmin\modules\site\news\NewsModule', 2);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('galleries', 'galleries', 1, '\webadmin\modules\site\galleries\GalleriesModule', 3);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('menus', 'menus', 1, '\webadmin\modules\site\menus\MenusModule', 4);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('templates', 'templates', 1, '\webadmin\modules\site\templates\TemplatesModule', 5);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('redirects', 'redirects', 1, '\webadmin\modules\site\redirects\RedirectsModule', 6);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('ptranslation', 'ptranslation', 1, '\webadmin\modules\site\translation\TranslationModule', 6);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('languages', 'languages', 1, '\webadmin\modules\site\languages\LanguagesModule', 7);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('sites', 'sites', 1, '\webadmin\modules\site\sites\SitesModule', 8);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('tags', 'tags', 1, '\webadmin\modules\site\tags\TagsModule', 9);
