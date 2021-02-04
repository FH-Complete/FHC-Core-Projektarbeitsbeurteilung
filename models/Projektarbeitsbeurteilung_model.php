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
	 * Gets data of an Projketarbeitsbeurteilung
	 * @param $abschlusspruefung_id
	 * @return object
	 */
	public function getProjektarbeitsbeurteilung($projektarbeit_id, $projektbetreuer_person_id, $student_uid)
	{
		$this->load->model('crm/Student_model', 'StudentModel');
		$this->load->model('crm/Prestudentstatus_model', 'PrestudentstatusModel');
		$this->load->model('codex/Orgform_model', 'OrgformModel');
/*		$this->load->model('education/Projektarbeit_model', 'ProjektarbeitModel');
		$this->load->model('organisation/Studiengang_model', 'StudiengangModel');*/

		$projektarbeitsbeurteilungdata = array();

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
			AND tbl_student.student_uid = ?
			AND parbeit.projekttyp_kurzbz IN ('Bachelor', 'Diplom')
			ORDER BY CASE WHEN betreuer.betreuerart_kurzbz = 'Begutachter' THEN 1 
			    			WHEN betreuer.betreuerart_kurzbz = 'Erstbegutachter' THEN 2
			    			WHEN betreuer.betreuerart_kurzbz = 'Betreuer' THEN 3
			    			WHEN betreuer.betreuerart_kurzbz = 'Zweitbegutachter' THEN 4
							ELSE 5
						END
			LIMIT 1
		";

		$params = array($projektarbeit_id, $projektbetreuer_person_id, $student_uid);

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

				//get last statusfor orgform
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
}
