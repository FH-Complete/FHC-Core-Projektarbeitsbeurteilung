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
			'link' => site_url() . "/extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung?projektarbeit_id=$projektarbeit_id&uid=$student_uid",
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
	 * @param $zweitbetreuer_person_id int
	 * @return object success or error
	 */
	public function sendInfoMailToStudiengang($projektarbeit_id, $betreuer_person_id)
	{
		$projektarbeitsbeurteilungres = $this->_ci->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung($projektarbeit_id, $betreuer_person_id);

		if (!hasData($projektarbeitsbeurteilungres))
			return error('Projektarbeitsbeurteilung not found');

		$projektarbeitsbeurteilung = getData($projektarbeitsbeurteilungres);

		$studiengang_kz = $projektarbeitsbeurteilung->studiengang_kz;

		$this->_ci->StudiengangModel->addSelect('email');
		$studiengangres = $this->_ci->StudiengangModel->load($studiengang_kz);

		if (!hasData($studiengangres))
			return error('Studiengang not found');

		$studiengang_email = getData($studiengangres)[0]->email;
		$betreuer_fullname = implode(' ', array_filter(array($projektarbeitsbeurteilung->titelpre_betreuer, $projektarbeitsbeurteilung->vorname_betreuer,
			$projektarbeitsbeurteilung->nachname_betreuer, $projektarbeitsbeurteilung->titelpost_betreuer)));
		$student_fullname = implode(' ', array_filter(array($projektarbeitsbeurteilung->titelpre_student, $projektarbeitsbeurteilung->vorname_student,
			$projektarbeitsbeurteilung->nachname_student, $projektarbeitsbeurteilung->titelpost_student)));

		$mailcontent_data_arr = array(
			'betreuer_voller_name' => $betreuer_fullname,
			'student_voller_name' => $student_fullname,
			'betreuer_art' => $projektarbeitsbeurteilung->betreuerart
		);

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
	 * Sends info mail to commitee when requested by Erstbetreuer.
	 * @param int $projektarbeit_id
	 * @return object success or error
	 */
	public function sendInfoMailToKommission($projektarbeit_id)
	{
		$kommissionsMitgliederRes = $this->_ci->ProjektbetreuerModel->getBetreuerOfProjektarbeit($projektarbeit_id, Projektarbeitsbeurteilung::BETREUERART_KOMMISSION);

		if (!hasData($kommissionsMitgliederRes))
			return error('Committee members not found');

		$kommissionsMitglieder = getData($kommissionsMitgliederRes);

		$betreuerart = $kommissionsMitglieder[0]->projekttyp_kurzbz == 'Diplom' ? Projektarbeitsbeurteilung::BETREUERART_ERSTBEGUTACHTER : Projektarbeitsbeurteilung::BETREUERART_BACHELOR_BEGUTACHTER;

		$erstbetreuerRes = $this->_ci->ProjektbetreuerModel->getBetreuerOfProjektarbeit($projektarbeit_id, $betreuerart);

		if (!hasData($erstbetreuerRes))
			return error('Erstbetreuer not found');

		$erstbetreuer = getData($erstbetreuerRes)[0];

		foreach ($kommissionsMitglieder as $kommissionsMitglied)
		{
			$receiverMail = $kommissionsMitglied->uid.'@'.DOMAIN;
			$erstbetreuerMail = $erstbetreuer->uid.'@'.DOMAIN;

			$mailcontent_data_arr = array(
				'geehrt' => "geehrte".($kommissionsMitglied->geschlecht=="m"?"r":""),
				'anrede' => $kommissionsMitglied->anrede,
				'kommissionsmitglied_voller_name' => $kommissionsMitglied->voller_name,
				'erstbetreuer_bezeichnung' => 'Erstbetreuer'.($erstbetreuer->geschlecht=="w"?"in":""),
				'erstbetreuer_anrede' => $erstbetreuer->anrede,
				'erstbetreuer_voller_name' => $erstbetreuer->voller_name,
				'erstbetreuer_email' => $erstbetreuerMail,
				'link' => site_url() . "/extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung?projektarbeit_id=$projektarbeit_id&uid=".$erstbetreuer->student_uid
			);

			sendSanchoMail(
				'ParbeitsbeurteilungKommissionInf',
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
