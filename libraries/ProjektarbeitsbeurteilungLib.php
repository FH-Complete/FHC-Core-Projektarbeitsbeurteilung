<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Library retrieving data for Projektarbeitsbeurteilung
 */
class ProjektarbeitsbeurteilungLib
{
	const BETREUERART_BACHELOR_BEGUTACHTER = 'Begutachter';
	const BETREUERART_ERSTBEGUTACHTER = 'Erstbegutachter';
	const BETREUERART_ZWEITBEGUTACHTER = 'Zweitbegutachter';
	const BETREUERART_SENATSVORSITZ = 'Senatsvorsitz';
	const BETREUERART_SENATSMITGLIED = 'Senatsmitglied';

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
	public function getSenatspruefer($projektarbeit_id, $vorsitz_person_id)
	{
		$kommissionPruefer = array();
		$kommissionPrueferRes = $this->_ci->ProjektbetreuerModel->getBetreuerOfProjektarbeit(
			$projektarbeit_id,
			self::BETREUERART_SENATSMITGLIED
		);

		if (isError($kommissionPrueferRes))
			show_error(getError($kommissionPrueferRes));

		if (hasData($kommissionPrueferRes))
		{
			$kommissionPruefer = getData($kommissionPrueferRes);

			// set the mail for Kommission Prüfer
			foreach ($kommissionPruefer as $kp)
			{
				$deliveryEmail = '';
				if (isEmptyString($kp->uid))
				{
					// if no user account, use private mail
					if (!isEmptyString($kp->private_email))
						$deliveryEmail = $kp->private_email;
				}
				else // if has user account, use university mail
					$deliveryEmail = $kommissionVorsitz->uid.'@'.DOMAIN;
			}

			// Bewertung of Vorsitz has to be displayed for Senatsmitglied
			if ($projektarbeitsbeurteilung->betreuerart === self::BETREUERART_SENATSMITGLIED)
			{
				$vorsitzProjektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung(
					$projektarbeit_id,
					$vorsitz_person_id,
					$student_uid
				);

				// copy Bewertung and Note from Senatsvorsitz to Senatsmitglied
				if (hasData($vorsitzProjektarbeitsbeurteilungResult))
				{
					$vorsitzProjektarbeitsbeurteilung = getData($vorsitzProjektarbeitsbeurteilungResult);
					$projektarbeitsbeurteilung->projektarbeit_bewertung = $vorsitzProjektarbeitsbeurteilung->projektarbeit_bewertung;
					$projektarbeitsbeurteilung->betreuernote = $vorsitzProjektarbeitsbeurteilung->betreuernote;
				}
			}
		}
	}
}
