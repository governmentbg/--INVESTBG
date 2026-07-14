CREATE TABLE IF NOT EXISTS authentication (
  authentication integer PRIMARY KEY,
  authenticator varchar(128) NOT NULL,
  settings text,
  position integer NOT NULL,
  disabled integer NOT NULL DEFAULT '0',
  conditions text
);



CREATE TABLE IF NOT EXISTS uploads (
  id integer PRIMARY KEY,
  name text  NOT NULL ,
  location text  NOT NULL ,
  bytesize integer NOT NULL DEFAULT '0' ,
  uploaded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  hash varchar(32) NOT NULL DEFAULT '' ,
  data bytea DEFAULT NULL ,
  settings text 
);

CREATE TABLE IF NOT EXISTS users (
  usr integer PRIMARY KEY,
  name varchar(80) NOT NULL DEFAULT '' ,
  mail varchar(255) NOT NULL DEFAULT '' ,
  tfa smallint NOT NULL DEFAULT '0' ,
  disabled smallint  NOT NULL DEFAULT '0' ,
  avatar integer  DEFAULT NULL,
  avatar_data text,
  data text,
  push text,
  sessions text,
  FOREIGN KEY (avatar) REFERENCES uploads (id)
);

CREATE TABLE IF NOT EXISTS permissions (
  perm varchar(80) PRIMARY KEY ,
  created timestamp NOT NULL 
);

CREATE TABLE IF NOT EXISTS grps (
  grp integer PRIMARY KEY,
  name varchar(80) NOT NULL ,
  created timestamp NOT NULL
)   ;

CREATE TABLE IF NOT EXISTS group_permissions (
  grp integer not null ,
  perm varchar(80) not null ,
  created timestamp NOT NULL,
  PRIMARY KEY (grp, perm),
  FOREIGN KEY (grp) REFERENCES grps (grp) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (perm) REFERENCES permissions (perm) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS organization (
  org integer PRIMARY KEY,
  lft integer  NOT NULL ,
  rgt integer  NOT NULL ,
  lvl integer  NOT NULL ,
  pid integer  DEFAULT NULL ,
  pos integer  NOT NULL ,
  title varchar(50) NOT NULL DEFAULT '' ,
  properties text ,
  FOREIGN KEY (pid) REFERENCES organization (org) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS log (
  id integer PRIMARY KEY,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  lvl varchar(20) NOT NULL DEFAULT 'error' ,
  message varchar(255) NOT NULL DEFAULT '' ,
  context text,
  request text,
  response text,
  ip varchar(45) NOT NULL DEFAULT '' ,
  usr integer  DEFAULT NULL ,
  usr_name varchar(80) DEFAULT NULL ,
  FOREIGN KEY (usr) REFERENCES users (usr)
);

CREATE TABLE IF NOT EXISTS mails (
  mail integer PRIMARY KEY,
  recipient varchar(1000) NOT NULL,
  subject varchar(2000) NOT NULL,
  content text,
  priority integer DEFAULT '0',
  added timestamp NOT NULL,
  started timestamp DEFAULT NULL,
  finished timestamp DEFAULT NULL,
  result varchar(255) DEFAULT NULL
);


CREATE TABLE IF NOT EXISTS log_system (
  id integer PRIMARY KEY,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  lvl varchar(20) NOT NULL,
  message varchar(255) NOT NULL DEFAULT '',
  context text NOT NULL,
  module varchar(100) DEFAULT NULL,
  module_id varchar(100) DEFAULT NULL,
  usr integer DEFAULT NULL,
  FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS user_groups (
  usr integer  not null ,
  grp integer  not null ,
  main integer NOT NULL DEFAULT '0' ,
  created timestamp NOT NULL ,
  PRIMARY KEY (usr, grp),
  FOREIGN KEY (grp) REFERENCES grps (grp) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)   ;

CREATE TABLE IF NOT EXISTS user_groups_provisional (
  usr integer  not null ,
  grp integer  not null ,
  created timestamp NOT NULL ,
  PRIMARY KEY (usr, grp),
  FOREIGN KEY (grp) REFERENCES grps (grp) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)   ;


-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_organizations
CREATE TABLE IF NOT EXISTS user_organizations (
  usr integer not null,
  org integer not null,
  PRIMARY KEY (usr, org),
  FOREIGN KEY (org) REFERENCES organization (org) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)  ;

-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_providers
CREATE TABLE IF NOT EXISTS user_providers (
  usrprov integer PRIMARY KEY,
  provider varchar(80) NOT NULL ,
  id varchar(500) NOT NULL ,
  usr integer  NOT NULL ,
  name varchar(500) NOT NULL DEFAULT '' ,
  data varchar(4000) DEFAULT NULL ,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  used timestamp DEFAULT NULL ,
  disabled smallint  NOT NULL DEFAULT '0' ,
  details text,
  FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)   ;

CREATE TABLE IF NOT EXISTS modules (
  name varchar(255) PRIMARY KEY ,
  slug varchar(255),
  loaded integer default 0,
  classname varchar(255),
  pos integer default 0,
  settings text
);

CREATE TABLE IF NOT EXISTS user_pending (
  usrpend integer PRIMARY KEY,
  provider varchar(80) NOT NULL ,
  id varchar(500) NOT NULL ,
  name varchar(500) NOT NULL DEFAULT '' ,
  mail varchar(500) NOT NULL DEFAULT '' ,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  details text
)   ;

-- Data exporting was unselected.
-- Dumping structure for table webadmin.versions
CREATE TABLE IF NOT EXISTS versions (
  tbl varchar(255) NOT NULL ,
  id varchar(255) NOT NULL ,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  entity text ,
  reason varchar(50) NOT NULL ,
  usr integer  NOT NULL DEFAULT '0' ,
  usr_name varchar(80) NOT NULL 
)   ;
