<?php
class Projektarbeitsbeurteilung_model extends DB_Model
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_projektarbeitsbeurteilung';
		$this->pk = 'projektarbeitsbeurteilung_id';
	}

	/**
	 * Gets data of a Projketarbeitsbeurteilung.
	 * @param $projektarbeit_id int
	 * @param $projektbetreuer_person_id int
	 * @param $student_uid
	 * @return object
	 */
	public function getProjektarbeitsbeurteilung($projektarbeit_id, $projektbetreuer_person_id, $student_uid = null)
	{
		$this->load->model('crm/Student_model', 'StudentModel');
		$this->load->model('crm/Prestudentstatus_model', 'PrestudentstatusModel');
		$this->load->model('codex/Orgform_model', 'OrgformModel');

		$projektarbeitsbeurteilungdata = array();
		$params = array($projektarbeit_id, $projektbetreuer_person_id);

		$qry = "SELECT tbl_projektarbeitsbeurteilung.bewertung AS projektarbeit_bewertung, parbeit.titel AS projektarbeit_titel, parbeit.titel_english AS projektarbeit_titel_english, parbeit.projekttyp_kurzbz AS parbeit_typ,
       		studentpers.vorname AS vorname_student, studentpers.nachname AS nachname_student, studentpers.titelpre AS titelpre_student, studentpers.titelpost AS titelpost_student,
       		tbl_student.matrikelnr AS personenkennzeichen_student, studentben.uid AS uid_student, betreuer.betreuerart_kurzbz AS betreuerart, betreuer.note AS betreuernote,
			betreuerpers.vorname AS vorname_betreuer, betreuerpers.nachname AS nachname_betreuer, betreuerpers.titelpre AS titelpre_betreuer, betreuerpers.titelpost AS titelpost_betreuer,
       		tbl_projektarbeitsbeurteilung.abgeschicktamum, tbl_projektarbeitsbeurteilung.abgeschicktvon
			FROM lehre.tbl_projektarbeit parbeit
			JOIN public.tbl_benutzer studentben ON parbeit.student_uid = studentben.uid
			JOIN public.tbl_person studentpers ON studentben.person_id = studentpers.person_id
			JOIN public.tbl_student ON studentben.uid = tbl_student.student_uid
			JOIN lehre.tbl_projektbetreuer betreuer ON parbeit.projektarbeit_id = betreuer.projektarbeit_id
			JOIN public.tbl_person betreuerpers ON betreuer.person_id = betreuerpers.person_id
			LEFT JOIN extension.tbl_projektarbeitsbeurteilung ON parbeit.projektarbeit_id = tbl_projektarbeitsbeurteilung.projektarbeit_id 
			                                                     AND betreuer.person_id = tbl_projektarbeitsbeurteilung.betreuer_person_id 
			WHERE parbeit.projektarbeit_id = ?
			AND betreuer.person_id = ?
			AND parbeit.projekttyp_kurzbz IN ('Bachelor', 'Diplom')
			AND betreuer.betreuerart_kurzbz IN ('Begutachter', 'Erstbegutachter', 'Zweitbegutachter', 'Kommission')";

		if (isset($student_uid))
		{
			$qry .= " AND tbl_student.student_uid = ?";
			$params[] = $student_uid;

		}

		$qry .= "ORDER BY CASE WHEN betreuer.betreuerart_kurzbz = 'Begutachter' THEN 1 
						WHEN betreuer.betreuerart_kurzbz = 'Erstbegutachter' THEN 2
						WHEN betreuer.betreuerart_kurzbz = 'Zweitbegutachter' THEN 4
						ELSE 5
					END
			LIMIT 1";

		$projektarbeitsbeurteilung = $this->execQuery($qry, $params);
		
		if (isError($projektarbeitsbeurteilung))
			return $projektarbeitsbeurteilung;
		elseif (hasData($projektarbeitsbeurteilung))
		{
			$projektarbeitsbeurteilungdata = getData($projektarbeitsbeurteilung)[0];

			$projektarbeitsbeurteilungdata->projektarbeit_bewertung = json_decode($projektarbeitsbeurteilungdata->projektarbeit_bewertung);

			// get Studiengang of Student
			$student_uid = $projektarbeitsbeurteilungdata->uid_student;
			$this->StudentModel->addSelect('prestudent_id');
			$prestudent_id = $this->StudentModel->load(array('student_uid' => $student_uid));
			
			if (isError($prestudent_id))
				return $prestudent_id;
			elseif (hasData($prestudent_id))
			{
				//get Studiengangname from Studienplan and -ordnung
				$studienordnung = $this->PrestudentstatusModel->getStudienordnungFromPrestudent(getData($prestudent_id)[0]->prestudent_id);
				
				if (isError($studienordnung))
					return $studienordnung;
				elseif (hasData($studienordnung))
				{
					$studienordnungdata = getData($studienordnung)[0];

					$projektarbeitsbeurteilungdata->studiengang_kz = $studienordnungdata->studiengang_kz;
					$projektarbeitsbeurteilungdata->studiengangbezeichnung = $studienordnungdata->studiengangbezeichnung;
					$projektarbeitsbeurteilungdata->studiengangbezeichnung_englisch = $studienordnungdata->studiengangbezeichnung_englisch;
				}
				// if no Studienordnung available (e.g. Incomings), use Studiengangname provided by table student
				elseif (!hasData($studienordnung))
				{
					$this->resetQuery();
					
					$this->load->model('crm/Student_model', 'StudentModel');
					$this->addSelect('studiengang_kz, bezeichnung, english');
					$this->addJoin('public.tbl_studiengang', 'studiengang_kz');
					$result = $this->StudentModel->load(array(
						'student_uid' => $student_uid)
					);
					
					if ($result = getData($result)[0])
					{
						$projektarbeitsbeurteilungdata->studiengang_kz = $result->studiengang_kz;
						$projektarbeitsbeurteilungdata->studiengangbezeichnung = $result->bezeichnung;
						$projektarbeitsbeurteilungdata->studiengangbezeichnung_englisch = $result->english;
					}
				}

				//get last status for orgform
				$lastStatus = $this->PrestudentstatusModel->getLastStatus(getData($prestudent_id)[0]->prestudent_id);

				if (isError($lastStatus))
					return $lastStatus;
				elseif (hasData($lastStatus))
				{
					$lastStatusData = getData($lastStatus)[0];

					// orgform from Studienplan, otherwise from prestudentstatus
					$projektarbeitsbeurteilungdata->orgform_kurzbz = isset($lastStatusData->orgform) ? $lastStatusData->orgform : $lastStatusData->orgform_kurzbz;

					$orgformbezeichnung = $this->OrgformModel->load($projektarbeitsbeurteilungdata->orgform_kurzbz);

					if (hasData($orgformbezeichnung))
					{
						$orgformbezeichnungdata = getData($orgformbezeichnung)[0];
						$projektarbeitsbeurteilungdata->orgform_bezeichnung = $orgformbezeichnungdata->bezeichnung_mehrsprachig;
					}
					else
						return error("no orgformbezeichnung found");
				}
			}
		}

		return success($projektarbeitsbeurteilungdata);
	}

	/**
	 * Gets Zweitbegutachter of a Projektarbeit with a certain Erstbegutachter.
	 * @param int $projektarbeit_id
	 * @param int $erstbetreuer_person_id
	 * @param string $student_uid
	 * @return object
	 */
	public function getZweitbegutachterFromErstbegutachter($projektarbeit_id, $erstbetreuer_person_id, $student_uid)
	{
		$zweitbetrQry = "
			SELECT betr.person_id, beurt.abgeschicktamum
			FROM lehre.tbl_projektbetreuer betr
			JOIN lehre.tbl_projektarbeit parb ON betr.projektarbeit_id = parb.projektarbeit_id 
			LEFT JOIN extension.tbl_projektarbeitsbeurteilung beurt ON betr.projektarbeit_id = beurt.projektarbeit_id
			                                                          AND betr.betreuerart_kurzbz = beurt.betreuerart_kurzbz
			WHERE betr.betreuerart_kurzbz = 'Zweitbegutachter'
			AND betr.projektarbeit_id = ?
			AND parb.student_uid = ?
			AND EXISTS (
			    SELECT 1 FROM lehre.tbl_projektbetreuer
			    WHERE person_id = ?
			    AND betreuerart_kurzbz = 'Erstbegutachter'
			    AND projektarbeit_id = betr.projektarbeit_id
			)
			ORDER BY betr.insertamum DESC
			LIMIT 1";

		return $this->execQuery($zweitbetrQry, array($projektarbeit_id, $student_uid, $erstbetreuer_person_id));
	}

	/**
	 * Gets Erstbegutachter of a Projektarbeit with a certain Zweitbegutachter. Includes data of student.
	 * @param $projektarbeit_id int
	 * @param $zweitbegutachter_person_id int
	 * @return object
	 */
	public function getErstbegutachterFromZweitbegutachter($projektarbeit_id, $zweitbegutachter_person_id)
	{
		$zweitbetrQry = "
			SELECT betr.person_id, ben.uid, parb.student_uid, pers.anrede, pers.geschlecht, stud_pers.anrede AS student_anrede,
					trim(COALESCE(pers.titelpre,'')||' '||COALESCE(pers.vorname,'')||' '||COALESCE(pers.nachname,'')||' '||COALESCE(pers.titelpost,'')) AS fullname,
					trim(COALESCE(stud_pers.titelpre,'')||' '||COALESCE(stud_pers.vorname,'')||' '||COALESCE(stud_pers.nachname,'')||' '||COALESCE(stud_pers.titelpost,'')) AS student_fullname
			FROM lehre.tbl_projektbetreuer betr
			JOIN lehre.tbl_projektarbeit parb ON betr.projektarbeit_id = parb.projektarbeit_id
			JOIN public.tbl_person pers ON betr.person_id = pers.person_id
			JOIN public.tbl_benutzer stud_ben ON parb.student_uid = stud_ben.uid
			JOIN public.tbl_person stud_pers ON stud_ben.person_id = stud_pers.person_id
			LEFT JOIN extension.tbl_projektarbeitsbeurteilung beurt ON betr.projektarbeit_id = beurt.projektarbeit_id
			                                                          AND betr.betreuerart_kurzbz = beurt.betreuerart_kurzbz
			LEFT JOIN public.tbl_benutzer ben ON betr.person_id = ben.person_id AND ben.aktiv = TRUE
			LEFT JOIN public.tbl_mitarbeiter ma ON ben.uid = ma.mitarbeiter_uid
			WHERE betr.betreuerart_kurzbz = 'Erstbegutachter'
			AND betr.projektarbeit_id = ?
			AND beurt.abgeschicktamum IS NULL
			AND EXISTS (
			    SELECT 1 FROM lehre.tbl_projektbetreuer
			    WHERE person_id = ?
			    AND betreuerart_kurzbz = 'Zweitbegutachter'
			    AND projektarbeit_id = betr.projektarbeit_id
			)
			ORDER BY CASE WHEN ma.mitarbeiter_uid IS NULL THEN 1 ELSE 0 END, /* prefer mitarbeiter accounts */
			         CASE WHEN ben.uid IS NULL THEN 1 ELSE 0 END,
			         betr.insertamum DESC
			LIMIT 1";

		return $this->execQuery($zweitbetrQry, array($projektarbeit_id, $zweitbegutachter_person_id));
	}
}
