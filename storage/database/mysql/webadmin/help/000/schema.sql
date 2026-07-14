CREATE TABLE IF NOT EXISTS help (
  url varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  content longtext COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (url)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
