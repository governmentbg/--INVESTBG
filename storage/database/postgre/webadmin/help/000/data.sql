INSERT INTO permissions (perm, created) VALUES ('help', NOW());
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'help', NOW());
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('help', 'help', 1, '\webadmin\modules\common\help\HelpModule', 23);
