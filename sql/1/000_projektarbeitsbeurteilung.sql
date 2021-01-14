CREATE SEQUENCE IF NOT EXISTS lehre.tbl_projektarbeitsbeurteilung_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

GRANT SELECT, UPDATE ON SEQUENCE lehre.tbl_projektarbeitsbeurteilung_id_seq TO vilesci;
GRANT SELECT, UPDATE ON SEQUENCE lehre.tbl_projektarbeitsbeurteilung_id_seq TO fhcomplete;

CREATE TABLE IF NOT EXISTS lehre.tbl_projektarbeitsbeurteilung (
    projektarbeitsbeurteilung_id integer NOT NULL DEFAULT nextval('lehre.tbl_projektarbeitsbeurteilung_id_seq'::regclass),
    projektarbeit_id integer NOT NULL,
    projektbetreuer_person_id integer NOT NULL,
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
    ALTER TABLE lehre.tbl_projektarbeitsbeurteilung ADD CONSTRAINT pk_projektarbeitsbeurteilung PRIMARY KEY (projektarbeitsbeurteilung_id);
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE lehre.tbl_projektarbeitsbeurteilung ADD CONSTRAINT fk_projektarbeitsbeurteilung_projektarbeit_id FOREIGN KEY (projektarbeit_id, projektbetreuer_person_id, betreuerart_kurzbz) REFERENCES lehre.tbl_projektbetreuer (projektarbeit_id, person_id, betreuerart_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT ON TABLE lehre.tbl_projektarbeitsbeurteilung TO web;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE lehre.tbl_projektarbeitsbeurteilung TO vilesci;
COMMENT ON TABLE lehre.tbl_projektarbeitsbeurteilung IS 'Tabelle zur Verwaltung von Projektarbeitsbeurteilungen';