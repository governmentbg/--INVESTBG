CREATE TABLE  IF NOT EXISTS insurable_income (
	ii serial4 NOT NULL,
	from_date date NOT NULL,
	to_date date NOT NULL,
	category smallint DEFAULT 0,
	max_income decimal(10, 2) NOT NULL,
	percent_insurance decimal(6, 2) NOT NULL,
	CONSTRAINT ii_pk PRIMARY KEY (ii)
);
