/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

CREATE TABLE IF NOT EXISTS authentication (
  authentication int(10) UNSIGNED NOT NULL,
  authenticator varchar(128) NOT NULL,
  settings longtext NOT NULL,
  position int(10) UNSIGNED NOT NULL,
  disabled tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  conditions text COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (authentication)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS uploads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Име на файла',
  location text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Път до файла (спрямо основната директория за прикачени файлове)',
  bytesize bigint(20) NOT NULL DEFAULT '0' COMMENT 'Размер на файла',
  uploaded datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на прикачане в системата',
  hash varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'md5 хеш на файловото съдържание (използва се за ETag)',
  data longblob COMMENT 'Съдържание на файла (само в случай, че не се пише по файловата система)',
  settings text COLLATE utf8mb4_general_ci COMMENT 'Опционални настройки на файла',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Прикачени файлове';

CREATE TABLE IF NOT EXISTS users (
  usr int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(80) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Име на потребителя (Иван Георгиев)',
  mail varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'email адрес',
  tfa tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Изисква ли се двуфакторна аутентикация при логин',
  disabled tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Блокиран',
  avatar bigint(20) unsigned DEFAULT NULL,
  avatar_data text COLLATE utf8mb4_general_ci,
  data longtext COLLATE utf8mb4_general_ci,
  push longtext COLLATE utf8mb4_general_ci,
  sessions longtext COLLATE utf8mb4_general_ci,
  PRIMARY KEY (usr),
  CONSTRAINT FK_USER_AVATAR FOREIGN KEY (avatar) REFERENCES uploads (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Потребители';

CREATE TABLE IF NOT EXISTS permissions (
  perm varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Име на правото',
  created datetime NOT NULL COMMENT 'Дата на добавяне на правото',
  PRIMARY KEY (perm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Права в системата';

CREATE TABLE IF NOT EXISTS grps (
  grp int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Име на групата',
  name varchar(80) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Име на групата',
  created datetime NOT NULL COMMENT 'Дата на създаване на групата',
  PRIMARY KEY (grp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Потребителски групи';

-- Dumping structure for table webadmin.group_permissions
CREATE TABLE IF NOT EXISTS group_permissions (
  grp int(10) unsigned NOT NULL COMMENT 'Име на групата',
  perm varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Име на правото',
  created datetime NOT NULL COMMENT 'Дата и час на даване на правото на групата',
  PRIMARY KEY (grp,perm),
  KEY FK_GROUPPERMISSIONS_PERMISSIONS (perm),
  CONSTRAINT FK_GROUPPERMISSIONS_GROUPS FOREIGN KEY (grp) REFERENCES grps (grp) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_GROUPPERMISSIONS_PERMISSIONS FOREIGN KEY (perm) REFERENCES permissions (perm) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Права на потребителски групи';

CREATE TABLE IF NOT EXISTS organization (
  org int(10) unsigned NOT NULL AUTO_INCREMENT,
  lft int(10) unsigned NOT NULL COMMENT 'Ляв индекс (nested set)',
  rgt int(10) unsigned NOT NULL COMMENT 'Десен индекс (nested set)',
  lvl int(10) unsigned NOT NULL COMMENT 'Ниво (nested set)',
  pid int(10) unsigned DEFAULT NULL COMMENT 'Родител (adjacency)',
  pos int(10) unsigned NOT NULL COMMENT 'Позиция (adjacency)',
  title varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Име на брънката',
  properties longtext COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Допълнителни параметри (в JSON формат)',
  PRIMARY KEY (org),
  KEY lft (lft,rgt),
  KEY pid (pid),
  CONSTRAINT FK_ORGANIZATION_PARENT FOREIGN KEY (pid) REFERENCES organization (org) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Структура на организация - пазят се едновременно nested set и adjacency';

CREATE TABLE IF NOT EXISTS log (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на събитието',
  lvl enum('emergency','alert','critical','error','warning','notice','info','debug') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'error' COMMENT 'Тип на събитието',
  message varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Съобщение',
  context longtext COLLATE utf8mb4_general_ci COMMENT 'Допълнителни параметри (в JSON формат)',
  request longtext COLLATE utf8mb4_general_ci COMMENT 'Копие на HTTP заявката към системата (ако е налична)',
  response longtext COLLATE utf8mb4_general_ci COMMENT 'Копие на HTTP отговора (ако е наличен)',
  ip varchar(45) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP адрес изпратил заявката',
  usr int(10) unsigned DEFAULT NULL COMMENT 'ID на потребител изпратил заявката',
  usr_name varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Име на потребител изпратил заявката',
  PRIMARY KEY (id),
  KEY created (created, ip),
  KEY FK_LOG_USER (usr),
  CONSTRAINT FK_LOG_USER FOREIGN KEY (usr) REFERENCES users (usr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Лог на действията в системата';

CREATE TABLE IF NOT EXISTS mails (
  mail INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  recipient VARCHAR(1000) NOT NULL COLLATE 'utf8mb4_general_ci',
  subject VARCHAR(2000) NOT NULL COLLATE 'utf8mb4_general_ci',
  content LONGTEXT DEFAULT NULL COLLATE 'utf8mb4_general_ci',
  priority TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  added DATETIME NOT NULL,
  started DATETIME DEFAULT NULL,
  finished DATETIME DEFAULT NULL,
  result VARCHAR(2000) DEFAULT NULL COLLATE 'utf8mb4_general_ci',
  PRIMARY KEY (mail) USING BTREE,
  KEY added (added),
  KEY recipient (recipient)
) DEFAULT CHARSET=utf8mb4 COLLATE='utf8mb4_general_ci' ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS log_system (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на събитието',
  lvl enum('DEBUG','INFO','NOTICE','WARNING','ERROR','CRITICAL','ALERT','EMERGENCY') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'error' COMMENT 'Тип на събитието',
  message varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Съобщение',
  context longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Допълнителни параметри (в JSON формат)',
  module varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  module_id varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  usr int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  KEY FK_SYSLOG_USER (usr),
  CONSTRAINT FK_SYSLOG_USER FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS user_groups (
  usr int(10) unsigned NOT NULL COMMENT 'ID на потребител',
  grp int(10) unsigned NOT NULL COMMENT 'ID на група',
  main tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Дали е основна група за този потребител (всеки потребител има една основна група)',
  created datetime NOT NULL COMMENT 'Дата и час на добаване на потребителя към групата',
  PRIMARY KEY (usr,grp),
  KEY FK_USERGROUPS_GROUPS (grp),
  CONSTRAINT FK_USERGROUPS_GROUPS FOREIGN KEY (grp) REFERENCES grps (grp) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERGROUPS_USERS FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Връзка между потребители и потребителски групи';

CREATE TABLE IF NOT EXISTS user_groups_provisional (
  usr int(10) unsigned NOT NULL COMMENT 'ID на потребител',
  grp int(10) unsigned NOT NULL COMMENT 'ID на група',
  created datetime NOT NULL COMMENT 'Дата и час на добаване на потребителя към групата',
  PRIMARY KEY (usr,grp),
  KEY FK_USERGROUPSP_GROUPS (grp),
  CONSTRAINT FK_USERGROUPSP_GROUPS FOREIGN KEY (grp) REFERENCES grps (grp) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERGROUPSP_USERS FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_organizations
CREATE TABLE IF NOT EXISTS user_organizations (
  usr int(10) unsigned NOT NULL,
  org int(10) unsigned NOT NULL,
  PRIMARY KEY (usr,org),
  KEY FK_USERORGANIZATION_ORGANIZATION (org),
  CONSTRAINT FK_USERORGANIZATION_ORGANIZATION FOREIGN KEY (org) REFERENCES organization (org) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERORGANIZATION_USER FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_providers
CREATE TABLE IF NOT EXISTS user_providers (
  usrprov bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  provider varchar(80) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Име на аутентикиращата услуга',
  id varchar(500) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ID на потребителя в аутентикиращата услуга',
  usr int(10) unsigned NOT NULL COMMENT 'ID на потребителя в системата',
  name varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Кратко име на комбинацията услуга / ID',
  data varchar(4000) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Допълнителна информация от услугата',
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на създаване',
  used datetime DEFAULT NULL COMMENT 'Дата и час на последно използване',
  disabled tinyint(1) NOT NULL DEFAULT '0',
  details longtext COLLATE utf8mb4_general_ci,
  PRIMARY KEY (usrprov),
  KEY PROVIDER_ID (provider, id),
  KEY FK_USERPROVIDERS_USERS (usr),
  CONSTRAINT FK_USERPROVIDERS_USERS FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Начини на аутентикация за всеки потребител';

CREATE TABLE IF NOT EXISTS modules (
  name varchar(255),
  classname varchar(255),
  slug varchar(255),
  loaded int default 0,
  pos int default 0,
  settings text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (name)
);

CREATE TABLE IF NOT EXISTS user_pending (
  usrpend bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  provider varchar(80) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Име на аутентикиращата услуга',
  id varchar(500) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ID на потребителя в аутентикиращата услуга',
  name varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Име на потребителя в аутентикиращата услуга',
  mail varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Мейл на потребителя в аутентикиращата услуга',
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на създаване',
  details longtext COLLATE utf8mb4_general_ci,
  PRIMARY KEY (usrpend),
  KEY PROVIDER_ID (provider, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Успешни аутентикации без асоцииран потребител';

-- Data exporting was unselected.
-- Dumping structure for table webadmin.versions
CREATE TABLE IF NOT EXISTS versions (
  tbl varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Име на модула',
  id varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ID на записа в модула',
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на създаване на версията',
  entity text COLLATE utf8mb4_general_ci COMMENT 'JSON Сериализиран обект, представляващ новата версия',
  reason varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Причина за създаване на версията',
  usr int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Потребител създал версията',
  usr_name varchar(80) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Име на потребителя създал версията',
  KEY VERSIONS_TABLE_ID (tbl,id,created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Версии на записи от всички модули (запазват се само версии на модулите, в които изрично е посочено)';




/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
