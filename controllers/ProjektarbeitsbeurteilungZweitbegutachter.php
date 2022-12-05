<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once('AbstractProjektarbeitsbeurteilung.php');

/**
 */
class ProjektarbeitsbeurteilungZweitbegutachter extends AbstractProjektarbeitsbeurteilung
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Load helpers
		$this->load->helper('extensions/FHC-Core-Projektarbeitsbeurteilung/projektarbeitsbeurteilung_helper');

		// set fields required for assessment
		$this->requiredFields = array(
			'beurteilung_zweitbegutachter' => array('type' => 'text', 'phrase' => 'kurzeSchriftlicheBeurteilung')
		);
	}

	// -----------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Show Projektarbeitsbeurteilung.
	 */
	public function showProjektarbeitsbeurteilung()
	{
		$authObj = $this->authenticate();

		// if authentication valid
		if (isset($authObj->person_id))
		{
			$betreuer_person_id = $authObj->person_id;
			// params retrieved by token or as get param
			$student_uid = isset($authObj->uid) ? $authObj->uid : $this->input->get('uid');
			$projektarbeit_id = isset($authObj->projektarbeit_id) ? $authObj->projektarbeit_id : $this->input->get('projektarbeit_id');

			if (!is_numeric($projektarbeit_id))
				show_error('invalid Projektarbeitsbeurteilung');

			if (isEmptyString($student_uid))
				show_error('invalid student uid');

			// get type of logged in Betreuer
			$betreuerartRes = $this->ProjektbetreuerModel->getBetreuerart($projektarbeit_id, $betreuer_person_id);

			if (hasData($betreuerartRes))
			{
				$betreuerart = getData($betreuerartRes)[0]->betreuerart_kurzbz;

				// if Erstbegutachter is logged in
				$isErstbegutachter = $betreuerart == self::BETREUERART_ERSTBEGUTACHTER;
				if ($isErstbegutachter)
				{
					// get Zweitbegutachter from Erstbegutachter
					$zweitbetreuerRes = $this->ProjektarbeitsbeurteilungModel->getZweitbegutachterFromErstbegutachter(
						$projektarbeit_id,
						$betreuer_person_id,
						$student_uid
					);

					if (hasData($zweitbetreuerRes))
					{
						$zweitbetreuer = getData($zweitbetreuerRes)[0];
						// set projektbetreuer_id to zweitbegutachter id to display correct zweitbegutachter form
						$betreuer_person_id = $zweitbetreuer->person_id;
					}
				}
			}
			else
			{
				show_error('Projektbetreuer not found');
			}

			// get Projektarbeitsbeurteilung data for given Projektarbeit and Betreuer
			$projektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung(
				$projektarbeit_id,
				$betreuer_person_id,
				$student_uid,
				self::BETREUERART_ZWEITBEGUTACHTER
			);

			if (hasData($projektarbeitsbeurteilungResult))
			{
				$language = getUserLanguage();

				$projektarbeitsbeurteilung = getData($projektarbeitsbeurteilungResult);

				// read only access if Projektarbeit is already sent or viewed by Erstbegutachter
				$readOnlyAccess = isset($projektarbeitsbeurteilung->abgeschicktamum) || $isErstbegutachter;

				$data = array(
					'projektarbeit_id' => $projektarbeit_id,
					'betreuer_person_id' => $betreuer_person_id, // Betreuer viewing/grading the Projektarbeit
					'student_uid' => $student_uid,
					'authtoken' => isset($authObj->authtoken) ? $authObj->authtoken : null,
					'projektarbeitsbeurteilung' => $projektarbeitsbeurteilung,
					'language' => $language,
					'readOnlyAccess' => $readOnlyAccess
				);

				// load the view
				$this->load->view("extensions/FHC-Core-Projektarbeitsbeurteilung/projektarbeitsbeurteilung_zweitbegutachter.php", $data);
			}
			else
			{
				show_error('Projektarbeit not found');
			}
		}
		else
		{
			// not logged in - load token login form
			$authtokenData = array(
				'authtoken' => 	isset($authObj->authtoken) ? $authObj->authtoken : null,
				'controllerName' => 'ProjektarbeitsbeurteilungZweitbegutachter'
			);

			$this->load->view("extensions/FHC-Core-Projektarbeitsbeurteilung/tokenlogin.php", $authtokenData);
		}
	}

	/**
	 * Save Pruefungsprotokoll (including possible Freigabe).
	 */
	public function saveProjektarbeitsbeurteilung()
	{
		$saveProjektarbeitsbeurteilungResult = null;

		// save current date as abgeschicktamum
		$abgeschicktamum = date('Y-m-d H:i:s');

		$authObj = $this->authenticate();

		// if successfully authenticated
		if (isset($authObj->person_id) && isset($authObj->username))
		{
			$betreuer_person_id = $authObj->person_id;

			// determine if assessment was saved and sent
			$saveAndSend = $this->input->post('saveAndSend');
			if ($saveAndSend === 'true')
				$saveAndSend = true;
			elseif ($saveAndSend === 'false')
				$saveAndSend = false;

			// get other input params
			$projektarbeit_id = isset($authObj->projektarbeit_id) ? $authObj->projektarbeit_id : $this->input->post('projektarbeit_id');
			//$betreuerart = $this->input->post('betreuerart');
			$bewertung = $this->input->post('bewertung');

			// check input params
			if (!is_numeric($projektarbeit_id))
			{
				$this->outputJsonError('Invalid Projektarbeit Id');
			}
			elseif (!is_numeric($betreuer_person_id))
			{
				$this->outputJsonError('Invalid Betreuer Id');
			}
			elseif (!is_bool($saveAndSend))
			{
				$this->outputJsonError('Invalid saveAndSend');
			}
			else
			{
				// prepare Bewertung to save in database
				$bewertung = $this->prepareBeurteilungDataForSave($bewertung);

				// check entered Bewertung for validity
				$checkRes = $this->checkBewertung($bewertung, $saveAndSend);

				if (isError($checkRes))
				{
					$this->outputJsonError(getError($checkRes));
					return;
				}

				// set send date
				$bewertung['beurteilungsdatum'] = $abgeschicktamum;

				// encode bewertung into json format
				$bewertungJson = json_encode($bewertung);

				if (!$bewertungJson)
				{
					$this->outputJsonError('Invalid JSON Format');
					return;
				}

				// get Projektbetreuer
				$projektbetreuerResult = $this->ProjektbetreuerModel->loadWhere(
					array(
						'projektarbeit_id' => $projektarbeit_id,
						'person_id' => $betreuer_person_id,
						'betreuerart_kurzbz' => self::BETREUERART_ZWEITBEGUTACHTER
					)
				);

				if (hasData($projektbetreuerResult))
				{
					$saveProjektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->saveProjektarbeitsbeurteilung(
						$projektarbeit_id,
						$betreuer_person_id,
						self::BETREUERART_ZWEITBEGUTACHTER,
						$bewertungJson,
						$authObj->username,
						$saveAndSend ? $abgeschicktamum : null
					);

					if (isError($saveProjektarbeitsbeurteilungResult))
					{
						$this->outputJsonError(getError($saveProjektarbeitsbeurteilungResult));
						return;
					}

					// additional actions if not only saved, but also sent (finalized) Beurteilung
					if ($saveAndSend === true)
					{
						// send info mail to Erstbegutachter after Zweitbegutachter has finished assessment
						$mailResult = $this->projektarbeitsbeurteilungmaillib->sendInfoMailToErstbegutachter($projektarbeit_id, $betreuer_person_id);

						if (isError($mailResult))
						{
							$this->outputJsonError('Error when sending Mail to Erstbegutachter: '.getError($mailResult));
						}

						$mailResult = $this->projektarbeitsbeurteilungmaillib->sendInfoMailToStudiengang($projektarbeit_id, $betreuer_person_id);

						if (isError($mailResult))
						{
							$this->outputJsonError('Error when sending Mail to Studiengang: '.getError($mailResult));
						}
					}

					$this->outputJsonSuccess(getData($saveProjektarbeitsbeurteilungResult));

				}
				else
					$this->outputJsonError('No Projektbetreuer found');
			}
		}
		else
			$this->outputJsonError('invalid authentication');
	}
}
