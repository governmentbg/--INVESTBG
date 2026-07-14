
CREATE TABLE IF NOT EXISTS tree_struct (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  lft int(10) unsigned NOT NULL COMMENT 'Ляв индекс (nested set)',
  rgt int(10) unsigned NOT NULL COMMENT 'Десен индекс (nested set)',
  lvl int(10) unsigned NOT NULL COMMENT 'Ниво (nested set)',
  pid int(10) unsigned DEFAULT NULL COMMENT 'Родител (adjacency)',
  pos int(10) unsigned NOT NULL COMMENT 'Позиция (adjacency)',
  PRIMARY KEY (id),
  KEY lft (lft,rgt),
  KEY pid (pid),
  CONSTRAINT FK_TREENODE_PARENT FOREIGN KEY (pid) REFERENCES tree_struct (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Структура от страници - пазят се едновременно nested set и adjacency';


CREATE TABLE IF NOT EXISTS sites (
  site int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Site name',
  domains varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  tree int(10) unsigned DEFAULT NULL COMMENT 'Site root',
  disabled tinyint(3) unsigned NOT NULL DEFAULT '0',
  dflt tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (site),
  KEY FK_SITES_TREE (tree),
  CONSTRAINT FK_SITES_TREE FOREIGN KEY (tree) REFERENCES tree_struct (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Сайтове';

CREATE TABLE IF NOT EXISTS languages (
  lang int(10) unsigned NOT NULL AUTO_INCREMENT,
  code char(2) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ISO 639-1 language code',
  name varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Own language name',
  local varchar(200) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Language name (in Bulgarian)',
  PRIMARY KEY (lang),
  UNIQUE KEY code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Езици достъпни в системата';

CREATE TABLE IF NOT EXISTS tags (
  tag int(10) unsigned NOT NULL AUTO_INCREMENT,
  lang int(10) unsigned NOT NULL,
  name varchar(100) NOT NULL,
  PRIMARY KEY (tag),
  CONSTRAINT FK_TAGS_LANGUAGE FOREIGN KEY (lang) REFERENCES languages (lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Тагове за новини, съдържание и др';

CREATE TABLE IF NOT EXISTS news (
  news int(10) unsigned NOT NULL AUTO_INCREMENT,
  lang int(10) unsigned NOT NULL,
  fordate date NOT NULL,
  title varchar(1000) NOT NULL DEFAULT '',
  image bigint(20) unsigned DEFAULT NULL,
  content longtext NOT NULL,
  hidden tinyint(3) unsigned NOT NULL DEFAULT '0',
  visible_beg datetime NOT NULL,
  visible_end datetime DEFAULT NULL,
  site int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (news),
  KEY FK_NEWS_LANGUAGE (lang),
  KEY FK_NEWS_SITE (site),
  CONSTRAINT FK_NEWS_LANGUAGE FOREIGN KEY (lang) REFERENCES languages (lang),
  CONSTRAINT FK_NEWS_IMAGE FOREIGN KEY (image) REFERENCES uploads (id),
  CONSTRAINT FK_NEWS_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS news_tags (
  news int(10) unsigned NOT NULL,
  tag int(10) unsigned NOT NULL,
  PRIMARY KEY (news,tag),
  KEY FK_NEWSTAGS_TAG (tag),
  CONSTRAINT FK_NEWSTAGS_NEWS FOREIGN KEY (news) REFERENCES news (news),
  CONSTRAINT FK_NEWSTAGS_TAG FOREIGN KEY (tag) REFERENCES tags (tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Тагове за новина';


CREATE TABLE IF NOT EXISTS redirects (
  redirect int(10) unsigned NOT NULL AUTO_INCREMENT,
  url_from varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  url_to varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  rtype enum('none','temporary','permanent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'none',
  site int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (redirect),
  KEY url_from (url_from(768)),
  KEY FK_REDIRECT_SITE (site),
  CONSTRAINT FK_REDIRECT_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS search_index (
  si varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  url varchar(2000) COLLATE utf8mb4_general_ci NOT NULL,
  title varchar(2000) COLLATE utf8mb4_general_ci NOT NULL,
  data longtext COLLATE utf8mb4_general_ci NOT NULL,
  meta json NOT NULL,
  indexed datetime NOT NULL,
  remove tinyint(4) NOT NULL DEFAULT '0',
  site int(10) unsigned DEFAULT NULL,
  lang int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (si),
  KEY url (url(768)),
  KEY FK_SEARCH_SITE (site),
  FULLTEXT KEY title_data (title,data),
  CONSTRAINT FK_SEARCH_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_SEARCH_LANG FOREIGN KEY (lang) REFERENCES languages (lang) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS site_domain (
  site int(10) unsigned NOT NULL,
  domain varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (site,domain),
  KEY domain (domain),
  CONSTRAINT FK_SITEDOMAIN_SITE FOREIGN KEY (site) REFERENCES sites (site)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS site_lang (
  site int(10) unsigned NOT NULL,
  lang int(10) unsigned NOT NULL,
  PRIMARY KEY (site,lang),
  KEY FK_SITELANG_LANG (lang),
  CONSTRAINT FK_SITELANG_LANG FOREIGN KEY (lang) REFERENCES languages (lang) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_SITELANG_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS templates (
  template int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255)  NOT NULL DEFAULT '',
  base varchar(255)  NOT NULL DEFAULT '',
  is_default tinyint(1) NOT NULL DEFAULT '0',
  child_default int(10) unsigned DEFAULT NULL,
  widgets text,
  zones text,
  PRIMARY KEY (template),
  CONSTRAINT FK_CHILD_TEMPLATE FOREIGN KEY (child_default) REFERENCES templates (template)
) ;

CREATE TABLE IF NOT EXISTS menus (
  menu int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255)  NOT NULL DEFAULT '',
  slug varchar(255)  NOT NULL DEFAULT '',
  site int(10) unsigned NOT NULL,
  lang int(10) unsigned NOT NULL,
  is_default tinyint(1) NOT NULL DEFAULT '0',
  items text,
  PRIMARY KEY (menu)
) ;

CREATE TABLE IF NOT EXISTS tree_data (
  id int(10) unsigned NOT NULL,
  lang int(10) unsigned NOT NULL COMMENT 'Език (всяка брънка може да има много записи с различен език)',
  version int(10) unsigned NOT NULL COMMENT 'Версия (увеличаващ се индекс при всеки нов запис за тази брънка)',
  from_version int(10) unsigned DEFAULT NULL,
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на създаване',
  usr int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Автор на съдържанието',
  title varchar(255) NOT NULL DEFAULT '' COMMENT 'Заглавие на страницата',
  hidden tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Скрита ли е страницата',
  url varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO URL на страницата',
  redirect varchar(1024) NOT NULL DEFAULT '' COMMENT 'URL към което редиректва страницата',
  settings text COMMENT 'JSON - настройки на страницата',
  content text COMMENT 'JSON - Съдържание',
  permissions text COMMENT 'JSON - Права',
  template int(10) unsigned NOT NULL,
  menu int(10) unsigned DEFAULT NULL,
  published tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Публикувана ли е страницата',
  PRIMARY KEY (id,lang,version),
  KEY url (url),
  KEY FK_TREE_USR (usr),
  KEY FK_TREE_LANG (lang),
  FULLTEXT KEY content (content),
  CONSTRAINT FK_TREE_LANG FOREIGN KEY (lang) REFERENCES languages (lang),
  CONSTRAINT FK_TREE_STRUCT FOREIGN KEY (id) REFERENCES tree_struct (id),
  CONSTRAINT FK_TREE_USR FOREIGN KEY (usr) REFERENCES users (usr),
  CONSTRAINT FK_TREE_TEMPLATE FOREIGN KEY (template) REFERENCES templates (template),
  CONSTRAINT FK_TREE_MENU FOREIGN KEY (menu) REFERENCES menus (menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Съдържание на страници (свързано с tree_struct)';

CREATE TABLE IF NOT EXISTS tree_data_pub (
  id int(10) unsigned NOT NULL,
  lang int(10) unsigned NOT NULL COMMENT 'Език (всяка брънка може да има много записи с различен език)',
  version int(10) unsigned NOT NULL COMMENT 'Версия (увеличаващ се индекс при всеки нов запис за тази брънка)',
  from_version int(10) unsigned DEFAULT NULL,
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на създаване',
  usr int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Автор на съдържанието',
  title varchar(255) NOT NULL DEFAULT '' COMMENT 'Заглавие на страницата',
  hidden tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Скрита ли е страницата',
  url varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO URL на страницата',
  redirect varchar(1024) NOT NULL DEFAULT '' COMMENT 'URL към което редиректва страницата',
  settings text COMMENT 'JSON - настройки на страницата',
  content text COMMENT 'JSON - Съдържание',
  permissions text COMMENT 'JSON - Права',
  template int(10) unsigned NOT NULL,
  menu int(10) unsigned DEFAULT NULL,
  published tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Публикувана ли е страницата',
  PRIMARY KEY (id,lang),
  KEY url (url),
  KEY FK_TREEPUB_USR (usr),
  KEY FK_TREEPUB_LANG (lang),
  FULLTEXT KEY content (content),
  CONSTRAINT FK_TREEPUB_LANG FOREIGN KEY (lang) REFERENCES languages (lang),
  CONSTRAINT FK_TREEPUB_STRUCT FOREIGN KEY (id) REFERENCES tree_struct (id),
  CONSTRAINT FK_TREEPUB_USR FOREIGN KEY (usr) REFERENCES users (usr),
  CONSTRAINT FK_TREEPUB_TEMPLATE FOREIGN KEY (template) REFERENCES templates (template),
  CONSTRAINT FK_TREEPUB_MENU FOREIGN KEY (menu) REFERENCES menus (menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Съдържание на страници (свързано с tree_struct)';

-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_lang
CREATE TABLE IF NOT EXISTS user_lang (
  usr int(10) unsigned NOT NULL,
  lang int(10) unsigned NOT NULL,
  PRIMARY KEY (usr,lang),
  KEY FK_USERLANG_LANG (lang),
  CONSTRAINT FK_USERLANG_LANG FOREIGN KEY (lang) REFERENCES languages (lang) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERLANG_USER FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_site
CREATE TABLE IF NOT EXISTS user_site (
  usr int(10) unsigned NOT NULL,
  site int(10) unsigned NOT NULL,
  PRIMARY KEY (usr,site),
  KEY FK_USERSITE_SITE (site),
  CONSTRAINT FK_USERSITE_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERSITE_USER FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS galleries (
  gallery int(10) unsigned NOT NULL AUTO_INCREMENT,
  lang int(10) unsigned NOT NULL,
  fordate date NOT NULL,
  title varchar(1000) NOT NULL DEFAULT '',
  content longtext NOT NULL,
  hidden tinyint(3) unsigned NOT NULL DEFAULT '0',
  visible_beg datetime NOT NULL,
  visible_end datetime DEFAULT NULL,
  site int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (gallery),
  KEY FK_GALLERIES_LANGUAGE (lang),
  KEY FK_GALLERIES_SITE (site),
  CONSTRAINT FK_GALLERIES_LANGUAGE FOREIGN KEY (lang) REFERENCES languages (lang),
  CONSTRAINT FK_GALLERIES_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery_tags (
  gallery int(10) unsigned NOT NULL,
  tag int(10) unsigned NOT NULL,
  PRIMARY KEY (gallery,tag),
  KEY FK_GALLERYTAGS_TAG (tag),
  CONSTRAINT FK_GALLERYTAGS_GALLERY FOREIGN KEY (gallery) REFERENCES galleries (gallery),
  CONSTRAINT FK_GALLERYTAGS_TAG FOREIGN KEY (tag) REFERENCES tags (tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Тагове за галерия';

CREATE TABLE IF NOT EXISTS gallery_images (
  gallery int(10) unsigned NOT NULL,
  upload bigint(20) unsigned NOT NULL,
  pos int(10) unsigned NOT NULL,
  PRIMARY KEY (gallery,upload),
  KEY FK_GALLERYIMAGES_IMAGE (upload),
  CONSTRAINT FK_GALLERYIMAGES_GALLERY FOREIGN KEY (gallery) REFERENCES galleries (gallery),
  CONSTRAINT FK_GALLERYIMAGES_IMAGE FOREIGN KEY (upload) REFERENCES uploads (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

CREATE TABLE IF NOT EXISTS log_public (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и час на събитието',
  lvl enum('emergency','alert','critical','error','warning','notice','info','debug') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'error' COMMENT 'Тип на събитието',
  message varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Съобщение',
  context longtext COLLATE utf8mb4_general_ci COMMENT 'Допълнителни параметри (в JSON формат)',
  request longtext COLLATE utf8mb4_general_ci COMMENT 'Копие на HTTP заявката към системата (ако е налична)',
  response longtext COLLATE utf8mb4_general_ci COMMENT 'Копие на HTTP отговора (ако е наличен)',
  ip varchar(45) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP адрес изпратил заявката',
  ua varchar(512) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'User agent изпратил заявката',
  PRIMARY KEY (id),
  KEY created (created, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Лог на действията в сайта';
