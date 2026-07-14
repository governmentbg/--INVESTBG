CREATE TABLE IF NOT EXISTS migrations (
  migration bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  package varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  installed datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  removed datetime DEFAULT NULL,
  PRIMARY KEY (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ;

CREATE TABLE IF NOT EXISTS config (
  k varchar(255) NOT NULL,
  v varchar(2000) NOT NULL DEFAULT '',
  PRIMARY KEY (k)
);
