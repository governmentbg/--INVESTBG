CREATE TABLE IF NOT EXISTS tree_struct (
  id SERIAL NOT NULL,
  lft int  NOT NULL ,
  rgt int  NOT NULL ,
  lvl int  NOT NULL ,
  pid int  DEFAULT NULL ,
  pos int  NOT NULL ,
  PRIMARY KEY (id),
  CONSTRAINT FK_TREENODE_PARENT FOREIGN KEY (pid) REFERENCES tree_struct (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS sites (
  site SERIAL NOT NULL,
  name varchar(200)  NOT NULL ,
  domains varchar(2000)  NOT NULL DEFAULT '',
  tree int  DEFAULT NULL ,
  disabled smallint  NOT NULL DEFAULT '0',
  dflt smallint  NOT NULL DEFAULT '0',
  PRIMARY KEY (site),
  CONSTRAINT FK_SITES_TREE FOREIGN KEY (tree) REFERENCES tree_struct (id)
);

CREATE TABLE IF NOT EXISTS languages (
  lang SERIAL NOT NULL,
  code char(2) NOT NULL ,
  name varchar(200) NOT NULL ,
  local varchar(200) NOT NULL ,
  PRIMARY KEY (lang),
  CONSTRAINT code UNIQUE (code)
);

CREATE TABLE IF NOT EXISTS tags (
  tag SERIAL NOT NULL,
  lang int  NOT NULL,
  name varchar(100) NOT NULL,
  PRIMARY KEY (tag),
  CONSTRAINT FK_TAGS_LANGUAGE FOREIGN KEY (lang) REFERENCES languages (lang)
);

CREATE TABLE IF NOT EXISTS news (
  news SERIAL NOT NULL,
  lang int  NOT NULL,
  fordate date NOT NULL,
  title varchar(1000) NOT NULL DEFAULT '',
  image int DEFAULT NULL,
  content text NOT NULL,
  hidden smallint  NOT NULL DEFAULT '0',
  visible_beg timestamp NOT NULL,
  visible_end timestamp DEFAULT NULL,
  site int  DEFAULT NULL,
  PRIMARY KEY (news),
  CONSTRAINT FK_NEWS_LANGUAGE FOREIGN KEY (lang) REFERENCES languages (lang),
  CONSTRAINT FK_NEWS_IMAGE FOREIGN KEY (image) REFERENCES uploads (id),
  CONSTRAINT FK_NEWS_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS news_tags (
  news int  NOT NULL,
  tag int  NOT NULL,
  PRIMARY KEY (news,tag),
  CONSTRAINT FK_NEWSTAGS_NEWS FOREIGN KEY (news) REFERENCES news (news),
  CONSTRAINT FK_NEWSTAGS_TAG FOREIGN KEY (tag) REFERENCES tags (tag)
);

CREATE TABLE IF NOT EXISTS galleries (
  gallery SERIAL NOT NULL,
  lang int  NOT NULL,
  fordate date NOT NULL,
  title varchar(1000) NOT NULL DEFAULT '',
  content text NOT NULL,
  hidden smallint  NOT NULL DEFAULT '0',
  visible_beg timestamp NOT NULL,
  visible_end timestamp DEFAULT NULL,
  site int  DEFAULT NULL,
  PRIMARY KEY (gallery),
  CONSTRAINT FK_GALLERIES_LANGUAGE FOREIGN KEY (lang) REFERENCES languages (lang),
  CONSTRAINT FK_GALLERIES_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS gallery_tags (
  gallery int  NOT NULL,
  tag int  NOT NULL,
  PRIMARY KEY (gallery,tag),
  CONSTRAINT FK_GALLERYTAGS_GALLERY FOREIGN KEY (gallery) REFERENCES galleries (gallery),
  CONSTRAINT FK_GALLERYTAGS_TAG FOREIGN KEY (tag) REFERENCES tags (tag)
);

CREATE TABLE IF NOT EXISTS gallery_images (
  gallery int  NOT NULL,
  upload int  NOT NULL,
  pos int  NOT NULL,
  PRIMARY KEY (gallery,upload),
  CONSTRAINT FK_GALLERYIMAGES_GALLERY FOREIGN KEY (gallery) REFERENCES galleries (gallery),
  CONSTRAINT FK_GALLERYIMAGES_IMAGE FOREIGN KEY (upload) REFERENCES uploads (id)
);

CREATE TABLE IF NOT EXISTS redirects (
  redirect SERIAL NOT NULL,
  url_from varchar(2000)  NOT NULL DEFAULT '',
  url_to varchar(2000)  NOT NULL DEFAULT '',
  rtype varchar(20) NOT NULL DEFAULT 'none',
  site int DEFAULT NULL,
  PRIMARY KEY (redirect),
  CONSTRAINT FK_REDIRECT_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE TABLE IF NOT EXISTS search_index (
  si varchar(40) NOT NULL,
  url varchar(2000) NOT NULL,
  title varchar(2000) NOT NULL,
  data text NOT NULL,
  meta json NOT NULL,
  indexed timestamp NOT NULL,
  remove smallint NOT NULL DEFAULT '0',
  site int  DEFAULT NULL,
  lang int  DEFAULT NULL,
  PRIMARY KEY (si),
  CONSTRAINT FK_SEARCH_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_SEARCH_LANG FOREIGN KEY (lang) REFERENCES languages (lang) ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE TABLE IF NOT EXISTS site_domain (
  site int  NOT NULL,
  domain varchar(500) NOT NULL,
  PRIMARY KEY (site,domain),
  CONSTRAINT FK_SITEDOMAIN_SITE FOREIGN KEY (site) REFERENCES sites (site)
)  ;

CREATE TABLE IF NOT EXISTS site_lang (
  site int  NOT NULL,
  lang int  NOT NULL,
  PRIMARY KEY (site,lang),
  CONSTRAINT FK_SITELANG_LANG FOREIGN KEY (lang) REFERENCES languages (lang) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_SITELANG_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
)  ;

CREATE TABLE IF NOT EXISTS templates (
  template SERIAL NOT NULL,
  name varchar(255)  NOT NULL DEFAULT '',
  base varchar(255)  NOT NULL DEFAULT '',
  is_default smallint NOT NULL DEFAULT '0',
  child_default int DEFAULT NULL,
  widgets text,
  zones text,
  PRIMARY KEY (template),
  CONSTRAINT FK_CHILD_TEMPLATE FOREIGN KEY (child_default) REFERENCES templates (template)
) ;

CREATE TABLE IF NOT EXISTS menus (
  menu SERIAL NOT NULL,
  name varchar(255)  NOT NULL DEFAULT '',
  slug varchar(255)  NOT NULL DEFAULT '',
  site int  NOT NULL,
  lang int  NOT NULL,
  is_default smallint NOT NULL DEFAULT '0',
  items text,
  PRIMARY KEY (menu)
) ;

CREATE TABLE IF NOT EXISTS tree_data (
  id int  NOT NULL,
  lang int  NOT NULL ,
  version int  NOT NULL ,
  from_version int DEFAULT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  usr int  NOT NULL DEFAULT '0' ,
  title varchar(255) NOT NULL DEFAULT '' ,
  hidden int  NOT NULL DEFAULT '0' ,
  url varchar(255) NOT NULL DEFAULT '' ,
  redirect varchar(1024) NOT NULL DEFAULT '' ,
  settings text ,
  content text ,
  permissions text ,
  template int NOT NULL ,
  menu int DEFAULT NULL ,
  published int  NOT NULL DEFAULT '0' ,
  PRIMARY KEY (id,lang,version),
  CONSTRAINT FK_TREE_LANG FOREIGN KEY (lang) REFERENCES languages (lang),
  CONSTRAINT FK_TREE_STRUCT FOREIGN KEY (id) REFERENCES tree_struct (id),
  CONSTRAINT FK_TREE_USR FOREIGN KEY (usr) REFERENCES users (usr),
  CONSTRAINT FK_TREE_TEMPLATE FOREIGN KEY (template) REFERENCES templates (template),
  CONSTRAINT FK_TREE_MENU FOREIGN KEY (menu) REFERENCES menus (menu)
);

CREATE TABLE IF NOT EXISTS tree_data_pub (
  id int  NOT NULL,
  lang int  NOT NULL ,
  version int  NOT NULL ,
  from_version int DEFAULT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  usr int  NOT NULL DEFAULT '0' ,
  title varchar(255) NOT NULL DEFAULT '' ,
  hidden int  NOT NULL DEFAULT '0' ,
  url varchar(255) NOT NULL DEFAULT '' ,
  redirect varchar(1024) NOT NULL DEFAULT '' ,
  settings text ,
  content text ,
  permissions text ,
  template int NOT NULL ,
  menu int DEFAULT NULL ,
  published int  NOT NULL DEFAULT '0' ,
  PRIMARY KEY (id,lang),
  CONSTRAINT FK_TREEPUB_LANG FOREIGN KEY (lang) REFERENCES languages (lang),
  CONSTRAINT FK_TREEPUB_STRUCT FOREIGN KEY (id) REFERENCES tree_struct (id),
  CONSTRAINT FK_TREEPUB_USR FOREIGN KEY (usr) REFERENCES users (usr),
  CONSTRAINT FK_TREEPUB_TEMPLATE FOREIGN KEY (template) REFERENCES templates (template),
  CONSTRAINT FK_TREEPUB_MENU FOREIGN KEY (menu) REFERENCES menus (menu)
);

-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_lang
CREATE TABLE IF NOT EXISTS user_lang (
  usr int  NOT NULL,
  lang int  NOT NULL,
  PRIMARY KEY (usr,lang),
  CONSTRAINT FK_USERLANG_LANG FOREIGN KEY (lang) REFERENCES languages (lang) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERLANG_USER FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)  ;


-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_site
CREATE TABLE IF NOT EXISTS user_site (
  usr int  NOT NULL,
  site int  NOT NULL,
  PRIMARY KEY (usr,site),
  CONSTRAINT FK_USERSITE_SITE FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERSITE_USER FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)  ;

CREATE TABLE IF NOT EXISTS log_public (
  id SERIAL NOT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  lvl varchar(20) NOT NULL DEFAULT 'error' ,
  message varchar(255) NOT NULL DEFAULT '' ,
  context text,
  request text,
  response text,
  ip varchar(45) NOT NULL DEFAULT '' ,
  ua varchar(512) NOT NULL DEFAULT '' ,
  PRIMARY KEY (id)
);
CREATE INDEX IF NOT EXISTS idx_log_public_created ON log_public(created, ip);
