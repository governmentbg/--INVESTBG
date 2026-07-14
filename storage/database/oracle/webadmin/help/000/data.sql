INSERT INTO permissions (perm, created) VALUES ('help', SYSTIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'help', SYSTIMESTAMP);
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('help', 'help', 1, '\webadmin\modules\common\help\HelpModule', 23);
