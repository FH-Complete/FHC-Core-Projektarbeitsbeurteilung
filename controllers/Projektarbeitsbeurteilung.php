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
	const BETREUERART_SENATSVORSITZ = 'Senatsvorsitz';
	const BETREUERART_SENATSPRUEFER = 'Senatspruefer';
	const EXTERNER_BEURTEILER_NAME = 'externerBeurteiler';

	// fields required to be filled out by Erstbetreuer
	private $_requiredFieldsErstbegutachter = array(
		'plagiatscheck_unauffaellig' => array('type' => 'bool', 'phrase' => 'plagiatscheck'),
		'bewertung_thema' => array('type' => 'points', 'phrase' => 'thema'),
		'bewertung_loesungsansatz' => array('type' => 'points', 'phrase' => 'loesungsansatz'),
		'bewertung_methode' => array('type' => 'points', 'phrase' => 'methode'),
		'bewertung_ereignissediskussion' => array('type' => 'points', 'phrase' => 'ereignisseDiskussion'),
		'bewertung_eigenstaendigkeit' => array('type' => 'points', 'phrase' => 'eigenstaendigkeit'),
		'bewertung_struktur' => array('type' => 'points', 'phrase' => 'struktur'),
		'bewertung_stil' => array('type' => 'points', 'phrase' => 'stil'),
		'bewertung_form' => array('type' => 'points', 'phrase' => 'form'),
		'bewertung_literatur' => array('type' => 'points', 'phrase' => 'literatur'),
		'bewertung_zitierregeln' => array('type' => 'points', 'phrase' => 'zitierregeln'),
		'begruendung' => array('type' => 'text', 'phrase' => 'begruendungText'),
		'betreuernote' => array('type' => 'grade', 'phrase' => 'betreuernote')
	);

	// fields required to be filled out by Zweitbetreuer
	private $_requiredFieldsZweitbegutachter = array(
		'beurteilung_zweitbegutachter' => array('type' => 'text', 'phrase' => 'kurzeSchriftlicheBeurteilung')
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
		$this->load->model('education/Paabgabe_model', 'PaabgabeModel');

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
			$zweitbetreuerRes = $this->ProjektarbeitsbeurteilungModel->getZweitbegutachterFromErstbegutachter(
				$projektarbeit_id,
				$betreuer_person_id,
				$student_uid
			);
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
			$projektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung(
				$projektarbeit_id,
				$betreuer_person_id,
				$student_uid
			);

			if (hasData($projektarbeitsbeurteilungResult))
			{
				$language = getUserLanguage();

				$projektarbeitsbeurteilung = getData($projektarbeitsbeurteilungResult);

				// check if Projektarbeit is kommissionell - for displaying additional info/functionality
				$isKommission = false;

				$kommissionVorsitz = null;
				$kommissionPruefer = array();

				// get Kommission Vorsitz
				$kommissionVorsitzRes = $this->ProjektbetreuerModel->getBetreuerOfProjektarbeit(
					$projektarbeit_id,
					self::BETREUERART_SENATSVORSITZ
				);

				// if kommissionell
				if (hasData($kommissionVorsitzRes))
				{
					// set flag that it is kommissionell
					$isKommission = true;

					// Kommissionvorsitz will grade the work
					$kommissionVorsitz = getData($kommissionVorsitzRes)[0];
					$kommissionVorsitz->univEmail = isset($kommissionVorsitz->uid) ? $kommissionVorsitz->uid.'@'.DOMAIN : '';
					$vorsitz_person_id = $kommissionVorsitz->person_id;

					// get other Kommission members
					$kommissionPrueferRes = $this->ProjektbetreuerModel->getBetreuerOfProjektarbeit(
						$projektarbeit_id,
						self::BETREUERART_SENATSPRUEFER
					);

					if (isError($kommissionPrueferRes))
						show_error(getError($kommissionPrueferRes));

					if (hasData($kommissionPrueferRes))
					{
						$kommissionPruefer = getData($kommissionPrueferRes);

						// set the mail for Kommission Prüfer
						foreach ($kommissionPruefer as $kp)
						{
							$kp->zustellung_mail = '';
							if (isEmptyString($kp->uid))
							{
								// if no user account, use private mail
								if (!isEmptyString($kp->private_email))
									$kp->zustellung_mail = $kp->private_email;
							}
							else // if has user account, use university mail
								$kp->zustellung_mail = $kommissionVorsitz->uid.'@'.DOMAIN;
						}

						// Bewertung of Vorsitz has to be displayed for Senatspruefer
						if ($projektarbeitsbeurteilung->betreuerart === self::BETREUERART_SENATSPRUEFER)
						{
							$vorsitzProjektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung(
								$projektarbeit_id,
								$vorsitz_person_id,
								$student_uid
							);

							// copy Bewertung and Note from Senatsvorsitz to Senatspruefer
							if (hasData($vorsitzProjektarbeitsbeurteilungResult))
							{
								$vorsitzProjektarbeitsbeurteilung = getData($vorsitzProjektarbeitsbeurteilungResult);
								$projektarbeitsbeurteilung->projektarbeit_bewertung = $vorsitzProjektarbeitsbeurteilung->projektarbeit_bewertung;
								$projektarbeitsbeurteilung->betreuernote = $vorsitzProjektarbeitsbeurteilung->betreuernote;
							}
						}
					}
				}

				// read only access if Projektarbeit is already sent, or logged in Betreuer is Senatspruefer of Kommission
				$readOnlyAccess = isset($projektarbeitsbeurteilung->abgeschicktamum)
									|| $projektarbeitsbeurteilung->betreuerart === self::BETREUERART_SENATSPRUEFER;

				$betreuerart = $projektarbeitsbeurteilung->betreuerart;

				// show correct view type, depending on betreuerart (Erstbegutachter or Zweitbegutachter)
				$viewname = 'projektarbeitsbeurteilung';
				if ($betreuerart === self::BETREUERART_ZWEITBEGUTACHTER)
					$viewname = 'projektarbeitsbeurteilung_zweitbegutachter';
				else
				{
					// if not a Zweitbegutachter, calculate the points reached and max points for displaying
					$bewertung_punkte = $this->_calculateBewertungPunkte($projektarbeitsbeurteilung->projektarbeit_bewertung);
					$projektarbeitsbeurteilung->bewertung_gesamtpunkte = $bewertung_punkte->gesamtpunkte;
					$projektarbeitsbeurteilung->bewertung_maxpunkte = $bewertung_punkte->maxpunkte;
				}

				$data = array(
					'projektarbeit_id' => $projektarbeit_id,
					'betreuer_person_id' => $betreuer_person_id, // Betreuer viewing/grading the Projektarbeit
					'student_uid' => $student_uid,
					'authtoken' => isset($authObj->authtoken) ? $authObj->authtoken : null,
					'projektarbeitsbeurteilung' => $projektarbeitsbeurteilung,
					'language' => $language,
					'zweitbetreuer_person_id' => $zweitbetreuer_person_id, // optional Zweitbetreuer
					'zweitbetreuer_abgeschicktamum' => $zweitbetreuer_abgeschicktamum,
					'kommission_vorsitz' => $kommissionVorsitz, // if kommissionell, Kommissionsvorsitz
					'kommission_betreuer' => $kommissionPruefer, // optional additional Kommissionspruefer
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
	 * Save Pruefungsprotokoll (including possible Freigabe).
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

							$saveProjektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->update(
								$projektarbeitsbeurteilung_id,
								$projektarbeitsbeurteilungToSave
							);
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
							$isPrimaryBetreuer = in_array(
								$betreuerart,
								array(self::BETREUERART_BACHELOR_BEGUTACHTER, self::BETREUERART_ERSTBEGUTACHTER, self::BETREUERART_SENATSVORSITZ)
							);

							// if the Erstbetreuer has sent the Bewertung
							if ($isPrimaryBetreuer)
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
								elseif ($isPrimaryBetreuer) // if primary Begutachter, set Note and send Studiengangmail
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

	/**
	 * Save only the title of a Projektarbeit.
	 */
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
	 * Download Projektarbeit document.
	 */
	public function downloadProjektarbeit()
	{
		$authObj = $this->_authenticate();

		// if successfully authenticated
		if (isset($authObj->person_id) && isset($authObj->username))
		{
			$projektarbeit_id = isset($authObj->projektarbeit_id) ? $authObj->projektarbeit_id : $this->input->get('projektarbeit_id');
			$endabgabeRes = $this->PaabgabeModel->getEndabgabe($projektarbeit_id);

			if (isError($endabgabeRes))
				show_error(getError($endabgabeRes));

			if (hasData($endabgabeRes))
			{
				$endabgabe = getData($endabgabeRes)[0];
				$filepath = PAABGABE_PATH.$endabgabe->filename;

				if (file_exists($filepath))
				{
					$this->output
						->set_status_header(200)
						->set_content_type('application/pdf', 'utf-8')
						->set_header('Content-Disposition: attachment; filename="'.$endabgabe->filename.'"')
						->set_output(file_get_contents($filepath))
						->_display();
				}
				else
				{
					show_error("File does not exist.");
				}
			}
		}
		else
		{
			show_error("Invalid authentication.");
		}
	}

	/**
	 * Send info mail to commitee members when Erstbetreuer requests their consultation.
	 */
	public function sendInfoMailToKommission()
	{
		$authObj = $this->_authenticate();

		if (!isset($authObj->person_id))
		{
			$this->outputJsonError('invalid authentication');
			return;
		}

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

		foreach ($this->_requiredFields as $requiredField => $fieldData)
		{
			if (!isset($bewertung[$requiredField]) || (is_string($bewertung[$requiredField]) && trim($bewertung[$requiredField]) === ''))
			{
				// if only save and not send, null values are allowed. Begruedung is only necessary when grade is 5.
				if ($saveAndSend == false || ($requiredField == 'begruendung' && $betreuernote != 5))
					continue;

				$fieldName = isset($fieldData['phrase']) ? $this->p->t('projektarbeitsbeurteilung', $fieldData['phrase']) : $requiredField;

				return error(ucfirst($fieldName) . ' ' . $this->p->t('ui', 'fehlt'));
			}

			$valid = true;
			$fieldType = isset($fieldData['type']) ? $fieldData['type'] : 'text';

			switch($fieldType)
			{
				case 'text':
					$valid = is_string($bewertung[$requiredField]);
					break;
				case 'bool':
					$valid = is_bool($bewertung[$requiredField]);
					break;
				case 'points':
					$valid = is_numeric($bewertung[$requiredField]);
					break;
				case 'grade':
					$valid = in_array($bewertung[$requiredField], array('1', '2', '3', '4', '5'));
					break;
			}

			if (!$valid)
				return error("$requiredField" . $this->p->t('ui', 'ungueltig'));
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

		foreach ($this->_requiredFields as $requiredField => $fieldData)
		{
			if (isset($fieldData['type']) && $fieldData['type'] == 'points')
			{
				if (isset($bewertung->{$requiredField}) && is_numeric($bewertung->{$requiredField}))
				{
					$punkte->gesamtpunkte += (float)$bewertung->{$requiredField};
				}
				$punkte->maxpunkte += self::CATEGORY_MAX_POINTS;
			}
		}

		return $punkte;
	}
}
