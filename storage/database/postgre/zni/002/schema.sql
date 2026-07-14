CREATE TABLE IF NOT EXISTS maintenance_reports (
	mr serial4 NOT NULL,
	contract integer not null,
	date_from date NOT NULL,
	date_to date NOT NULL,
	status smallint DEFAULT 0,
	report_comment varchar(2000),
	correction_date date,
	correction_attempt smallint DEFAULT 0,
	dme_prev_year integer,
	sme_prev_year integer,
	report_nra_prev_year integer,
	document_annual_reporting_statistics integer,
	other text,
	last_sync date,
	pdf_sign int,
	CONSTRAINT maintenance_reports_pk PRIMARY KEY (mr),
	CONSTRAINT maintenance_reports_contract_contracts_fk FOREIGN KEY (contract)
		REFERENCES contracts(contract) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT maintenance_reports_dpy_uploads_id_fk FOREIGN KEY (dme_prev_year)
		REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT maintenance_reports_spy_uploads_id_fk FOREIGN KEY (sme_prev_year)
		REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT maintenance_reports_dars_uploads_id_fk FOREIGN KEY (document_annual_reporting_statistics)
		REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT maintenance_reports_pdf_sign_uploads_id_fk FOREIGN KEY (pdf_sign)
		REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS maintenance_reports_employees (
	mr int4 NOT NULL,
	workplace_empl int4 NOT NULL,
	comment varchar(500),
	CONSTRAINT maintenance_reports_employees_pk PRIMARY KEY (mr,workplace_empl),
	CONSTRAINT maintenance_reports_employees_mr_fk FOREIGN KEY (mr) REFERENCES maintenance_reports(mr) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT maintenance_reports_employees_workplace_empl_fk FOREIGN KEY (workplace_empl) REFERENCES workplace_empls(workplace_empl) ON DELETE RESTRICT ON UPDATE RESTRICT
);