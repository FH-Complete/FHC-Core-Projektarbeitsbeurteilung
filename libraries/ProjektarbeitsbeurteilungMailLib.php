<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Library for sending mails during Projektarbeitsbeurteilung process.
 */
class ProjektarbeitsbeurteilungMailLib
{
	private $_ci;

	public function __construct()
	{
		$this->_ci =& get_instance();

		$this->_ci->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$this->_ci->load->model('education/Projektbetreuer_model', 'ProjektbetreuerModel');
		$this->_ci->load->model('extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung_model', 'ProjektarbeitsbeurteilungModel');

		$this->_ci->load->library('DmsLib');

		$this->_ci->load->helper('hlp_sancho_helper');
	}

	/**
	 * Sends sancho infomail to Erstbegutachter after Zweitbegutachter finished assessment.
	 * @param $projektarbeit_id int
	 * @param $zweitbetreuer_person_id int
	 * @return object success or error
	 */
	public function sendInfoMailToErstbegutachter($projektarbeit_id, $zweitbetreuer_person_id)
	{
		$this->_ci->load->model('person/Benutzer_model', 'BenutzerModel');

		$erstbetreuerRes = $this->_ci->ProjektarbeitsbeurteilungModel->getErstbegutachterFromZweitbegutachter($projektarbeit_id, $zweitbetreuer_person_id);

		if (!hasData($erstbetreuerRes))
			return error("no Erstbetreuer found");

		$erstbetreuerData = getData($erstbetreuerRes)[0];
		$erstbegutachter_person_id = $erstbetreuerData->person_id;
		$receiver_uid = $erstbetreuerData->uid;
		$student_uid = $erstbetreuerData->student_uid;

		$receiverMail = $receiver_uid.'@'.DOMAIN;

		$mailcontent_data_arr = array(
			'link' => site_url() . "/extensions/FHC-Core-Projektarbeitsbeurteilung/ProjektarbeitsbeurteilungErstbegutachter"
			."?projektarbeit_id=$projektarbeit_id&uid=$student_uid",
			'anrede' => $erstbetreuerData->anrede,
			'betreuer_voller_name' => $erstbetreuerData->fullname,
			'student_voller_name' => $erstbetreuerData->student_fullname,
			'geehrt' => "geehrte".($erstbetreuerData->geschlecht=="m"?"r":"")
		);

		sendSanchoMail(
			'ParbeitsbeurteilungMoeglich',
			$mailcontent_data_arr,
			$receiverMail,
			'Projektarbeitsbeurteilung möglich',
			'sancho_header_min_bw.jpg',
			'sancho_footer_min_bw.jpg'
		);

		return success($erstbegutachter_person_id);
	}

	/**
	 * Sends sancho infomail to Studiengang after Erstbegutachter finished assessment.
	 * @param $projektarbeit_id int
	 * @param $betreuer_person_id int
	 * @return object success or error
	 */
	public function sendInfoMailToStudiengang($projektarbeit_id, $betreuer_person_id)
	{
		// get Beurteilung
		$projektarbeitsbeurteilungres = $this->_ci->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung(
			$projektarbeit_id,
			$betreuer_person_id,
			null,
			array(
				AbstractProjektarbeitsbeurteilung::BETREUERART_BACHELOR_BEGUTACHTER,
				AbstractProjektarbeitsbeurteilung::BETREUERART_ERSTBEGUTACHTER,
				AbstractProjektarbeitsbeurteilung::BETREUERART_SENATSVORSITZ,
				AbstractProjektarbeitsbeurteilung::BETREUERART_ZWEITBEGUTACHTER
			)
		);

		if (!hasData($projektarbeitsbeurteilungres))
			return error('Projektarbeitsbeurteilung not found');

		$projektarbeitsbeurteilung = getData($projektarbeitsbeurteilungres);

		// get Studiengang
		$studiengang_kz = $projektarbeitsbeurteilung->studiengang_kz;

		$this->_ci->StudiengangModel->addSelect('email');
		$studiengangres = $this->_ci->StudiengangModel->load($studiengang_kz);

		if (!hasData($studiengangres))
			return error('Studiengang not found');

		$studiengang_email = getData($studiengangres)[0]->email;

		// get Betreuer and Student names
		$betreuer_fullname = implode(
			' ',
			array_filter(
				array(
					$projektarbeitsbeurteilung->titelpre_betreuer,
					$projektarbeitsbeurteilung->vorname_betreuer,
					$projektarbeitsbeurteilung->nachname_betreuer,
					$projektarbeitsbeurteilung->titelpost_betreuer
				)
			)
		);

		$student_fullname = implode(
			' ',
			array_filter(
				array(
					$projektarbeitsbeurteilung->titelpre_student,
					$projektarbeitsbeurteilung->vorname_student,
					$projektarbeitsbeurteilung->nachname_student,
					$projektarbeitsbeurteilung->titelpost_student
				)
			)
		);

		$mailcontent_data_arr = array(
			'betreuer_voller_name' => $betreuer_fullname,
			'student_voller_name' => $student_fullname,
			'betreuer_art' => $projektarbeitsbeurteilung->betreuerart
		);

		// send mail with retrieved data
		sendSanchoMail(
			'ParbeitsbeurteilungInfoAnStg',
			$mailcontent_data_arr,
			$studiengang_email,
			'Projektarbeitsbeurteilung abgeschlossen',
			'sancho_header_min_bw.jpg',
			'sancho_footer_min_bw.jpg'
		);

		return success($studiengang_email);
	}

