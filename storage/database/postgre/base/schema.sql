CREATE TABLE IF NOT EXISTS migrations (
  migration SERIAL NOT NULL,
  package varchar(255) NOT NULL,
  installed timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  removed timestamp DEFAULT NULL ,
  PRIMARY KEY (migration)
);

CREATE TABLE IF NOT EXISTS config (
  k varchar(255) NOT NULL,
  v varchar(2000) NOT NULL DEFAULT '',
  PRIMARY KEY (k)
);
