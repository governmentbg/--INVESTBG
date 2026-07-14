CREATE TABLE IF NOT EXISTS tree_struct (
  id integer PRIMARY KEY,
  lft integer  NOT NULL ,
  rgt integer  NOT NULL ,
  lvl integer  NOT NULL ,
  pid integer  DEFAULT NULL ,
  pos integer  NOT NULL ,
  FOREIGN KEY (pid) REFERENCES tree_struct (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS sites (
  site integer PRIMARY KEY ,
  name varchar(200)  NOT NULL ,
  domains varchar(2000)  NOT NULL DEFAULT '',
  tree integer  DEFAULT NULL ,
  disabled smallint  NOT NULL DEFAULT '0',
  dflt smallint  NOT NULL DEFAULT '0',
  FOREIGN KEY (tree) REFERENCES tree_struct (id)
);

CREATE TABLE IF NOT EXISTS languages (
  lang integer PRIMARY KEY,
  code char(2) NOT NULL ,
  name varchar(200) NOT NULL ,
  local varchar(200) NOT NULL ,
  CONSTRAINT code UNIQUE (code)
);

CREATE TABLE IF NOT EXISTS tags (
  tag integer PRIMARY KEY,
  lang integer  NOT NULL,
  name varchar(100) NOT NULL,
  FOREIGN KEY (lang) REFERENCES languages (lang)
);

CREATE TABLE IF NOT EXISTS news (
  news integer PRIMARY KEY,
  lang integer  NOT NULL,
  fordate date NOT NULL,
  title varchar(1000) NOT NULL DEFAULT '',
  image integer DEFAULT NULL,
  content text NOT NULL,
  hidden smallint  NOT NULL DEFAULT '0',
  visible_beg timestamp NOT NULL,
  visible_end timestamp DEFAULT NULL,
  site integer  DEFAULT NULL,
  FOREIGN KEY (lang) REFERENCES languages (lang),
  FOREIGN KEY (image) REFERENCES uploads (id),
  FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS news_tags (
  news integer  NOT NULL,
  tag integer  NOT NULL,
  PRIMARY KEY (news, tag),
  FOREIGN KEY (news) REFERENCES news (news),
  FOREIGN KEY (tag) REFERENCES tags (tag)
);

CREATE TABLE IF NOT EXISTS galleries (
  gallery integer PRIMARY KEY,
  lang integer  NOT NULL,
  fordate date NOT NULL,
  title varchar(1000) NOT NULL DEFAULT '',
  content text NOT NULL,
  hidden smallint  NOT NULL DEFAULT '0',
  visible_beg timestamp NOT NULL,
  visible_end timestamp DEFAULT NULL,
  site integer  DEFAULT NULL,
  FOREIGN KEY (lang) REFERENCES languages (lang),
  FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS gallery_tags (
  gallery integer  NOT NULL,
  tag integer  NOT NULL,
  PRIMARY KEY (gallery, tag),
  FOREIGN KEY (gallery) REFERENCES galleries (gallery),
  FOREIGN KEY (tag) REFERENCES tags (tag)
);

CREATE TABLE IF NOT EXISTS gallery_images (
  gallery integer  NOT NULL,
  upload integer  NOT NULL,
  pos integer  NOT NULL,
  PRIMARY KEY (gallery, upload),
  FOREIGN KEY (gallery) REFERENCES galleries (gallery),
  FOREIGN KEY (upload) REFERENCES uploads (id)
);

CREATE TABLE IF NOT EXISTS redirects (
  redirect integer PRIMARY KEY,
  url_from varchar(2000)  NOT NULL DEFAULT '',
  url_to varchar(2000)  NOT NULL DEFAULT '',
  rtype varchar(20) NOT NULL DEFAULT 'none',
  site integer DEFAULT NULL,
  FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE TABLE IF NOT EXISTS search_index (
  si varchar(40) NOT NULL,
  url varchar(2000) NOT NULL,
  title varchar(2000) NOT NULL,
  data text NOT NULL,
  meta json NOT NULL,
  indexed timestamp NOT NULL,
  remove smallint NOT NULL DEFAULT '0',
  site integer  DEFAULT NULL,
  lang integer  DEFAULT NULL,
  FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (lang) REFERENCES languages (lang) ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE TABLE IF NOT EXISTS site_domain (
  site integer  NOT NULL,
  domain varchar(500) NOT NULL,
  PRIMARY KEY (site, domain),
  FOREIGN KEY (site) REFERENCES sites (site)
)  ;

CREATE TABLE IF NOT EXISTS site_lang (
  site integer  NOT NULL,
  lang integer  NOT NULL,
  PRIMARY KEY (site, lang),
  FOREIGN KEY (lang) REFERENCES languages (lang) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE
)  ;

CREATE TABLE IF NOT EXISTS templates (
  template integer PRIMARY KEY,
  name varchar(255)  NOT NULL DEFAULT '',
  base varchar(255)  NOT NULL DEFAULT '',
  is_default smallint NOT NULL DEFAULT '0',
  child_default integer DEFAULT NULL,
  widgets text,
  zones text,
  FOREIGN KEY (child_default) REFERENCES templates (template)
) ;

CREATE TABLE IF NOT EXISTS menus (
  menu integer PRIMARY KEY,
  name varchar(255)  NOT NULL DEFAULT '',
  slug varchar(255)  NOT NULL DEFAULT '',
  site integer  NOT NULL,
  lang integer  NOT NULL,
  is_default smallint NOT NULL DEFAULT '0',
  items text
) ;

CREATE TABLE IF NOT EXISTS tree_data (
  id integer  NOT NULL,
  lang integer  NOT NULL ,
  version integer  NOT NULL ,
  from_version integer DEFAULT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  usr integer  NOT NULL DEFAULT '0' ,
  title varchar(255) NOT NULL DEFAULT '' ,
  hidden integer  NOT NULL DEFAULT '0' ,
  url varchar(255) NOT NULL DEFAULT '' ,
  redirect varchar(1024) NOT NULL DEFAULT '' ,
  settings text ,
  content text ,
  permissions text ,
  template integer NOT NULL ,
  menu integer DEFAULT NULL ,
  published integer  NOT NULL DEFAULT '0' ,
  PRIMARY KEY (id, lang, version),
  FOREIGN KEY (lang) REFERENCES languages (lang),
  FOREIGN KEY (id) REFERENCES tree_struct (id),
  FOREIGN KEY (usr) REFERENCES users (usr),
  FOREIGN KEY (template) REFERENCES templates (template),
  FOREIGN KEY (menu) REFERENCES menus (menu)
);

CREATE TABLE IF NOT EXISTS tree_data_pub (
  id integer  NOT NULL,
  lang integer  NOT NULL ,
  version integer  NOT NULL ,
  from_version integer DEFAULT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  usr integer  NOT NULL DEFAULT '0' ,
  title varchar(255) NOT NULL DEFAULT '' ,
  hidden integer  NOT NULL DEFAULT '0' ,
  url varchar(255) NOT NULL DEFAULT '' ,
  redirect varchar(1024) NOT NULL DEFAULT '' ,
  settings text ,
  content text ,
  permissions text ,
  template integer NOT NULL ,
  menu integer DEFAULT NULL ,
  published integer  NOT NULL DEFAULT '0' ,
  PRIMARY KEY (id, lang),
  FOREIGN KEY (lang) REFERENCES languages (lang),
  FOREIGN KEY (id) REFERENCES tree_struct (id),
  FOREIGN KEY (usr) REFERENCES users (usr),
  FOREIGN KEY (template) REFERENCES templates (template),
  FOREIGN KEY (menu) REFERENCES menus (menu)
);

-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_lang
CREATE TABLE IF NOT EXISTS user_lang (
  usr integer  NOT NULL,
  lang integer  NOT NULL,
  PRIMARY KEY (usr, lang),
  FOREIGN KEY (lang) REFERENCES languages (lang) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)  ;


-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_site
CREATE TABLE IF NOT EXISTS user_site (
  usr integer  NOT NULL,
  site integer  NOT NULL,
  PRIMARY KEY (usr, site),
  FOREIGN KEY (site) REFERENCES sites (site) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)  ;

CREATE TABLE IF NOT EXISTS log_public (
  id integer PRIMARY KEY,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  lvl varchar(20) NOT NULL DEFAULT 'error' ,
  message varchar(255) NOT NULL DEFAULT '' ,
  context text,
  request text,
  response text,
  ip varchar(45) NOT NULL DEFAULT '' ,
  ua varchar(512) NOT NULL DEFAULT '' 
);
