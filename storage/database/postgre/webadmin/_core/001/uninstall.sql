DROP TABLE IF EXISTS upload_collections CASCADE;
DROP TABLE IF EXISTS collection_groups CASCADE;
DROP TABLE IF EXISTS collections CASCADE;
DROP TABLE IF EXISTS upload_user CASCADE;

DELETE FROM group_permissions WHERE perm = 'uploads/master';
DELETE FROM permissions WHERE perm = 'uploads/master';
