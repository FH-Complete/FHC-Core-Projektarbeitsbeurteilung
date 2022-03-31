CREATE OR REPLACE FUNCTION lehre.projektarbeit_testcase(betreuer_person_id int, bachelor_stud_uid varchar, master_stud_uid varchar)
    returns void
AS
$func$
DECLARE
    semester_kurzbz varchar;
    pers_id integer;
    ps_id integer;
    stg_kz int;
    orgf_kurzbz varchar;
    lehreinh_id_bachelor int;
    lehreinh_id_master int;
    paarbeit_id int;
    alternativbetreuer_person_id int;
BEGIN
    -- define variables
    lehreinh_id_bachelor := 118711;
    lehreinh_id_master := 122975;
    alternativbetreuer_person_id := 36510; -- used as second betreuer, Prüfungssenatmiglied etc.

    -- /****************************** Student Bachelor add /******************************

    if bachelor_stud_uid IS NULL then

        -- define studiengang_kz
        stg_kz := 256;

        -- get studiensemester from lehreinheit
        SELECT studiensemester_kurzbz  INTO semester_kurzbz
        FROM lehre.tbl_lehreinheit
        WHERE lehreinheit_id = lehreinh_id_bachelor;

        --get orgform from studiengang
        SELECT orgform_kurzbz  INTO orgf_kurzbz
        FROM public.tbl_studiengang
        WHERE studiengang_kz = stg_kz;

        -- create student
        INSERT INTO public.tbl_person (vorname, nachname, gebdatum) VALUES ('Projektarbeittest', 'Projektarbeittest', '2000-01-01')
        returning person_id into pers_id; --< store the returned ID in local variable

        RAISE NOTICE 'Person angelegt: %', pers_id;

        bachelor_stud_uid := 'baarbeitteststudent_' || pers_id;

        INSERT INTO public.tbl_prestudent (person_id, studiengang_kz) VALUES (pers_id, stg_kz)
        returning prestudent_id into ps_id; --< store the returned ID in local variable

        RAISE NOTICE 'Prestudent angelegt: %', ps_id;

        INSERT INTO public.tbl_prestudentstatus (prestudent_id, status_kurzbz, studiensemester_kurzbz, orgform_kurzbz) VALUES (ps_id, 'Student', semester_kurzbz, orgf_kurzbz)
        returning prestudent_id into ps_id; --< store the returned ID in local variable

        RAISE NOTICE 'Prestudentstatus angelegt: %', ps_id;

        INSERT INTO public.tbl_benutzer (uid, person_id) VALUES (bachelor_stud_uid, pers_id);

        RAISE NOTICE 'Benutzer angelegt: %', bachelor_stud_uid;

        INSERT INTO public.tbl_student (student_uid, prestudent_id, studiengang_kz, semester, verband, gruppe) VALUES (bachelor_stud_uid, ps_id, stg_kz, 1, '', '');

        RAISE NOTICE 'Student angelegt: %', bachelor_stud_uid;

    end if;

    -- /****************************** Student Master add /******************************

    if master_stud_uid IS NULL then

        -- define studiengang_kz
        stg_kz := 302;

        --get orgform from studiengang
        SELECT orgform_kurzbz  INTO orgf_kurzbz
        FROM public.tbl_studiengang
        WHERE studiengang_kz = stg_kz;

        -- create student
        INSERT INTO public.tbl_person (vorname, nachname, gebdatum) VALUES ('Projektarbeittest', 'Projektarbeittest', '2000-01-01')
        returning person_id into pers_id; --< store the returned ID in local variable

        RAISE NOTICE 'Person angelegt: %', pers_id;

        bachelor_stud_uid := 'maarbeitteststudent_' || pers_id;

        INSERT INTO public.tbl_prestudent (person_id, studiengang_kz) VALUES (pers_id, stg_kz)
        returning prestudent_id into ps_id; --< store the returned ID in local variable

        RAISE NOTICE 'Prestudent angelegt: %', ps_id;

        INSERT INTO public.tbl_prestudentstatus (prestudent_id, status_kurzbz, studiensemester_kurzbz, orgform_kurzbz) VALUES (ps_id, 'Student', semester_kurzbz, orgf_kurzbz)
        returning prestudent_id into ps_id; --< store the returned ID in local variable

        RAISE NOTICE 'Prestudentstatus angelegt: %', ps_id;

        INSERT INTO public.tbl_benutzer (uid, person_id) VALUES (bachelor_stud_uid, pers_id);

        RAISE NOTICE 'Benutzer angelegt: %', bachelor_stud_uid;

        INSERT INTO public.tbl_student (student_uid, prestudent_id, studiengang_kz, semester, verband, gruppe) VALUES (bachelor_stud_uid, ps_id, stg_kz, 1, '', '');

        RAISE NOTICE 'Student angelegt: %', bachelor_stud_uid;

    end if;

    -- /****************************** Bachelor Erstbegutachter /******************************

    -- create bachelor projektarbeit with given Erstbetreuer
    INSERT INTO lehre.tbl_projektarbeit (projekttyp_kurzbz, titel, lehreinheit_id, student_uid, insertamum) VALUES ('Bachelor', 'Test Bachelor Erstbetreuer', lehreinh_id_bachelor, bachelor_stud_uid, now())
    returning projektarbeit_id into paarbeit_id; --< store the returned ID in local variable;

    RAISE NOTICE 'Projektarbeit Bachelor for Erstbetreuer Test angelegt: %', paarbeit_id;

    -- assign betreuer
    INSERT INTO lehre.tbl_projektbetreuer (person_id, projektarbeit_id, betreuerart_kurzbz) VALUES (betreuer_person_id, paarbeit_id, 'Begutachter');

    RAISE NOTICE 'Projektarbeit Bachelor Erstbetreuer zugewiesen';

    -- create endabgabe (as if already uploaded)
    INSERT INTO campus.tbl_paabgabe (projektarbeit_id, paabgabetyp_kurzbz, datum, abgabedatum) VALUES (paarbeit_id, 'end', now(), now());

    RAISE NOTICE 'Projektarbeit Endabgabe hinzugefügt';

    -- /****************************** Bachelor Erstbegutachter with Prüfungssenat/******************************

    -- create bachelor projektarbeit with given Erstbetreuer
    INSERT INTO lehre.tbl_projektarbeit (projekttyp_kurzbz, titel, lehreinheit_id, student_uid, insertamum) VALUES ('Bachelor', 'Test Bachelor Erstbetreuer mit Prüfungssenat', lehreinh_id_bachelor, bachelor_stud_uid, now())
    returning projektarbeit_id into paarbeit_id; --< store the returned ID in local variable;

    RAISE NOTICE 'Projektarbeit Bachelor for Erstbetreuer with Prüfungssenat Test angelegt: %', paarbeit_id;

    -- assign betreuer
    INSERT INTO lehre.tbl_projektbetreuer (person_id, projektarbeit_id, betreuerart_kurzbz) VALUES (betreuer_person_id, paarbeit_id, 'Begutachter');

    RAISE NOTICE 'Projektarbeit Bachelor Erstbetreuer zugewiesen';

    -- assign Prüfungssenatsmitglied
    INSERT INTO lehre.tbl_projektbetreuer (person_id, projektarbeit_id, betreuerart_kurzbz) VALUES (alternativbetreuer_person_id, paarbeit_id, 'Kommission');

    RAISE NOTICE 'Projektarbeit Bachelor Prüfungssenatmiglied zugewiesen';

    -- create endabgabe (as if already uploaded)
    INSERT INTO campus.tbl_paabgabe (projektarbeit_id, paabgabetyp_kurzbz, datum, abgabedatum) VALUES (paarbeit_id, 'end', now(), now());

    RAISE NOTICE 'Projektarbeit Endabgabe hinzugefügt';

    -- /****************************** Bachelor Prüfungssenatsmitglied /******************************

    -- create bachelor projektarbeit with given Erstbetreuer
    INSERT INTO lehre.tbl_projektarbeit (projekttyp_kurzbz, titel, lehreinheit_id, student_uid, insertamum) VALUES ('Bachelor', 'Test Bachelor Prüfungssenatsmitglied', lehreinh_id_bachelor, bachelor_stud_uid, now())
    returning projektarbeit_id into paarbeit_id; --< store the returned ID in local variable;

    RAISE NOTICE 'Projektarbeit Bachelor für Prüfungssenatsmitglied Test angelegt: %', paarbeit_id;

    -- assign betreuer
    INSERT INTO lehre.tbl_projektbetreuer (person_id, projektarbeit_id, betreuerart_kurzbz) VALUES (alternativbetreuer_person_id, paarbeit_id, 'Begutachter');

    RAISE NOTICE 'Projektarbeit Bachelor Erstbetreuer zugewiesen';

    -- assign Prüfungssenatsmitglied
    INSERT INTO lehre.tbl_projektbetreuer (person_id, projektarbeit_id, betreuerart_kurzbz) VALUES (betreuer_person_id, paarbeit_id, 'Kommission');

    RAISE NOTICE 'Projektarbeit Bachelor Prüfungssenatmitglied zugewiesen';

    -- create endabgabe (as if already uploaded)
    INSERT INTO campus.tbl_paabgabe (projektarbeit_id, paabgabetyp_kurzbz, datum, abgabedatum) VALUES (paarbeit_id, 'end', now(), now());

    RAISE NOTICE 'Projektarbeit Endabgabe hinzugefügt';

    -- /****************************** Master Erstbetreuer /******************************

    -- create master projektarbeit with given Erstbetreuer
    INSERT INTO lehre.tbl_projektarbeit (projekttyp_kurzbz, titel, lehreinheit_id, student_uid, insertamum) VALUES ('Diplom', 'Test Master Erstbetreuer', lehreinh_id_master, master_stud_uid, now())
    returning projektarbeit_id into paarbeit_id; --< store the returned ID in local variable;

    RAISE NOTICE 'Projektarbeit Master für Erstbetreuer angelegt: %', paarbeit_id;

    -- assign Betreuer
    INSERT INTO lehre.tbl_projektbetreuer (person_id, projektarbeit_id, betreuerart_kurzbz) VALUES (betreuer_person_id, paarbeit_id, 'Erstbegutachter');

    RAISE NOTICE 'Projektarbeit Master Erstbetreuer zugewiesen';

    -- assign Zweitbetreuer
    INSERT INTO lehre.tbl_projektbetreuer (person_id, projektarbeit_id, betreuerart_kurzbz) VALUES (alternativbetreuer_person_id, paarbeit_id, 'Zweitbegutachter');

    RAISE NOTICE 'Projektarbeit Master Zweitbetreuer zugewiesen';

    -- create endabgabe (as if already uploaded)
    INSERT INTO campus.tbl_paabgabe (projektarbeit_id, paabgabetyp_kurzbz, datum, abgabedatum) VALUES (paarbeit_id, 'end', now(), now());

    RAISE NOTICE 'Projektarbeit Endabgabe hinzugefügt';

    -- /****************************** Master Zweitbetreuer /******************************

    -- create master projektarbeit with given Erstbetreuer
    INSERT INTO lehre.tbl_projektarbeit (projekttyp_kurzbz, titel, lehreinheit_id, student_uid, insertamum) VALUES ('Diplom', 'Test Master Zweitbetreuer', lehreinh_id_master, master_stud_uid, now())
    returning projektarbeit_id into paarbeit_id; --< store the returned ID in local variable;

    RAISE NOTICE 'Projektarbeit Master für Zweitbetreuer angelegt: %', paarbeit_id;

    -- assign Betreuer
    INSERT INTO lehre.tbl_projektbetreuer (person_id, projektarbeit_id, betreuerart_kurzbz) VALUES (alternativbetreuer_person_id, paarbeit_id, 'Erstbegutachter');

    RAISE NOTICE 'Projektarbeit Bachelor Erstbetreuer zugewiesen';

    -- assign Zweitbetreuer
    INSERT INTO lehre.tbl_projektbetreuer (person_id, projektarbeit_id, betreuerart_kurzbz) VALUES (betreuer_person_id, paarbeit_id, 'Zweitbegutachter');

    RAISE NOTICE 'Projektarbeit Bachelor Prüfungssenatmiglied zugewiesen';

    -- create endabgabe (as if already uploaded)
    INSERT INTO campus.tbl_paabgabe (projektarbeit_id, paabgabetyp_kurzbz, datum, abgabedatum, insertamum) VALUES (paarbeit_id, 'end', now(), now(), now());

    RAISE NOTICE 'Projektarbeit Endabgabe hinzugefügt';
END
$func$ LANGUAGE plpgsql;

SELECT lehre.projektarbeit_testcase(27912, 'el21b501', 'wi21m501');