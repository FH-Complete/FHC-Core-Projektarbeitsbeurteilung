<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 */
class Projektarbeitsbeurteilung extends FHC_Controller
{
	const CATEGORY_MAX_POINTS = 10;
	const BETREUERART_ZWEITBEGUTACHTER = 'Zweitbegutachter';
	const EXTERNER_BEURTEILERAME = 'externerBeurteiler';

	private $_requiredFields = array(
			'betreuernote' => 'grade',
			'plagiatscheck_unauffaellig' => 'bool',
			'bewertung_thema' => 'points',
			'bewertung_loesungsansatz' => 'points',
			'bewertung_methode' => 'points',
			'bewertung_ereignissediskussion' => 'points',
			'bewertung_eigenstaendigkeit' => 'points',
			'bewertung_struktur' => 'points',
			'bewertung_stil' => 'points',
			'bewertung_form' => 'points',
			'bewertung_literatur' => 'points',
			'bewertung_zitierregeln' => 'points',
			'begruendung' => 'text'
	);

    /**
     * Constructor
     */
    public function __construct()
    {
		parent::__construct();

        // Load models
		$this->load->model('extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung_model', 'ProjektarbeitsbeurteilungModel');
		$this->load->model('education/Projektbetreuer_model', 'ProjektbetreuerModel');

        // Load language phrases
        $this->loadPhrases(
            array(
            	'ui',
                'global',
                'abschlusspruefung',
                'projektarbeitsbeurteilung',
				'lehre'
            )
        );
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Public methods
	public function index()
	{
		$this->showProjektarbeitsbeurteilung();
	}

	/**
	 * Show Projektarbeitsbeurteilung.
	 */
	public function showProjektarbeitsbeurteilung()
	{
		$authObj = $this->_authenticate();

		if (isset($authObj->person_id))
		{
			$betreuer_person_id = $authObj->person_id;
			// params retrieved by token or get
			$student_uid = isset($authObj->uid) ? $authObj->uid : $this->input->get('uid');
			$projektarbeit_id = isset($authObj->projektarbeit_id) ? $authObj->projektarbeit_id : $this->input->get('projektarbeit_id');

			if (!is_numeric($projektarbeit_id))
				show_error('invalid Projektarbeitsbeurteilung');

			if (isEmptyString($student_uid))
				show_error('invalid student uid');

			$zweitbetreuer_input_id = $this->input->get('zweitbetreuer_id');

			// get person_id of Zweitbetreuer
			$zweitbetreuerRes = $this->ProjektarbeitsbeurteilungModel->getZweitbegutachterFromErstbegutachter($projektarbeit_id, $betreuer_person_id, $student_uid);
			$zweitbetreuer_person_id = null;

			if (hasData($zweitbetreuerRes))
			{
				$zweitbetreuer_person_id = getData($zweitbetreuerRes)[0]->person_id;
				if (isset($zweitbetreuer_input_id))
				{
					if ($zweitbetreuer_input_id === $zweitbetreuer_person_id)
					{
						// user person_id of Zweitbetreuer to display zweitbetreuer form
						$betreuer_person_id = $zweitbetreuer_person_id;
					}
					else
						show_error('Invalid Zweitbetreuer-Projektarbeit.');
				}
			}

			$projektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung($projektarbeit_id, $betreuer_person_id, $student_uid);

			if (hasData($projektarbeitsbeurteilungResult))
			{
				$language = getUserLanguage();

				$projektarbeitsbeurteilung = getData($projektarbeitsbeurteilungResult);

				$betreuerart = $projektarbeitsbeurteilung->betreuerart;

				$viewname = 'projektarbeitsbeurteilung';
				if ($betreuerart === self::BETREUERART_ZWEITBEGUTACHTER)
					$viewname = 'projektarbeitsbeurteilung_zweitbegutachter';
				else
				{
					$bewertung_punkte = $this->_calculateBewertungPunkte($projektarbeitsbeurteilung->projektarbeit_bewertung);
					$projektarbeitsbeurteilung->bewertung_gesamtpunkte = $bewertung_punkte->gesamtpunkte;
					$projektarbeitsbeurteilung->bewertung_maxpunkte = $bewertung_punkte->maxpunkte;
				}

				$data = array(
					'projektarbeit_id' => $projektarbeit_id,
					'betreuer_person_id' => $betreuer_person_id,
					'student_uid' => $student_uid,
					'authtoken' => isset($authObj->authtoken) ? $authObj->authtoken : null,
					'projektarbeitsbeurteilung' => $projektarbeitsbeurteilung,
					'language' => $language,
					'zweitbetreuer_person_id' => $zweitbetreuer_person_id
				);

				$this->load->view("extensions/FHC-Core-Projektarbeitsbeurteilung/$viewname.php", $data);
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
				'authtoken' => 	isset($authObj->authtoken) ? $authObj->authtoken : null
			);

			$this->load->view("extensions/FHC-Core-Projektarbeitsbeurteilung/tokenlogin.php", $authtokenData);
		}
	}

	/**
	 * Save Pruefungsprotokoll (including possible Freigabe)
	 */
	public function saveProjektarbeitsbeurteilung()
	{
		$result = null;

		$abgeschicktamum = date('Y-m-d H:i:s');

		$authObj = $this->_authenticate();

		if (isset($authObj->person_id) && isset($authObj->username))
		{
			$betreuer_person_id = $authObj->person_id;

			$saveAndSend = $this->input->post('saveAndSend');
			if ($saveAndSend === 'true')
				$saveAndSend = true;
			elseif ($saveAndSend === 'false')
				$saveAndSend = false;

			$projektarbeit_id = isset($authObj->projektarbeit_id) ? $authObj->projektarbeit_id : $this->input->post('projektarbeit_id');
			$betreuerart = $this->input->post('betreuerart');
			$bewertung = $this->input->post('bewertung');

			if (!is_numeric($projektarbeit_id))
			{
				$this->outputJsonError('Invalid Projektarbeit Id');
			}
			elseif (!is_numeric($betreuer_person_id))
			{
				$this->outputJsonError('Invalid Betreuer Id');
			}
			elseif (isEmptyString($betreuerart))
			{
				$this->outputJsonError('Invalid Betreuerart');
			}
			elseif (!is_bool($saveAndSend))
			{
				$this->outputJsonError('Invalid saveAndSend');
			}
			else
			{
				$bewertung = $this->_prepareBeurteilungDataForSave($bewertung);

				if ($betreuerart == self::BETREUERART_ZWEITBEGUTACHTER)
				{
					$this->_requiredFields = array(
						'beurteilung_zweitbegutachter' => 'text'
					);
				}

				$checkRes = $this->_checkBewertung($bewertung, $saveAndSend);

				if (isError($checkRes))
				{
					$this->outputJsonError(getError($checkRes));
					return;
				}

				$betreuernote = isset($bewertung['betreuernote']) ? $bewertung['betreuernote'] : null;
				unset($bewertung['betreuernote']); // Grade is saved in Projektbeurteiler tbl, not Projektarbeitsbeurteilung
				$bewertung['beurteilungsdatum'] = $abgeschicktamum;

				$bewertungJson = json_encode($bewertung);

				if (!$bewertungJson)
				{
					$this->outputJsonError('Invalid JSON Format');
				}
				else
				{
					$projektbetreuerResult = $this->ProjektbetreuerModel->loadWhere(
						array(
							'projektarbeit_id' => $projektarbeit_id,
							'person_id' => $betreuer_person_id,
							'betreuerart_kurzbz' => $betreuerart
						)
					);

					if (hasData($projektbetreuerResult))
					{
						$projektarbeitsbeurteilungToSave = array(
							'projektarbeit_id' => $projektarbeit_id,
							'betreuer_person_id' => $betreuer_person_id,
							'betreuerart_kurzbz' => $betreuerart,
							'bewertung' => $bewertungJson
						);

						if ($saveAndSend === true)
						{
							$projektarbeitsbeurteilungToSave['abgeschicktvon'] = $authObj->username;
							$projektarbeitsbeurteilungToSave['abgeschicktamum'] = $abgeschicktamum;
						}

						$projektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->loadWhere(
							array(
								'projektarbeit_id' => $projektarbeit_id,
								'betreuer_person_id' => $betreuer_person_id,
								'betreuerart_kurzbz' => $betreuerart
							)
						);

						if (isError($projektarbeitsbeurteilungResult))
							$this->outputJsonError('Error when getting Beurteilung');
						// update if existing Beurteilung
						elseif (hasData($projektarbeitsbeurteilungResult))
						{
							$projektarbeitsbeurteilung_id = getData($projektarbeitsbeurteilungResult)[0]->projektarbeitsbeurteilung_id;

							$projektarbeitsbeurteilungToSave['updateamum'] = date('Y-m-d H:i:s', time());

							$result = $this->ProjektarbeitsbeurteilungModel->update($projektarbeitsbeurteilung_id, $projektarbeitsbeurteilungToSave);
						}
						else
						{
							// no existing Beurteilung -> insert if there is corresponding Projektbetreuer
							$projektarbeitsbeurteilungToSave['insertvon'] = $authObj->username;

							$result = $this->ProjektarbeitsbeurteilungModel->insert($projektarbeitsbeurteilungToSave);
						}

						if (isError($result))
							$this->outputJsonError(getError($result));
						else
						{
							// update note in Projektbetreuer tbl
							if (isset($betreuernote))
							{
								$noteUpdateResult = $this->ProjektbetreuerModel->update(
									array(
										'projektarbeit_id' => $projektarbeit_id,
										'person_id' => $betreuer_person_id,
										'betreuerart_kurzbz' => $betreuerart
									),
									array('note' => $betreuernote)
								);

								if (isError($noteUpdateResult))
								{
									$this->outputJsonError(getError($noteUpdateResult));
									return;
								}
							}

							// send info mail to Erstbegutachter after Zweitbegutachter has finished assessment
							if ($saveAndSend && $betreuerart === 'Zweitbegutachter')
								$this->_sendInfoMailToErstbegutachter($projektarbeit_id, $betreuer_person_id);

							$this->outputJsonSuccess(getData($result));
						}
					}
					else
						$this->outputJsonError('No Projektbetreuer found');
				}
			}
		}
		else
			$this->outputJsonError('invalid authentication');
	}

    // -----------------------------------------------------------------------------------------------------------------
    // Private methods

	private function _authenticate()
	{
		$authData = null;

		$token = $this->input->post('authtoken');
		$projektarbeit_id = $this->input->get('projektarbeit_id');

		if (!isset($projektarbeit_id)) // posted on save
			$projektarbeit_id =  $this->input->post('projektarbeit_id');

		if (!isEmptyString($token)) // if token passed, get projektarbeit from token
		{
			$tokenResult = $this->ProjektbetreuerModel->getBetreuerByToken($token);
			$authData = new stdClass();
			$authData->authtoken = $token;

			if (hasData($tokenResult))
			{
				$tokenData = getData($tokenResult)[0];

				$authData->username = self::EXTERNER_BEURTEILERAME;
				$authData->person_id = $tokenData->person_id;
				$authData->projektarbeit_id = $tokenData->projektarbeit_id;
				$authData->uid = $tokenData->student_uid;
			}
		}
		elseif (isset($projektarbeit_id) && is_numeric($projektarbeit_id)) // when projektarbeit given - normal login, id cannot be derived from token
		{
			$this->load->library('AuthLib');
			$authObj = $this->authlib->getAuthObj();

			// if already logged in (e.g. CIS)
			if (isset($authObj->person_id) && isset($authObj->username))
			{
				$authData = new stdClass();
				$authData->username = $authObj->username;
				$authData->person_id = $authObj->person_id;
			}
		}

		return $authData;
	}

	/**
	 * Prepares Beurteilungdata for save in database, replaces null strings, integer strings, bool strings.
	 * @param $data
	 * @return array
	 */
	private function _prepareBeurteilungDataForSave($data)
	{
		foreach ($data as $idx => $item)
		{
			if ($item === 'null')
				$data[$idx] = null;
			elseif (is_numeric($item))
				$data[$idx] = (int) $item;
			elseif ($item === 'true')
				$data[$idx] = true;
			elseif ($item === 'false')
				$data[$idx] = false;
		}

		return $data;
	}

	private function _checkBewertung($bewertung, $saveAndSend = false)
	{
		$betreuernote = isset ($bewertung['betreuernote']) ? $bewertung['betreuernote'] : null;

		foreach ($this->_requiredFields as $required_field => $fieldtype)
		{
			if (!isset($bewertung[$required_field]) || (is_string($bewertung[$required_field]) && trim($bewertung[$required_field]) === ''))
			{
				// if only save and not send, null values are allowed. Begruedung is only necessary when grade is 5.
				if ($saveAndSend == false || ($required_field == 'begruendung' && $betreuernote != 5))
					continue;

				return error(ucfirst($required_field) . ' ' . $this->p->t('ui', 'fehlt'));
			}

			$valid = true;

			switch($fieldtype)
			{
				case 'text':
					$valid = is_string($bewertung[$required_field]);
					break;
				case 'bool':
					$valid = is_bool($bewertung[$required_field]);
					break;
				case 'points':
					$valid = in_array($bewertung[$required_field], array('0', '5', '8', '10'));
					break;
				case 'grade':
					$valid = in_array($bewertung[$required_field], array('1', '2', '3', '4', '5'));
					break;
			}

			if (!$valid)
				return error("$required_field" . $this->p->t('ui', 'ungueltig'));
		}

		return success('Bewertung check passed');
	}

	private function _calculateBewertungPunkte($bewertung)
	{
		$punkte = new stdClass();
		$punkte->gesamtpunkte = 0;
		$punkte->maxpunkte = 0;

		foreach ($this->_requiredFields as $required_field => $fieldtype)
		{
			if ($fieldtype == 'points')
			{
				if (isset($bewertung->{$required_field}) && is_numeric($bewertung->{$required_field}))
				{
					$punkte->gesamtpunkte += (int)$bewertung->{$required_field};
				}
				$punkte->maxpunkte += self::CATEGORY_MAX_POINTS;
			}
		}

		return $punkte;
	}

	/**
	 * Sends sancho infomail to Erstbegutachter after Zweitbegutachter finished assessment.
	 * @param $projektarbeit_id int
	 * @param $zweitbetreuer_person_id int
	 * @return object success or error
	 */
	private function _sendInfoMailToErstbegutachter($projektarbeit_id, $zweitbetreuer_person_id)
	{
		$this->load->model('person/Benutzer_model', 'BenutzerModel');

		$erstbetreuerRes = $this->ProjektarbeitsbeurteilungModel->getErstbegutachterFromZweitbegutachter($projektarbeit_id, $zweitbetreuer_person_id);

		if (!hasData($erstbetreuerRes))
			return error("no Erstbetreuer found");

		$erstbetreuerData = getData($erstbetreuerRes)[0];
		$erstbegutachter_person_id = $erstbetreuerData->person_id;
		$receiver_uid = $erstbetreuerData->uid;
		$student_uid = $erstbetreuerData->student_uid;

		$receiverMail = $receiver_uid.'@'.DOMAIN;

		$this->load->helper('hlp_sancho_helper');

		$mailcontent_data_arr = array(
			'link' => site_url() . "/extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung?projektarbeit_id=$projektarbeit_id&uid=$student_uid",
			'anrede' => $erstbetreuerData->anrede,
			'betreuer_voller_name' => $erstbetreuerData->fullname,
			'student_voller_name' => $erstbetreuerData->student_fullname,
			'geehrt' => "geehrte".($erstbetreuerData->anrede=="Herr"?"r":"")
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
}
