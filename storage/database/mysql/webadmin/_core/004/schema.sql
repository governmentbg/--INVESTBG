CREATE TABLE IF NOT EXISTS uploads_versions (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  upload bigint(20) unsigned NOT NULL ,
  version text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Версия на файла',
  name text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Име на файла',
  location text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Път до файла (спрямо основната директория за прикачени файлове)',
  bytesize bigint(20) NOT NULL DEFAULT '0' COMMENT 'Размер на файла',
  uploaded datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на прикачане в системата',
  hash varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'md5 хеш на файловото съдържание (използва се за ETag)',
  data longblob COMMENT 'Съдържание на файла (само в случай, че не се пише по файловата система)',
  settings text COLLATE utf8mb4_general_ci COMMENT 'Опционални настройки на файла',
  PRIMARY KEY (id),
  CONSTRAINT FK_UVERS_UPLOAD FOREIGN KEY (upload) REFERENCES uploads (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ;
