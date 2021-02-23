CREATE SEQUENCE IF NOT EXISTS extension.tbl_projektarbeitsbeurteilung_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_projektarbeitsbeurteilung_id_seq TO vilesci;
GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_projektarbeitsbeurteilung_id_seq TO fhcomplete;
GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_projektarbeitsbeurteilung_id_seq TO web;

CREATE TABLE IF NOT EXISTS extension.tbl_projektarbeitsbeurteilung (
    projektarbeitsbeurteilung_id integer NOT NULL DEFAULT nextval('extension.tbl_projektarbeitsbeurteilung_id_seq'::regclass),
    projektarbeit_id integer NOT NULL,
    betreuer_person_id integer NOT NULL,
    betreuerart_kurzbz varchar(16) NOT NULL,
    bewertung jsonb NOT NULL,
    abgeschicktamum timestamp,
    abgeschicktvon varchar(32),
    insertamum timestamp default now(),
    insertvon varchar(32),
    updateamum timestamp
);

DO $$
BEGIN
    ALTER TABLE extension.tbl_projektarbeitsbeurteilung ADD CONSTRAINT pk_projektarbeitsbeurteilung PRIMARY KEY (projektarbeitsbeurteilung_id);
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE extension.tbl_projektarbeitsbeurteilung ADD CONSTRAINT fk_projektarbeitsbeurteilung_projektbetreuer FOREIGN KEY (projektarbeit_id, betreuer_person_id, betreuerart_kurzbz) REFERENCES lehre.tbl_projektbetreuer (projektarbeit_id, person_id, betreuerart_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_projektarbeitsbeurteilung TO vilesci;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_projektarbeitsbeurteilung TO fhcomplete;
GRANT SELECT, UPDATE, INSERT ON TABLE extension.tbl_projektarbeitsbeurteilung TO web;
COMMENT ON TABLE extension.tbl_projektarbeitsbeurteilung IS 'Tabelle zur Verwaltung von Projektarbeitsbeurteilungen';