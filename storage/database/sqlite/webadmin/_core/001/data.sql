DELETE FROM group_permissions WHERE perm = 'uploads';
DELETE FROM permissions WHERE perm = 'uploads';
INSERT INTO permissions (perm, created) VALUES ('uploads/master', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'uploads/master', CURRENT_TIMESTAMP);
UPDATE modules SET pos = pos + 1 WHERE pos > 17;
INSERT INTO modules (name, slug, loaded, classname, pos) VALUES ('collections', 'collections', 1, '\webadmin\modules\administration\collections\CollectionsModule', 907);
INSERT INTO permissions (perm, created) VALUES ('collections/master', CURRENT_TIMESTAMP);
INSERT INTO group_permissions (grp, perm, created) VALUES (1, 'collections/master', CURRENT_TIMESTAMP);
