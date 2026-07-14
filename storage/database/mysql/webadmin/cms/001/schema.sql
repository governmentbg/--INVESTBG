CREATE TABLE IF NOT EXISTS translations_public (
  locale char(2) not null,
  k varchar(255) not null,
  v varchar(2000),
  PRIMARY KEY (locale, k)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
