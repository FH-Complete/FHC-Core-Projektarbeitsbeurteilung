ALTER table lehre.tbl_projektbetreuer ADD COLUMN IF NOT EXISTS zugangstoken VARCHAR(32);
COMMENT ON COLUMN lehre.tbl_projektbetreuer.zugangstoken IS 'Zugangstoken zur Projektarbeitsbewertung fuer externe Betreuer';

ALTER table lehre.tbl_projektbetreuer ADD COLUMN IF NOT EXISTS zugangstoken_gueltigbis date;
COMMENT ON COLUMN lehre.tbl_projektbetreuer.zugangstoken_gueltigbis IS 'Gueligkeitsdatum fuer Zugangstoken zur Projektarbeitsbewertung fuer externe Betreuer';