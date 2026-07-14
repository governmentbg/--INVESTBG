DROP TABLE IF EXISTS upload_collections ;
DROP TABLE IF EXISTS collection_groups ;
DROP TABLE IF EXISTS collections ;
DROP TABLE IF EXISTS upload_user ;

DELETE FROM group_permissions WHERE perm = 'uploads/master';
DELETE FROM permissions WHERE perm = 'uploads/master';
