<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once('AbstractProjektarbeitsbeurteilung.php');

/**
 */
class ProjektarbeitsbeurteilungErstbegutachter extends AbstractProjektarbeitsbeurteilung
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// set point fields to be filled out for assessment
		$this->pointFields = array(
			'bewertung_problemstellung' => array('type' => 'points', 'phrase' => 'problemstellungZieldefinition'),
			'bewertung_methode' => array('type' => 'points', 'phrase' => 'methodikLoesungsansatz'),
			'bewertung_ergebnissediskussion' => array('type' => 'points', 'phrase' => 'ergebnisseDiskussion'),
			'bewertung_struktur' => array('type' => 'points', 'phrase' => 'strukturAufbau'),
			'bewertung_stil' => array('type' => 'points', 'phrase' => 'stilAusdruck'),
			'bewertung_zitierregeln' => array('type' => 'points', 'phrase' => 'zitierregelnQuellenangaben')
		);

		// set all fields required for assessment
		$this->requiredFields = array_merge(
			$this->pointFields,
			array(
				'plagiatscheck_unauffaellig' => array('type' => 'bool', 'phrase' => 'plagiatscheck'),
				'begruendung' => array('type' => 'text', 'phrase' => 'begruendungText'),
				'gesamtpunkte' => array('type' => 'points', 'phrase' => 'gesamtpunkte'),
				'betreuernote' => array('type' => 'grade', 'phrase' => 'betreuernote')
			)
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
			}

			// get Projektarbeitsbeurteilung data for given Projektarbeit and Betreuer
			$projektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung(
				$projektarbeit_id,
				$betreuer_person_id,
				$student_uid,
				array(
					self::BETREUERART_BACHELOR_BEGUTACHTER,
					self::BETREUERART_ERSTBEGUTACHTER,
					self::BETREUERART_SENATSVORSITZ,
					self::BETREUERART_SENATSMITGLIED
				)
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

						// Bewertung of Vorsitz has to be displayed for Senatsmitglied
						if ($projektarbeitsbeurteilung->betreuerart === self::BETREUERART_SENATSMITGLIED)
						{
							$vorsitzProjektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->getProjektarbeitsbeurteilung(
								$projektarbeit_id,
								$vorsitz_person_id,
								$student_uid,
								self::BETREUERART_SENATSVORSITZ
							);

							// copy Bewertung and Note from Senatsvorsitz to Senatsmitglied
							if (hasData($vorsitzProjektarbeitsbeurteilungResult))
							{
								$vorsitzProjektarbeitsbeurteilung = getData($vorsitzProjektarbeitsbeurteilungResult);
								$projektarbeitsbeurteilung->projektarbeit_bewertung = $vorsitzProjektarbeitsbeurteilung->projektarbeit_bewertung;
								$projektarbeitsbeurteilung->betreuernote = $vorsitzProjektarbeitsbeurteilung->betreuernote;
								$projektarbeitsbeurteilung->abgeschicktamum = $vorsitzProjektarbeitsbeurteilung->abgeschicktamum;
							}
						}
					}
				}

				// read only access if Projektarbeit is already sent, or logged in Betreuer is Senatsmitglied of Kommission
				$readOnlyAccess = isset($projektarbeitsbeurteilung->abgeschicktamum)
									|| $projektarbeitsbeurteilung->betreuerart === self::BETREUERART_SENATSMITGLIED;

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
					'readOnlyAccess' => $readOnlyAccess,
					'logoPath' => $this->logoPath,
					'pointFields' => $this->pointFields
				);

				// load the view
				$this->load->view("extensions/FHC-Core-Projektarbeitsbeurteilung/projektarbeitsbeurteilung.php", $data);
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
				'controllerName' => 'ProjektarbeitsbeurteilungErstbegutachter'
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
				$bewertung = $this->prepareBeurteilungDataForSave($bewertung);

				// check entered Bewertung for validity
				$checkRes = $this->checkBewertung($bewertung, $saveAndSend);

				if (isError($checkRes))
				{
					$this->outputJsonError(getError($checkRes));
					return;
				}

				// get betreuernote to save
				$betreuernote = isset($bewertung['betreuernote']) ? $bewertung['betreuernote'] : null;
				unset($bewertung['betreuernote']); // Grade is saved in Projektbeurteiler tbl, not Projektarbeitsbeurteilung
				$bewertung['version'] = self::PROJEKTARBEITSBEURTEILUNG_VERSION; // set version
				$bewertung['beurteilungsdatum'] = $abgeschicktamum; // set send date

				// encode bewertung into json format
				$bewertungJson = json_encode($bewertung);

				if (!$bewertungJson)
				{
					$this->outputJsonError('Invalid JSON Format');
					return;
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
						$saveProjektarbeitsbeurteilungResult = $this->ProjektarbeitsbeurteilungModel->saveProjektarbeitsbeurteilung(
							$projektarbeit_id,
							$betreuer_person_id,
							$betreuerart,
							$bewertungJson,
							$authObj->username,
							$saveAndSend ? $abgeschicktamum : null
						);

						if (isError($saveProjektarbeitsbeurteilungResult))
						{
							$this->outputJsonError(getError($saveProjektarbeitsbeurteilungResult));
							return;
						}

						$isPrimaryBetreuer = in_array(
							$betreuerart,
							array(self::BETREUERART_BACHELOR_BEGUTACHTER, self::BETREUERART_ERSTBEGUTACHTER, self::BETREUERART_SENATSVORSITZ)
						);

						// if Betreuer can grade the Bewertung
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
								return;
							}
						}

						// additional actions if not only saved, but also sent (finalized) Beurteilung
						if ($saveAndSend === true)
						{
							if ($isPrimaryBetreuer) // if primary Begutachter, set Note and send Studiengangmail
							{
								 // set Note
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
									return;
								}

								$mailErrors = array();

								// send info mail to Studiengang after Begutachter has finished assessment
								$mailResult = $this->projektarbeitsbeurteilungmaillib->sendInfoMailToStudiengang(
									$projektarbeit_id,
									$betreuer_person_id
								);

								if (isError($mailResult))
									$mailErrors[] = getError($mailResult);

								// send info mail to student after Begutachter has finished assessment
								$mailResult = $this->projektarbeitsbeurteilungmaillib->sendInfoMailToStudent(
									$projektarbeit_id,
									$betreuer_person_id
								);

								if (isError($mailResult))
									$mailErrors[] = getError($mailResult);

								if (!isEmptyArray($mailErrors))
								{
									$this->outputJsonError(implode(', ', $mailErrors));
									return;
								}
							}
						}

						$this->outputJsonSuccess(getData($saveProjektarbeitsbeurteilungResult));
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
		$authObj = $this->authenticate();

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
		$authObj = $this->authenticate();

		if (!isset($authObj->person_id))
		{
			$this->outputJsonError('invalid authentication');
			return;
		}

		$projektarbeit_id = $this->input->post('projektarbeit_id');

		$mailRes = $this->projektarbeitsbeurteilungmaillib->sendInfoMailToKommission($projektarbeit_id);

		$this->outputJson($mailRes);
	}
}
