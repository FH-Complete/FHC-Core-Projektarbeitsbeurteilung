<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 */
class Projektarbeitsbeurteilung extends FHC_Controller
{
	const CATEGORY_MAX_POINTS = 10;
	const BETREUERART_ZWEITBEGUTACHTER = 'Zweitbegutachter';
	const EXTERNER_BEURTEILERAME = 'externerBeurteiler';

    private $_uid;  // uid of the logged user
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

        //$this->load->library('PermissionLib');
		//$this->load->library('AuthLib');

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

        //$this->_setAuthUID(); // sets property uid

       // $this->setControllerId(); // sets the controller id
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
			$student_uid = $this->input->get('uid');
			$projektarbeit_id = $this->input->get('projektarbeit_id');

			// if params not passed, check if retrieved by token
			if (!isset($projektarbeit_id) && isset($authObj->projektarbeit_id))
				$projektarbeit_id = $authObj->projektarbeit_id;

			if (!isset($student_uid) && isset($authObj->uid))
				$student_uid = $authObj->uid;

			if (!is_numeric($projektarbeit_id))
				show_error('invalid Projektarbeitsbeurteilung');

			if (isEmptyString($student_uid))
				show_error('invalid student uid');

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
					'authtoken' => isset($authObj->authtoken) ? $authObj->authtoken : null,
					'projektarbeitsbeurteilung' => $projektarbeitsbeurteilung,
					'language' => $language
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

			$projektarbeit_id = $this->input->post('projektarbeit_id');
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
							// update grade in Projektbetreuer tbl
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
/*		$this->load->library('AuthLib', array(false));
		$authObj = $this->authlib->getAuthObj();

		$logged = isLogged();

		var_dump($logged);
		var_dump($authObj);
		die();*/

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
	 * Prepares Abschlussprüfung for save in database, replaces '' with null, sets Freigabedatum
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

				return error("$required_field " . $this->p->t('ui', 'fehlt'));

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
}
