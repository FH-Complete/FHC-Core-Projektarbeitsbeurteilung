<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 */
abstract class AbstractProjektarbeitsbeurteilung extends FHC_Controller
{
	// Projektarbeit types
	const BETREUERART_BACHELOR_BEGUTACHTER = 'Begutachter';
	const BETREUERART_ERSTBEGUTACHTER = 'Erstbegutachter';
	const BETREUERART_ZWEITBEGUTACHTER = 'Zweitbegutachter';
	const BETREUERART_SENATSVORSITZ = 'Senatsvorsitz';
	const BETREUERART_SENATSMITGLIED = 'Senatsmitglied';
	const EXTERNER_BEURTEILER_NAME = 'externerBeurteiler';

	// fields required to be filled out by Betreuer
	protected $requiredFields = array();

	// path to header logo
	protected $logoPath = '';

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

		// load config
		$this->config->load('extensions/FHC-Core-Projektarbeitsbeurteilung/config');

		// set logo path
		$this->logoPath = $this->config->item('projektarbeitsbeurteilung_logo_path');

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
	abstract public function showProjektarbeitsbeurteilung();

	/**
	 * Save Pruefungsprotokoll (including possible Freigabe).
	 */
	abstract public function saveProjektarbeitsbeurteilung();

	/**
	 * Change interface language.
	 * @return object success or error
	 */
	public function changeLanguage()
	{
		$language = $this->input->get('language');
		$this->outputJson(setUserLanguage($language));
	}

	// -----------------------------------------------------------------------------------------------------------------
	// Protected methods

	/**
	 * Performs authentification, either normally or via authtoken.
	 * @return object|null object with authentification data or null on authentification failure
	 */
	protected function authenticate()
	{
		$authData = null;

		$token = $this->input->post('authtoken');
		$projektarbeit_id = $this->input->get('projektarbeit_id');

		if (!isset($projektarbeit_id)) // posted on save
			$projektarbeit_id = $this->input->post('projektarbeit_id');

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
	protected function prepareBeurteilungDataForSave($data)
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
	protected function checkBewertung($bewertung, $saveAndSend = false)
	{
		$betreuernote = isset ($bewertung['betreuernote']) ? $bewertung['betreuernote'] : null;

		foreach ($this->requiredFields as $requiredField => $fieldData)
		{
			// check if empty/null
			if (!isset($bewertung[$requiredField]) || (is_string($bewertung[$requiredField]) && trim($bewertung[$requiredField]) === ''))
			{
				// if only save and not send, null values are allowed. Begruedung is only necessary when grade is 5.
				if ($saveAndSend == false || ($requiredField == 'begruendung' && $betreuernote != 5))
					continue;

				// get field name for error message (phrase)
				$fieldName = isset($fieldData['phrase']) ? $this->p->t('projektarbeitsbeurteilung', $fieldData['phrase']) : $requiredField;

				return error(ucfirst($fieldName) . ' ' . $this->p->t('ui', 'fehlt'));
			}

			// check field type
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

			// return error if invalid
			if (!$valid)
				return error("$requiredField" . $this->p->t('ui', 'ungueltig'));
		}

		return success('Bewertung check passed');
	}

	/**
	 * Download Projektarbeit document.
	 */
	public function downloadProjektarbeit()
	{
		$authObj = $this->authenticate();

		// if successfully authenticated
		if (isset($authObj->person_id) && isset($authObj->username))
		{
			$projektarbeit_id = isset($authObj->projektarbeit_id) ? $authObj->projektarbeit_id : $this->input->post('projektarbeit_id');
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
}
