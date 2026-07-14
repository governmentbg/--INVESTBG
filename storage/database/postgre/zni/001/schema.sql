CREATE TABLE companies (
	company serial4 NOT NULL,
	id varchar(15) NOT NULL,
	id_type int2 NOT NULL,
	company_name text NULL,
	company_address text NULL,
	company_email text NULL,
	company_region int2 NULL,
	company_municipality int2 NULL,
	company_city int4 NULL,
	created date NULL,
	CONSTRAINT companies_pk PRIMARY KEY (company),
	CONSTRAINT companies_cities_fk FOREIGN KEY (company_city) REFERENCES cities(city) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT companies_municipalities_fk FOREIGN KEY (company_municipality) REFERENCES municipalities(municipality) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT companies_regions_fk FOREIGN KEY (company_region) REFERENCES regions(region) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS company_egns (
	company int4 NOT NULL,
	egn varchar(10) NOT NULL,
	moderator int2 NOT NULL,
	"name" text NULL,
	CONSTRAINT companies_egns_pk PRIMARY KEY (company, egn),
	CONSTRAINT companies_egns_company_fk FOREIGN KEY (company) REFERENCES companies(company)
);

CREATE TABLE IF NOT EXISTS  contracts (
	contract serial4 NOT NULL,
	contract_type int2 NULL,
	company int4 NOT NULL,
	company_type int2 NULL,
	city int4,
	date_application date NULL,
	sector int2 NULL,
	sector_activity int2 NULL,
	cert_date date NULL,
	cert_expire date NULL,
	cert_number varchar(100) NULL,
	cert_type int2 NULL,
	contract_number text NULL,
	contract_date date NULL,
	contract_term date NULL,
	period_reporting int2 NULL,
	period_maintenance_start date NULL,
	period_maintenance_end date NULL,
	period_invest_start date NULL,
	period_invest_end date NULL,
	currency varchar(3) DEFAULT 'eur',
	period_value decimal(10,2) DEFAULT 0,
	invest_amount decimal(10,2) DEFAULT 0,
	number_persons int4 DEFAULT 0,
	declaration text NULL,
	files text NULL,
	CONSTRAINT contracts_pk PRIMARY KEY (contract),
	CONSTRAINT contracts_company_fk FOREIGN KEY (company) REFERENCES companies(company) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT contracts_cities_fk FOREIGN KEY (city) REFERENCES cities(city) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT contracts_nom_certificate_types_fk FOREIGN KEY (cert_type) REFERENCES nom_certificate_types(certificate_type) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT contracts_nom_company_types_fk FOREIGN KEY (company_type) REFERENCES nom_company_types(company_type) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT contracts_nom_contract_types_fk FOREIGN KEY (contract_type) REFERENCES nom_contract_types(contract_type) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT contracts_nom_reporting_periods_fk FOREIGN KEY (period_reporting) REFERENCES nom_reporting_periods(reporting_period) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT contracts_nom_sector_activities_fk FOREIGN KEY (sector_activity) REFERENCES nom_sector_activities(sector_activity) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT contracts_nom_sectors_fk FOREIGN KEY (sector) REFERENCES nom_sectors(sector) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS contract_kid (
	contract int4 NOT NULL,
	kid int4 NOT NULL,
	CONSTRAINT contract_kid_pk PRIMARY KEY (contract,kid),
	CONSTRAINT contract_kid_contracts_fk FOREIGN KEY (contract) REFERENCES contracts(contract) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT contract_kid_nom_kid_fk FOREIGN KEY (kid) REFERENCES nom_kid(kid) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS contract_users (
	contract int4 NOT NULL,
	usr int4 NOT NULL,
	CONSTRAINT contract_users_pk PRIMARY KEY (contract,usr),
	CONSTRAINT contract_users_contracts_fk FOREIGN KEY (contract) REFERENCES contracts(contract) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT contract_users_users_fk FOREIGN KEY (usr) REFERENCES users(usr) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS contract_orders (
	contact_order serial4 NOT NULL,
	contract int4 ,
	order_date date NULL,
	order_date_return date NULL,
	order_amount decimal(10,2) NULL,
	CONSTRAINT contract_orders_pk PRIMARY KEY (contact_order, contract),
	CONSTRAINT contract_orders_contracts_fk FOREIGN KEY (contract) REFERENCES contracts(contract) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS contract_banks (
	contact_bank serial4 NOT NULL,
	contract int4 NULL,
	bank_date_from date NULL,
	bank_date_to date NULL,
	bank_amount decimal(10,2) NULL,
	bank_name text NULL,
	CONSTRAINT contract_banks_pk PRIMARY KEY (contact_bank, contract),
	CONSTRAINT contract_banks_contracts_fk FOREIGN KEY (contract) REFERENCES contracts(contract) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS employees (
	employee serial4 NOT NULL,
	identifirer_type int2 NULL,
	identifirer varchar(11) NULL,
	"name" text NULL,
	CONSTRAINT employees_pk PRIMARY KEY (employee)
);

CREATE TABLE IF NOT EXISTS reports (
	report serial4 NOT NULL,
	contract_id int4 NULL,
	date_from date NULL,
	date_to date NULL,
	status int2 NULL,
	"locked" int2 NULL,
	workplaces int2 NULL,
	report_number int2 NULL,
	general_comment text NULL,
	correction_numb int2 NULL,
	correction_end_date date NULL,
	percent_second decimal(6, 2) NOT NULL,
	percent_third decimal(6, 2) NOT NULL,
	mir_doc int4,
	mir_checklist int4,
	pdf_sign int4,
	CONSTRAINT reports_pk PRIMARY KEY (report),
	CONSTRAINT reports_contracts_fk FOREIGN KEY (contract_id) REFERENCES contracts(contract) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT reports_mir_doc_fk FOREIGN KEY (mir_doc) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT reports_mir_checklist_fk FOREIGN KEY (mir_checklist) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT reports_pdf_sign_fk FOREIGN KEY (pdf_sign) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS reports_imports (
	ri serial4 NOT NULL,
	report int4 NOT NULL,
	usr int4 NOT NULL,
	created_at timestamp DEFAULT now() NULL,
	file_id int4 NOT NULL,
	type int2 DEFAULT 0,
	CONSTRAINT reports_imports_pk PRIMARY KEY (ri),
	CONSTRAINT reports_imports_report_id_fk FOREIGN KEY (report) REFERENCES reports(report) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT reports_imports_usr_fk FOREIGN KEY (usr) REFERENCES users(usr) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT reports_imports_file_id_fk FOREIGN KEY (file_id) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS workplaces (
	workplace serial4  NOT NULL,
	report_id int4 NOT NULL,
	position_id int4 NULL,
	workplace_no int4 NOT NULL,
	CONSTRAINT worklplaces_pk PRIMARY KEY (workplace),
	CONSTRAINT worklplaces_nom_nkpd_fk FOREIGN KEY (position_id) REFERENCES nom_nkpd(nkpd) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT worklplaces_reports_fk FOREIGN KEY (report_id) REFERENCES reports(report) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS workplace_empls (
	workplace_empl serial4 NOT NULL,
	workplace_id int4 NOT NULL,
	employee_id int4 NOT NULL,
	start_date date NULL,
	end_date date NULL,
	refund_sum numeric(10, 2) DEFAULT 0 NULL,
	salary_amount numeric(10, 2) NULL,
	project_start_date date NULL,
	not_empl_report int2 DEFAULT 0 NULL,
	last_amend_date date NULL,
	reason text NULL,
	eco_code text NULL,
	profession text NULL,
	ekatte varchar NULL,
	last_term text NULL,
	sync_status int2 NULL,
	last_sync date NULL,
	type_expense int4 NULL,
	CONSTRAINT workplace_empls_pk PRIMARY KEY (workplace_empl),
	CONSTRAINT workplace_empls_employees_fk FOREIGN KEY (employee_id) REFERENCES employees(employee) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT workplace_empls_nom_type_expense_fk FOREIGN KEY (type_expense) REFERENCES nom_type_expense(type_expense) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT workplace_empls_worklplaces_fk FOREIGN KEY (workplace_id) REFERENCES workplaces(workplace) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS report_documents (
	id serial4 NOT NULL,
	report_id int4 NOT NULL,
	payment_request int4 NULL,
	technical_report int4 NULL,
	financial_report int4 NULL,
	employment_contracts_report int4 NULL,
	other_public_funding_report int4 NULL,
	expenses_eligibility_declaration int4 NULL,
	auditor_report int4 NULL,
	state_aid_declaration int4 NULL,
	statistics_documents int4 NULL,
	other_documents text NULL,
	created_at timestamp DEFAULT now() NULL,
	updated_at timestamp DEFAULT now() NULL,
	CONSTRAINT report_documents_pkey PRIMARY KEY (id),
	CONSTRAINT report_documents_reports_fk FOREIGN KEY (report_id) REFERENCES reports(report) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT report_documents_uploads_fk FOREIGN KEY (payment_request) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT report_documents_uploads_fk_0 FOREIGN KEY (technical_report) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT report_documents_uploads_fk_1 FOREIGN KEY (financial_report) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT report_documents_uploads_fk_2 FOREIGN KEY (employment_contracts_report) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT report_documents_uploads_fk_3 FOREIGN KEY (other_public_funding_report) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT report_documents_uploads_fk_4 FOREIGN KEY (expenses_eligibility_declaration) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT report_documents_uploads_fk_5 FOREIGN KEY (auditor_report) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT report_documents_uploads_fk_6 FOREIGN KEY (state_aid_declaration) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
	CONSTRAINT report_documents_uploads_fk_7 FOREIGN KEY (statistics_documents) REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS employee_salary (
   employee_salary serial4 NOT NULL,
    workplace_empl int4 NULL,
    salary decimal(10,2) NULL,
    insurance decimal(10,2) NULL,
    "percent" decimal(10,2) NULL,
    "month" int2 NULL,
    "year" int2 NULL,
    CONSTRAINT employee_salary_pk PRIMARY KEY (employee_salary),
    CONSTRAINT fk_employee_salary_workplace_empl FOREIGN KEY (workplace_empl) REFERENCES workplace_empls(workplace_empl) ON DELETE RESTRICT
);

CREATE UNIQUE INDEX IF NOT EXISTS ux_employee_salary_unique
on employee_salary (month, year, workplace_empl);



CREATE TABLE IF NOT EXISTS report_employee_comments (
	report_id int4 NOT NULL,
	workplace_empl int4 NOT NULL,
	"comment" text NOT NULL,
	created_at timestamp DEFAULT now() NULL,
	updated_at timestamp DEFAULT now() NULL,
	CONSTRAINT report_employee_comments_pkey PRIMARY KEY (report_id, workplace_empl),
	CONSTRAINT fk_rec_workplace_empl FOREIGN KEY (workplace_empl) REFERENCES workplace_empls(workplace_empl) ON DELETE CASCADE,
	CONSTRAINT fk_rpc_report FOREIGN KEY (report_id) REFERENCES reports(report) ON DELETE CASCADE
);