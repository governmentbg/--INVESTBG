CREATE TABLE IF NOT EXISTS regions
(
    region  SMALLINT    NOT NULL,
    code    VARCHAR(3)  NOT NULL,
    name    VARCHAR(50) NOT NULL DEFAULT '',
    name_en VARCHAR(50) NOT NULL DEFAULT '',
    pos     SMALLINT    NOT NULL,
    CONSTRAINT regions_pk PRIMARY KEY (region),
    CONSTRAINT regions_unique UNIQUE (pos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS municipalities
(
    municipality SMALLINT     NOT NULL,
    code         VARCHAR(5)   NOT NULL,
    name         VARCHAR(100) NOT NULL DEFAULT '',
    name_en      VARCHAR(100) NOT NULL DEFAULT '',
    region       SMALLINT     NOT NULL,
    pos          SMALLINT     NOT NULL,
    CONSTRAINT municipalities_pk PRIMARY KEY (municipality),
    CONSTRAINT municipalities_regions_fk FOREIGN KEY (region) REFERENCES regions (region)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cities
(
    city         INT          NOT NULL,
    name         VARCHAR(100) NOT NULL DEFAULT '',
    name_en      VARCHAR(100) NOT NULL DEFAULT '',
    type         SMALLINT     NOT NULL,
    municipality SMALLINT     NOT NULL,
    pos          SMALLINT     NOT NULL,
    parent       INT          NULL,
    CONSTRAINT cities_pk PRIMARY KEY (city),
    CONSTRAINT cities_cities_fk FOREIGN KEY (parent) REFERENCES cities (city) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT cities_municipalities_fk FOREIGN KEY (municipality) REFERENCES municipalities (municipality)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;