INSERT INTO permissions (perm, created) VALUES ('help', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'help', CURRENT_TIMESTAMP);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('help', 'help', 1, '\webadmin\modules\common\help\HelpModule', 23);
