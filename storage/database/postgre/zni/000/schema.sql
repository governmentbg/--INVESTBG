CREATE TABLE IF NOT EXISTS nom_contract_types
(
    contract_type serial4        NOT NULL,
    "name"        varchar(255)   NOT NULL,
    pos           int2 DEFAULT 0 NOT NULL,
    CONSTRAINT nom_contract_types_pk PRIMARY KEY (contract_type)
);

CREATE TABLE IF NOT EXISTS nom_company_types
(
    company_type serial4        NOT NULL,
    "name"       varchar(255)   NOT NULL,
    pos          int2 DEFAULT 0 NOT NULL,
    CONSTRAINT nom_company_types_pk PRIMARY KEY (company_type)
);

CREATE TABLE IF NOT EXISTS nom_certificate_types
(
    certificate_type serial4        NOT NULL,
    "name"           varchar(255)   NOT NULL,
    pos              int2 DEFAULT 0 NOT NULL,
    CONSTRAINT nom_certificate_types_pk PRIMARY KEY (certificate_type)
);

CREATE TABLE IF NOT EXISTS nom_reporting_periods
(
    reporting_period serial4        NOT NULL,
    "name"           varchar(255)   NOT NULL,
    pos              int2 DEFAULT 0 NOT NULL,
    CONSTRAINT nom_reporting_periods_pk PRIMARY KEY (reporting_period)
);

CREATE TABLE IF NOT EXISTS nom_sectors
(
    sector serial4        NOT NULL,
    "name" varchar(255)   NOT NULL,
    pos    int2 DEFAULT 0 NOT NULL,
    CONSTRAINT nom_sectors_pk PRIMARY KEY (sector)
);

CREATE TABLE IF NOT EXISTS nom_sector_activities
(
    sector_activity serial4        NOT NULL,
    sector          int4           NOT NULL,
    "name"          varchar(255)   NOT NULL,
    pos             int2 DEFAULT 0 NOT NULL,
    CONSTRAINT nom_sector_activities_pk PRIMARY KEY (sector_activity),
    CONSTRAINT nom_sector_activities_nom_sectors_fk FOREIGN KEY (sector) REFERENCES nom_sectors (sector) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS nom_kid (
	kid serial4 NOT NULL,
	code text NULL,
	"name" text NULL,
	CONSTRAINT nom_kid_pk PRIMARY KEY (kid)
);

CREATE TABLE IF NOT EXISTS nom_nkpd (
	nkpd serial4 NOT NULL,
	code text NULL,
	"name" text NULL,
	CONSTRAINT nom_nkpd_pk PRIMARY KEY (nkpd)
);

CREATE TABLE  IF NOT EXISTS nom_type_expense (
	type_expense serial4 NOT NULL,
	"name" text NULL,
	pos int2 NULL,
	CONSTRAINT nom_type_expense_pk PRIMARY KEY (type_expense)
);
