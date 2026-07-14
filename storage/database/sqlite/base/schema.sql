CREATE TABLE IF NOT EXISTS migrations (
  migration integer PRIMARY KEY,
  package varchar(255) NOT NULL,
  installed timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  removed timestamp DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS config (
  k varchar(255)  PRIMARY KEY,
  v varchar(2000) NOT NULL DEFAULT ''
);
