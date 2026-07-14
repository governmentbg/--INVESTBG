DROP TABLE IF EXISTS notification_files ;
DROP TABLE IF EXISTS notification_recipients ;
DROP TABLE IF EXISTS notifications ;

DELETE FROM modules WHERE name = 'notifications';