	/**
	 * Sends sancho infomail to student after Erstbegutachter finished assessment.
	 * @param $projektarbeit_id int
	 * @param $betreuer_person_id int
	 * @return object success or error
	 */
	public function sendInfoMailToStudent($projektarbeit_id, $betreuer_person_id)
	{
		$this->_ci->load->model('crm/Student_model', 'StudentModel');
		$this->_ci->load->model('education/Projektarbeit_model', 'ProjektarbeitModel');
		$this->_ci->load->model('person/Person_model', 'PersonModel');

		// get Projektarbeit data
		$this->_ci->ProjektarbeitModel->addSelect('student_uid, titel');
		$projektarbeitRes = $this->_ci->ProjektarbeitModel->load($projektarbeit_id);

		if (!hasData($projektarbeitRes))
			return error("no Projektarbeit found");

		$projektarbeit = getData($projektarbeitRes)[0];

		$student_uid = $projektarbeit->student_uid;
		$titel = $projektarbeit->titel;

		// get student mail
		$student_mail = $this->_ci->StudentModel->getEmailFH($student_uid);

		// get student data
		$this->_ci->PersonModel->addLimit('1');
		$this->_ci->PersonModel->addSelect('titelpre, vorname, nachname, titelpost');
		$this->_ci->PersonModel->addJoin('public.tbl_benutzer', 'person_id');
		$studentRes = $this->_ci->PersonModel->loadWhere(array('uid' => $student_uid));

		if (!hasData($studentRes))
			return error("no student found");

		$student = getData($studentRes)[0];

		// get Projektbetreuer data
		$this->_ci->PersonModel->addSelect('titelpre, vorname, nachname, titelpost');
		$projektbetreuerRes = $this->_ci->PersonModel->load($betreuer_person_id);

		if (!hasData($projektbetreuerRes))
			return error("no Projektbetreuer found");

		$projektbetreuer = getData($projektbetreuerRes)[0];

		// get Betreuer and Student names
		$betreuer_fullname = implode(
			' ',
			array_filter(
				array(
					$projektbetreuer->titelpre,
					$projektbetreuer->vorname,
					$projektbetreuer->nachname,
					$projektbetreuer->titelpost
				)
			)
		);

		$student_fullname = implode(
			' ',
			array_filter(
				array(
					$student->titelpre,
					$student->vorname,
					$student->nachname,
					$student->titelpost
				)
			)
		);

		$mailcontent_data_arr = array(
			'betreuer_voller_name' => $betreuer_fullname,
			'student_voller_name' => $student_fullname,
			'titel' => $titel,
			'link' => CIS_ROOT."cis/private/lehre/abgabe_student_frameset.php"
		);

		// send mail with retrieved data
		sendSanchoMail(
			'ParbeitsbeurteilungInfoAnStudent',
			$mailcontent_data_arr,
			$student_mail,
			'Projektarbeitsbeurteilung abgeschlossen',
			'sancho_header_min_bw.jpg',
			'sancho_footer_min_bw.jpg'
		);

		return success($student_mail);
	}

