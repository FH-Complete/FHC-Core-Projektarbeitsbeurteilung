<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 */
class Projektarbeitsbeurteilung extends FHC_Controller
{
	const CATEGORY_MAX_POINTS = 10;
	const BETREUERART_ZWEITBEGUTACHTER = 'Zweitbegutachter';

    private $_uid;  // uid of the logged user
	private $_requiredFields = array(
			'gesamtnote' => 'grade',
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
        // Set required permissions
/*        parent::__construct(
            array(
                'index' => 'lehre/pruefungsbeurteilung:r',
                'Protokoll' => 'lehre/pruefungsbeurteilung:r',
                'saveProtokoll' => 'lehre/pruefungsbeurteilung:rw',
            )
        );*/

        // Load models
		$this->load->model('extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung_model', 'ProjektarbeitsbeurteilungModel');

        //$this->load->library('PermissionLib');
		//$this->load->library('AuthLib');
        //;

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
/*		$this->load->library('WidgetLib');

		$this->load->view('lehre/pruefungsprotokollUebersicht.php', $data)*/;
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

/*				print_r($projektarbeitsbeurteilung->projektarbeit_bewertung);
				die();*/

				$data = array(
					'projektarbeit_id' => $projektarbeit_id,
					'betreuer_person_id' => $betreuer_person_id,
					'projektarbeitsbeurteilung' => $projektarbeitsbeurteilung,
					'language' => $language
				);

				$this->load->view("extensions/FHC-Core-Projektarbeitsbeurteilung/$viewname.php", $data);
			}
			else
			{
				show_error('no Betreuer assigned for given Projektarbeit');
				//var_dump($projektarbeitsbeurteilungResult);
			}
		}
		else
			show_error('invalid authentication');
	}

	/**
	 * Save Pruefungsprotokoll (including possible Freigabe)
	 */
	public function saveProjektarbeitsbeurteilung()
	{
		$abgeschicktamum = date('Y-m-d H:i:s');

		$authObj = $this->_authenticate();

		if (isset($authObj->person_id) && isset($authObj->username))
		{
			$this->load->model('education/Projektbetreuer_model', 'ProjektbetreuerModel');
			$this->load->model('extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung_model', 'ProjektarbeitsbeurteilungModel');

			$result = null;

			$betreuer_person_id = $authObj->person_id;

			$saveAndSend = $this->input->post('saveAndSend');
			if ($saveAndSend === 'true')
				$saveAndSend = true;
			elseif ($saveAndSend === 'false')
				$saveAndSend = false;

			$projektarbeit_id = $this->input->post('projektarbeit_id');
			$betreuerart = $this->input->post('betreuerart');
			$bewertung = $this->input->post('bewertung');

/*			var_dump($saveAndSend);
			var_dump($projektarbeit_id);
			var_dump($betreuer_person_id);
			var_dump($bewertung);*/

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
				//var_dump($bewertung);
				$bewertung = $this->_prepareBeurteilungDataForSave($bewertung);

/*				var_dump($saveAndSend);
				die();*/

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

				$bewertung['beurteilungsdatum'] = $abgeschicktamum;

				$bewertungJson = json_encode($bewertung);

				if (!$bewertungJson)
				{
					$this->outputJsonError('Invalid JSON Format');
				}
				else
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

						$this->outputJsonSuccess($this->ProjektarbeitsbeurteilungModel->update($projektarbeitsbeurteilung_id, $projektarbeitsbeurteilungToSave));
					}
					else
					{
						// no existing Beurteilung -> insert if there is corresponding Projektbetreuer
						$projektbetreuerResult = $this->ProjektbetreuerModel->loadWhere(
							array(
								'projektarbeit_id' => $projektarbeit_id,
								'person_id' => $betreuer_person_id,
								'betreuerart_kurzbz' => $betreuerart
							)
						);

						if (hasData($projektbetreuerResult))
						{
							$projektarbeitsbeurteilungToSave['insertvon'] = $authObj->username;

							$this->outputJsonSuccess($this->ProjektarbeitsbeurteilungModel->insert($projektarbeitsbeurteilungToSave));
						}
					}
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
		if (isset($token))
		{
			// TODO if token present, auth by token
		}
		else
		{
			$this->load->library('AuthLib');
			$authObj = $this->authlib->getAuthObj();
			//var_dump($authObj);

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
		$gesamtnote = isset ($bewertung['gesamtnote']) ? $bewertung['gesamtnote'] : null;

		foreach ($this->_requiredFields as $required_field => $fieldtype)
		{
			if (!isset($bewertung[$required_field]) || (is_string($bewertung[$required_field]) && trim($bewertung[$required_field]) === ''))
			{
				// if only save and not send, null values are allowed. Begruedung is only necessary when grade is 5.
				if ($saveAndSend == false || ($required_field == 'begruendung' && $gesamtnote != 5))
					continue;

				return error("$required_field missing");

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
				return error("$required_field invalid");
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
