CREATE TABLE IF NOT EXISTS authentication (
  authentication SERIAL NOT NULL,
  authenticator varchar(128) NOT NULL,
  settings text,
  position int NOT NULL,
  disabled int NOT NULL DEFAULT '0',
  conditions text,
  PRIMARY KEY (authentication)
);



CREATE TABLE IF NOT EXISTS uploads (
  id SERIAL NOT NULL,
  name text  NOT NULL ,
  location text  NOT NULL ,
  bytesize int NOT NULL DEFAULT '0' ,
  uploaded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  hash varchar(32) NOT NULL DEFAULT '' ,
  data bytea DEFAULT NULL ,
  settings text ,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS users (
  usr SERIAL NOT NULL,
  name varchar(80) NOT NULL DEFAULT '' ,
  mail varchar(255) NOT NULL DEFAULT '' ,
  tfa smallint NOT NULL DEFAULT '0' ,
  disabled smallint  NOT NULL DEFAULT '0' ,
  avatar int  DEFAULT NULL,
  avatar_data text,
  data text,
  push text,
  sessions text,
  PRIMARY KEY (usr),
  CONSTRAINT FK_USER_AVATAR FOREIGN KEY (avatar) REFERENCES uploads (id)
);

CREATE TABLE IF NOT EXISTS permissions (
  perm varchar(80)  NOT NULL ,
  created timestamp NOT NULL ,
  PRIMARY KEY (perm)
);

CREATE TABLE IF NOT EXISTS grps (
  grp SERIAL NOT NULL,
  name varchar(80) NOT NULL ,
  created timestamp NOT NULL ,
  PRIMARY KEY (grp)
)   ;

CREATE TABLE IF NOT EXISTS group_permissions (
  grp int  NOT NULL ,
  perm varchar(80)  NOT NULL ,
  created timestamp NOT NULL ,
  PRIMARY KEY (grp,perm),
  CONSTRAINT FK_GROUPPERMISSIONS_GROUPS FOREIGN KEY (grp) REFERENCES grps (grp) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_GROUPPERMISSIONS_PERMISSIONS FOREIGN KEY (perm) REFERENCES permissions (perm) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS organization (
  org SERIAL NOT NULL,
  lft int  NOT NULL ,
  rgt int  NOT NULL ,
  lvl int  NOT NULL ,
  pid int  DEFAULT NULL ,
  pos int  NOT NULL ,
  title varchar(50) NOT NULL DEFAULT '' ,
  properties text ,
  PRIMARY KEY (org),
  CONSTRAINT FK_ORGANIZATION_PARENT FOREIGN KEY (pid) REFERENCES organization (org) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS log (
  id SERIAL NOT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  lvl varchar(20) NOT NULL DEFAULT 'error' ,
  message varchar(255) NOT NULL DEFAULT '' ,
  context text,
  request text,
  response text,
  ip varchar(45) NOT NULL DEFAULT '' ,
  usr int  DEFAULT NULL ,
  usr_name varchar(80) DEFAULT NULL ,
  PRIMARY KEY (id),
  CONSTRAINT FK_LOG_USER FOREIGN KEY (usr) REFERENCES users (usr)
);
CREATE INDEX IF NOT EXISTS idx_log_created ON log(created, ip);


CREATE TABLE IF NOT EXISTS mails (
  mail SERIAL NOT NULL,
  recipient varchar(1000) NOT NULL,
  subject varchar(2000) NOT NULL,
  content text,
  priority int DEFAULT '0',
  added timestamp NOT NULL,
  started timestamp DEFAULT NULL,
  finished timestamp DEFAULT NULL,
  result varchar(255) DEFAULT NULL,
  PRIMARY KEY (mail)
);
CREATE INDEX IF NOT EXISTS idx_added ON mails(added);
CREATE INDEX IF NOT EXISTS idx_recipient ON mails(recipient);


CREATE TABLE IF NOT EXISTS log_system (
  id SERIAL NOT NULL,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  lvl varchar(20) NOT NULL,
  message varchar(255) NOT NULL DEFAULT '',
  context text NOT NULL,
  module varchar(100) DEFAULT NULL,
  module_id varchar(100) DEFAULT NULL,
  usr int DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT FK_SYSLOG_USER FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS user_groups (
  usr int  NOT NULL ,
  grp int  NOT NULL ,
  main int NOT NULL DEFAULT '0' ,
  created timestamp NOT NULL ,
  PRIMARY KEY (usr,grp),
  CONSTRAINT FK_USERGROUPS_GROUPS FOREIGN KEY (grp) REFERENCES grps (grp) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERGROUPS_USERS FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)   ;

CREATE TABLE IF NOT EXISTS user_groups_provisional (
  usr int  NOT NULL ,
  grp int  NOT NULL ,
  created timestamp NOT NULL ,
  PRIMARY KEY (usr,grp),
  CONSTRAINT FK_USERGROUPSP_GROUPS FOREIGN KEY (grp) REFERENCES grps (grp) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERGROUPSP_USERS FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)   ;


-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_organizations
CREATE TABLE IF NOT EXISTS user_organizations (
  usr int  NOT NULL,
  org int  NOT NULL,
  PRIMARY KEY (usr,org),
  CONSTRAINT FK_USERORGANIZATION_ORGANIZATION FOREIGN KEY (org) REFERENCES organization (org) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_USERORGANIZATION_USER FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)  ;

-- Data exporting was unselected.
-- Dumping structure for table webadmin.user_providers
CREATE TABLE IF NOT EXISTS user_providers (
  usrprov SERIAL NOT NULL,
  provider varchar(80) NOT NULL ,
  id varchar(500) NOT NULL ,
  usr int  NOT NULL ,
  name varchar(500) NOT NULL DEFAULT '' ,
  data varchar(4000) DEFAULT NULL ,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  used timestamp DEFAULT NULL ,
  disabled smallint  NOT NULL DEFAULT '0' ,
  details text,
  PRIMARY KEY (usrprov),
  CONSTRAINT FK_USERPROVIDERS_USERS FOREIGN KEY (usr) REFERENCES users (usr) ON DELETE CASCADE ON UPDATE CASCADE
)   ;
CREATE INDEX IF NOT EXISTS idx_userprov_provid ON user_providers(provider, id);

CREATE TABLE IF NOT EXISTS modules (
  name varchar(255),
  slug varchar(255),
  loaded int default 0,
  classname varchar(255),
  pos int default 0,
  settings text,
  PRIMARY KEY (name)
);

CREATE TABLE IF NOT EXISTS user_pending (
  usrpend SERIAL NOT NULL,
  provider varchar(80) NOT NULL ,
  id varchar(500) NOT NULL ,
  name varchar(500) NOT NULL DEFAULT '' ,
  mail varchar(500) NOT NULL DEFAULT '' ,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  details text,
  PRIMARY KEY (usrpend)
)   ;
CREATE INDEX IF NOT EXISTS idx_userpend_provid ON user_pending(provider, id);

-- Data exporting was unselected.
-- Dumping structure for table webadmin.versions
CREATE TABLE IF NOT EXISTS versions (
  tbl varchar(255) NOT NULL ,
  id varchar(255) NOT NULL ,
  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  entity text ,
  reason varchar(50) NOT NULL ,
  usr int  NOT NULL DEFAULT '0' ,
  usr_name varchar(80) NOT NULL 
)   ;
