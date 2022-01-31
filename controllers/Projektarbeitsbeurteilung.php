<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 */
class Projektarbeitsbeurteilung extends FHC_Controller
{
	const CATEGORY_MAX_POINTS = 10;
	const BETREUERART_BACHELOR_BEGUTACHTER = 'Begutachter';
	const BETREUERART_ERSTBEGUTACHTER = 'Erstbegutachter';
	const BETREUERART_ZWEITBEGUTACHTER = 'Zweitbegutachter';
	const BETREUERART_KOMMISSION = 'Kommission';
	const EXTERNER_BEURTEILER_NAME = 'externerBeurteiler';

	// fields required to be filled out by Erstbetreuer
	private $_requiredFieldsErstbegutachter = array(
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
			'begruendung' => 'text',
			'betreuernote' => 'grade'
	);

	// fields required to be filled out by Zweitbetreuer
	private $_requiredFieldsZweitbegutachter = array(
		'beurteilung_zweitbegutachter' => 'text'
	);

	// fields required to be filled out by Betreuer, filled depending on Betreuer type
	private $_requiredFields = array();

    /**
     * Constructor
     */
    public function __construct()
    {
		parent::__construct();

        // Load models
		$this->load->model('extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung_model', 'ProjektarbeitsbeurteilungModel');
		$this->load->model('education/Projektarbeit_model', 'ProjektarbeitModel');
		$this->load->model('education/Projektbetreuer_model', 'ProjektbetreuerModel');

		// Load libraries
		$this->load->library('extensions/FHC-Core-Projektarbeitsbeurteilung/ProjektarbeitsbeurteilungMailLib');

		// Load helpers
		$this->load->helper('extensions/FHC-Core-Projektarbeitsbeurteilung/projektarbeitsbeurteilung_helper');

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

		$this->_requiredFields = $this->_requiredFieldsErstbegutachter;
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

			$zweitbetreuer_input_id = $this->input->get('zweitbetreuer_id');

			// get person_id of Zweitbetreuer
			$zweitbetreuerRes = $this->ProjektarbeitsbeurteilungModel->getZweitbegutachterFromErstbegutachter($projektarbeit_id, $betreuer_person_id, $student_uid);
			$zweitbetreuer_person_id = null;
			$zweitbetreuer_abgeschicktamum = null;

			if (hasData($zweitbetreuerRes))
			{
				$zweitbetreuer = getData($zweitbetreuerRes)[0];

				$zweitbetreuer_person_id = $zweitbetreuer->person_id;

				// information wether Zweitbetreuer has finished assessment -> different display
				$zweitbetreuer_abgeschicktamum = $zweitbetreuer->abgeschicktamum;

				if (isset($zweitbetreuer_input_id))
				{
					// if zweitbetreuer id passed is same as zweitbetreuer id from Projektarbeit
					if ($zweitbetreuer_input_id === $zweitbetreuer_person_id)
					{
						// save person_id of Zweitbetreuer to display Beurteilung from Zweitbegutachter
						$betreuer_person_id = $zweitbetreuer_person_id;
					}
					else
						show_error('Invalid Zweitbetreuer-Projektarbeit.');
				}
			}

			// get Projektarbeitsbeurteilung data for given Projektarbeit and Betreuer
			$projektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung($projektarbeit_id, $betreuer_person_id, $student_uid);

			if (hasData($projektarbeitsbeurteilungResult))
			{
				$language = getUserLanguage();

				$projektarbeitsbeurteilung = getData($projektarbeitsbeurteilungResult);

				// check if Projektarbeit is kommissionell - for displaying additional info/functionality
				$kommissionBetreuer = array();
				$isKommission = false;
				$kommissionBetreuerRes = $this->ProjektbetreuerModel->getBetreuerOfProjektarbeit($projektarbeit_id, self::BETREUERART_KOMMISSION);

				if (isError($kommissionBetreuerRes))
					show_error(getError($kommissionBetreuerRes));

				if (hasData($kommissionBetreuerRes))
				{
					$kommissionBetreuer = getData($kommissionBetreuerRes);

					// set the university mail for kommission Betreuer
					foreach ($kommissionBetreuer as $kb)
					{
						$kb->univEmail = isset($kb->uid) ? $kb->uid.'@'.DOMAIN : '';
					}

					// set flag that it is kommissionell
					$isKommission = true;
				}

				// read only if Projektarbeit is already sent, or logged in Betreuer is member of Kommission
				$readOnlyAccess = isset($projektarbeitsbeurteilung->abgeschicktamum) || $projektarbeitsbeurteilung->betreuerart === self::BETREUERART_KOMMISSION;

				$betreuerart = $projektarbeitsbeurteilung->betreuerart;

				// show correct view type, depending on betreuerart (Erstbegutachter or Zweitbegutachter)
				$viewname = 'projektarbeitsbeurteilung';
				if ($betreuerart === self::BETREUERART_ZWEITBEGUTACHTER)
					$viewname = 'projektarbeitsbeurteilung_zweitbegutachter';
				else
				{
					// calculate the points reached and max points for displaying
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
					'zweitbetreuer_person_id' => $zweitbetreuer_person_id,
					'zweitbetreuer_abgeschicktamum' => $zweitbetreuer_abgeschicktamum,
					'kommission_betreuer' => $kommissionBetreuer,
					'isKommission' => $isKommission,
					'readOnlyAccess' => $readOnlyAccess
				);

				// load the correct view
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
		$saveProjektarbeitsbeurteilungResult = null;

		// save current date as abgeschicktamum
		$abgeschicktamum = date('Y-m-d H:i:s');

		$authObj = $this->_authenticate();

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
			$betreuerart = $this->input->post('betreuerart');
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
				// prepare Bewertung to save in database
				$bewertung = $this->_prepareBeurteilungDataForSave($bewertung);

				// only check text field if Zweitbegutachter
				if ($betreuerart == self::BETREUERART_ZWEITBEGUTACHTER)
				{
					$this->_requiredFields = $this->_requiredFieldsZweitbegutachter;
				}

				// check entered Bewertung for validity
				$checkRes = $this->_checkBewertung($bewertung, $saveAndSend);

				if (isError($checkRes))
				{
					$this->outputJsonError(getError($checkRes));
					return;
				}

				// get betreuernote to save
				$betreuernote = isset($bewertung['betreuernote']) ? $bewertung['betreuernote'] : null;
				unset($bewertung['betreuernote']); // Grade is saved in Projektbeurteiler tbl, not Projektarbeitsbeurteilung
				$bewertung['beurteilungsdatum'] = $abgeschicktamum;

				// encode bewertung into json format
				$bewertungJson = json_encode($bewertung);

				if (!$bewertungJson)
				{
					$this->outputJsonError('Invalid JSON Format');
					exit;
				}
				else
				{
					// get Projektbetreuer
					$projektbetreuerResult = $this->ProjektbetreuerModel->loadWhere(
						array(
							'projektarbeit_id' => $projektarbeit_id,
							'person_id' => $betreuer_person_id,
							'betreuerart_kurzbz' => $betreuerart
						)
					);

					if (hasData($projektbetreuerResult))
					{
						// data to save
						$projektarbeitsbeurteilungToSave = array(
							'projektarbeit_id' => $projektarbeit_id,
							'betreuer_person_id' => $betreuer_person_id,
							'betreuerart_kurzbz' => $betreuerart,
							'bewertung' => $bewertungJson
						);

						// additional info if Beurteilung was sent (finalized)
						if ($saveAndSend === true)
						{
							$projektarbeitsbeurteilungToSave['abgeschicktvon'] = $authObj->username;
							$projektarbeitsbeurteilungToSave['abgeschicktamum'] = $abgeschicktamum;
						}

						// check if there is an existing Projektarbeitsbeurteilung
						$projektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->loadWhere(
							array(
								'projektarbeit_id' => $projektarbeit_id,
								'betreuer_person_id' => $betreuer_person_id,
								'betreuerart_kurzbz' => $betreuerart
							)
						);

						if (isError($projektarbeitsbeurteilungResult))
						{
							$this->outputJsonError('Error when getting Beurteilung');
							exit;
						}
						// update if existing Beurteilung
						elseif (hasData($projektarbeitsbeurteilungResult))
						{
							$projektarbeitsbeurteilung_id = getData($projektarbeitsbeurteilungResult)[0]->projektarbeitsbeurteilung_id;

							$projektarbeitsbeurteilungToSave['updateamum'] = date('Y-m-d H:i:s', time());

							$saveProjektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->update($projektarbeitsbeurteilung_id, $projektarbeitsbeurteilungToSave);

							if (isSuccess($saveProjektarbeitsbeurteilungResult) && $saveAndSend === true)
							{
								// send info mail to Studiengang on update
								$mailResult = $this->projektarbeitsbeurteilungmaillib->sendInfoMailToStudiengangUpdated($projektarbeit_id, $betreuer_person_id);

								if (isError($mailResult))
								{
									$this->outputJsonError(getError($mailResult));
									exit;
								}
							}
						}
						else
						{
							// no existing Beurteilung -> insert new
							$projektarbeitsbeurteilungToSave['insertvon'] = $authObj->username;

							$saveProjektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->insert($projektarbeitsbeurteilungToSave);
						}

						if (isError($saveProjektarbeitsbeurteilungResult))
						{
							$this->outputJsonError(getError($saveProjektarbeitsbeurteilungResult));
							exit;
						}
						else
						{
							// if the Erstbetreuer has sent the Bewertung
							if ($betreuerart != self::BETREUERART_ZWEITBEGUTACHTER)
							{
								// update note in Projektbetreuer tbl
								$noteUpdateResult = $this->ProjektbetreuerModel->update(
									array(
										'projektarbeit_id' => $projektarbeit_id,
										'person_id' => $betreuer_person_id,
										'betreuerart_kurzbz' => $betreuerart
									),
									array(
										'note' => $betreuernote,
										'updateamum' => 'NOW()'
									)
								);

								if (isError($noteUpdateResult))
								{
									$this->outputJsonError(getError($noteUpdateResult));
									exit;
								}
							}

							// additional actions if not only saved, but also sent (finalized) Beurteilung
							if ($saveAndSend === true)
							{
								// send info mail to Erstbegutachter after Zweitbegutachter has finished assessment
								if ($betreuerart === self::BETREUERART_ZWEITBEGUTACHTER)
								{
									$this->projektarbeitsbeurteilungmaillib->sendInfoMailToErstbegutachter($projektarbeit_id, $betreuer_person_id);

									$mailResult = $this->projektarbeitsbeurteilungmaillib->sendInfoMailToStudiengang($projektarbeit_id, $betreuer_person_id);

									if (isError($mailResult))
									{
										$this->outputJsonError(getError($mailResult));
										exit;
									}
								}
								else // if primary Begutachter, set Note and send Studiengangmail
								{
									if (isset($betreuernote))
									{
										// update note in Projektarbeit tbl (final Note)
										$finalNoteUpdateResult = $this->ProjektarbeitModel->update(
											array('projektarbeit_id' => $projektarbeit_id),
											array('note' => $betreuernote, 'updateamum' => 'NOW()')
										);

										if (isError($finalNoteUpdateResult))
										{
											$this->outputJsonError(getError($finalNoteUpdateResult));
											return;
										}
									}
									else
									{
										$this->outputJsonError('Final Note not set.');
										exit;
									}

									// send info mail to Studiengang after Begutachter has finished assessment
									$mailResult = $this->projektarbeitsbeurteilungmaillib->sendInfoMailToStudiengang($projektarbeit_id, $betreuer_person_id);

									if (isError($mailResult))
									{
										$this->outputJsonError(getError($finalNoteUpdateResult));
										exit;
									}
								}
							}

							$this->outputJsonSuccess(getData($saveProjektarbeitsbeurteilungResult));
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

	public function saveTitel()
	{
		$authObj = $this->_authenticate();

		// if successfully authenticated
		if (isset($authObj->person_id) && isset($authObj->username))
		{
			$projektarbeit_id = $this->input->post('projektarbeit_id');
			$titel = $this->input->post('titel');

			// check input params
			if (!is_numeric($projektarbeit_id))
			{
				$this->outputJsonError('Invalid Projektarbeit Id');
			}
			elseif (isEmptystring($titel))
			{
				$this->outputJsonError('Invalid titel');
			}
			else
			{
				// save titel of Projektarbeitsbeurteilung
				$titelSaveRes = $this->ProjektarbeitModel->update(
					array('projektarbeit_id' => $projektarbeit_id),
					array('titel' => $titel)
				);

				$this->outputJson($titelSaveRes);
			}
		}
		else
			$this->outputJsonError('invalid authentication');
	}

	/**
	 * Send info mail to commitee members when Erstbetreuer requests their consultation.
	 */
	public function sendInfoMailToKommission()
	{
		$authObj = $this->_authenticate();

		if (!isset($authObj->person_id))
			$this->outputJsonError('invalid authentication');

		$projektarbeit_id = $this->input->post('projektarbeit_id');

		$mailRes = $this->projektarbeitsbeurteilungmaillib->sendInfoMailToKommission($projektarbeit_id);

		$this->outputJson($mailRes);
	}

    // -----------------------------------------------------------------------------------------------------------------
    // Private methods

	/**
	 * Performs authentification, either normally or via authtoken.
	 * @return object|null object with authentification data or null on authentification failure
	 */
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

				$authData->username = self::EXTERNER_BEURTEILER_NAME;
				$authData->person_id = $tokenData->person_id;
				$authData->projektarbeit_id = $tokenData->projektarbeit_id;
				$authData->uid = $tokenData->student_uid;
			}
		}
		// when projektarbeit given - normal login
		elseif (isset($projektarbeit_id) && is_numeric($projektarbeit_id))
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
				$data[$idx] = (float) $item;
			elseif ($item === 'true')
				$data[$idx] = true;
			elseif ($item === 'false')
				$data[$idx] = false;
		}

		return $data;
	}

	/**
	 * Checks Bewertungdata before saving.
	 * @param $bewertung
	 * @param bool $saveAndSend wether Bewertung is only saved or saved and finally send
	 * @return object success if valid, error otherwise
	 */
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
					$valid = is_numeric($bewertung[$required_field]);
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

	/**
	 * Calculates Bewertungpunkte before passing to view.
	 * @param object $bewertung contains bewertungdata including points
	 * @return object containing gesamtpunkte (reached) and maxpunkte (max. reachable)
	 */
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
					$punkte->gesamtpunkte += (float)$bewertung->{$required_field};
				}
				$punkte->maxpunkte += self::CATEGORY_MAX_POINTS;
			}
		}

		return $punkte;
	}
}
