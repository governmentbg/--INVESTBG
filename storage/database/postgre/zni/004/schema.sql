CREATE TABLE  IF NOT EXISTS payments (
	payment serial4 NOT NULL,
	contract int4 NOT NULL,
	payment_date date NOT NULL,
	amount decimal(14,2) NOT NULL,
	CONSTRAINT payments_pk PRIMARY KEY (payment),
	CONSTRAINT payments_contracts_fk FOREIGN KEY (contract) REFERENCES contracts(contract) ON DELETE RESTRICT ON UPDATE RESTRICT
);