	/**
	 * Sends info mail to commitee when requested by Erstbetreuer/Senatsvorsitz.
	 * @param int $projektarbeit_id
	 * @return object success or error
	 */
	public function sendInfoMailToKommission($projektarbeit_id)
	{
		$kommissionsMitgliederRes = $this->_ci->ProjektbetreuerModel->getBetreuerOfProjektarbeit($projektarbeit_id, AbstractProjektarbeitsbeurteilung::BETREUERART_SENATSMITGLIED);

		if (!hasData($kommissionsMitgliederRes))
			return error('Committee members not found');

		$kommissionsMitglieder = getData($kommissionsMitgliederRes);

		$betreuerart =
			$kommissionsMitglieder[0]->projekttyp_kurzbz == 'Diplom'
			? AbstractProjektarbeitsbeurteilung::BETREUERART_ERSTBEGUTACHTER
			: AbstractProjektarbeitsbeurteilung::BETREUERART_SENATSVORSITZ;

		$erstbetreuerRes = $this->_ci->ProjektbetreuerModel->getBetreuerOfProjektarbeit($projektarbeit_id, $betreuerart);

		if (!hasData($erstbetreuerRes))
			return error('Erstbetreuer not found');

		$erstbetreuer = getData($erstbetreuerRes)[0];

		foreach ($kommissionsMitglieder as $kommissionsMitglied)
		{
			$palink = site_url() . "/extensions/FHC-Core-Projektarbeitsbeurteilung/ProjektarbeitsbeurteilungErstbegutachter";

			// check if Pruefer is external (has no Benutzer)
			if (isEmptyString($kommissionsMitglied->uid))
			{
				// not sending mail if no email
				if (isEmptyString($kommissionsMitglied->private_email))
					return error('No email address found for '.$kommissionsMitglied->voller_name);
				else
				{
					// get private mail for external
					$receiverMail = $kommissionsMitglied->private_email;

					// get Zugangstoken for external
					$this->_ci->ProjektbetreuerModel->addSelect('zugangstoken');
					$prueferRes = $this->_ci->ProjektbetreuerModel->loadWhere(
						array(
							'person_id' => $kommissionsMitglied->person_id,
							'projektarbeit_id' => $projektarbeit_id,
							'zugangstoken_gueltigbis >=' => date('Y-m-d')
						)
					);
					if (hasData($prueferRes))
						$zugangstoken = getData($prueferRes)[0]->zugangstoken;
				}
			}
			else
			{
				$receiverMail = $kommissionsMitglied->uid.'@'.DOMAIN;
				// correct link for Pruefer with account
				$palink .= "?projektarbeit_id=$projektarbeit_id&uid=".$erstbetreuer->student_uid;
			}

			$erstbetreuerMail = $erstbetreuer->uid.'@'.DOMAIN;

			$mailcontent_data_arr = array(
				'geehrt' => "geehrte".($kommissionsMitglied->geschlecht=="m"?"r":""),
				'anrede' => $kommissionsMitglied->anrede,
				'kommissionsmitglied_voller_name' => $kommissionsMitglied->voller_name,
				'erstbetreuer_anrede' => $erstbetreuer->anrede,
				'erstbetreuer_voller_name' => $erstbetreuer->voller_name,
				'erstbetreuer_email' => $erstbetreuerMail,
				'zugangstoken' => isset($zugangstoken) ? "<p>Zugangstoken: $zugangstoken</p>" : '',
				'zugangstoken_english' => isset($zugangstoken) ? "<p>Login token: $zugangstoken</p>" : '',
				'link' => $palink
			);

			sendSanchoMail(
				'ParbeitsbeurteilungInfoSenat',
				$mailcontent_data_arr,
				$receiverMail,
				'Beurteilung einer Bachelorarbeit zur kommissionellen Prüfung',
				'sancho_header_min_bw.jpg',
				'sancho_footer_min_bw.jpg'
			);
		}

		return success('Mails to committee members sent');
	}
}
