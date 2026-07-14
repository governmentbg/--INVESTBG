CREATE TABLE IF NOT EXISTS sample_documents (
	sample_document serial4 NOT NULL,
    name varchar(500) not null,
	file integer not null,
	CONSTRAINT sample_documents_pk PRIMARY KEY (sample_document),
    CONSTRAINT sample_documents_file_uploads_id_fk FOREIGN KEY (file)
        REFERENCES uploads(id) ON DELETE RESTRICT ON UPDATE RESTRICT
);
