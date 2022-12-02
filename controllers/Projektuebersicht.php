<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once('Projektarbeitsbeurteilung.php');

class Projektuebersicht extends Auth_Controller
{

	private $_uid;

	private $_ci;

	const FILTER_ID = 'filter_id';
	const PREV_FILTER_ID = 'prev_filter_id';
	const BERECHTIGUNG_KURZBZ = 'assistenz';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
			'index'=> 'assistenz:rw',
			'unlockAssessment' => 'assistenz:rw',
			'resendToken' => 'assistenz:rw'
			)
		);

		$this->_ci =& get_instance();
		$this->_setAuthUID(); // sets property uid
		$this->_ci->load->library('WidgetLib');
		$this->_ci->load->library('VariableLib', array('uid' => $this->_uid));
		$this->_ci->load->library('PermissionLib');

		$this->_ci->load->model('person/Person_model', 'PersonModel');
		$this->_ci->load->model('extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung_model', 'ProjektarbeitsbeurteilungModel');
		$this->_ci->load->model('education/Projektarbeit_model', 'ProjektarbeitModel');
		$this->_ci->load->model('education/Projektbetreuer_model', 'ProjektbetreuerModel');
		$this->_ci->load->model('crm/Student_model', 'StudentModel');

		$this->load->helper('hlp_sancho_helper');

		$this->_ci->loadPhrases(
			array(
				'projektarbeitsbeurteilung',
				'person',
				'global',
				'lehre',
				'filter',
				'ui'
			)
		);

		$this->setControllerId(); // sets the controller id
	}

	/**
	 * Retrieve the UID of the logged user and checks if it is valid
	 */
	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();

		if (!$this->_uid) show_error('User authentification failed');
	}

	public function index()
	{
		$data[self::FHC_CONTROLLER_ID] = $this->getControllerId();
		$data[self::PREV_FILTER_ID] = $this->_ci->input->get(self::PREV_FILTER_ID);
		$oeKurzbz = $this->_ci->permissionlib->getOE_isEntitledFor(self::BERECHTIGUNG_KURZBZ);

		if (!$oeKurzbz)
			show_error('Keine Berechtigung.');

		$data['oeKurz'] = $oeKurzbz;

		$this->_ci->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/projektuebersicht/projektUebersicht.php', $data);
	}


	public function unlockAssessment()
	{
		$personid = $this->_ci->input->post('personid');
		$projektarbeitid = $this->_ci->input->post('projektid');

		$projektarbeit = $this->_ci->ProjektarbeitModel->load($projektarbeitid);

		if (!hasData($projektarbeit))
			$this->terminateWithJsonError('Keine Projektarbeit gefunden');

		$projektarbeit = getData($projektarbeit)[0];

		$person = $this->_ci->PersonModel->load($personid);

		if (!hasData($person))
			$this->terminateWithJsonError('Die Person kann nicht geladen werden.');

		$person = getData($person)[0];

		$projektbeurteilung = $this->_ci->ProjektarbeitsbeurteilungModel->loadWhere(array('betreuer_person_id' => $person->person_id, 'projektarbeit_id' => $projektarbeit->projektarbeit_id));

		if (!hasData($projektbeurteilung))
			$this->terminateWithJsonError('Die Projektbeurteilung konnte nicht gefunden werden.');

		$projektbeurteilung = getData($projektbeurteilung)[0];

		$result = $this->_ci->ProjektarbeitsbeurteilungModel->update(
			$projektbeurteilung->projektarbeitsbeurteilung_id,
			array(
				'abgeschicktamum' => null,
				'abgeschicktvon' => null
			)
		);

		if (isSuccess($result))
			$this->outputJsonSuccess('Erfolgreich freigeschalten');
		else
			$this->terminateWithJsonError('Fehler beim freischalten');
	}

	public function resendToken()
	{
		$erstbetreuerid = $this->_ci->input->post('personid');
		$projektarbeitid = $this->_ci->input->post('projektid');
		$studentid = $this->_ci->input->post('studentid');
		$kommissionprueferid = $this->_ci->input->post('kommissionprueferid');

		// optional kommissionspruefer id if kommissionelle Prüfung (can have multiple Pruefer)
		// token needs to be sent for one Pruefer at a time, so id identification needed
		if (isEmptyString($kommissionprueferid))
			$kommissionprueferid = null;

		$projektarbeit = $this->_ci->ProjektarbeitModel->load($projektarbeitid);

		if (!hasData($projektarbeit))
			$this->terminateWithJsonError('Keine Projektarbeit gefunden');

		$projektarbeit = getData($projektarbeit)[0];

		$person = $this->_ci->PersonModel->load($erstbetreuerid);

		if (!hasData($person))
			$this->terminateWithJsonError('Die Person kann nicht geladen werden.');

		$person = getData($person)[0];

		$db = new DB_Model();
		$qry = "SELECT * FROM campus.vw_benutzer WHERE uid = ?";
		$student = $db->execReadOnlyQuery($qry, array($studentid));

		if (!hasData($student))
			$this->terminateWithJsonError('Student kann nicht geladen werden.');

		$student = getData($student)[0];

		// Get Zweitbegutachter before Token generation
		$zweitbegutachter = $this->_ci->ProjektbetreuerModel->getZweitbegutachterWithToken(
			$erstbetreuerid,
			$projektarbeit->projektarbeit_id,
			$student->uid,
			$kommissionprueferid
		);

		if (!hasData($zweitbegutachter))
			$this->terminateWithJsonError('Zweitbegutachter kann nicht geladen werden.');

		$zweitbegutachter = getData($zweitbegutachter)[0];

		// Token generation
		$tokenGenRes = $this->_ci->ProjektbetreuerModel->generateZweitbegutachterToken(
			$zweitbegutachter->person_id,
			$projektarbeit->projektarbeit_id
		);

		if(isSuccess($tokenGenRes))
		{
			// Get Zweitbegutachter after Token generation
			$zweitbegutachter = $this->_ci->ProjektbetreuerModel->getZweitbegutachterWithToken(
				$erstbetreuerid,
				$projektarbeit->projektarbeit_id,
				$student->uid,
				$kommissionprueferid
			);

			if (!hasData($zweitbegutachter))
				$this->terminateWithJsonError('Zweitbegutachter kann nicht geladen werden.');

			$zweitbetr = getData($zweitbegutachter)[0];

			$mail_link = CIS_ROOT."index.ci.php/extensions/FHC-Core-Projektarbeitsbeurteilung/".
			(
				$zweitbetr->betreuerart_kurzbz == AbstractProjektarbeitsbeurteilung::BETREUERART_ZWEITBEGUTACHTER
				? "ProjektarbeitsbeurteilungZweitbegutachter"
				: "ProjektarbeitsbeurteilungErstbegutachter"
			);

			$zweitbetmaildata = array();
			$zweitbetmaildata['geehrt'] = "geehrte" . ($zweitbetr->anrede == "Herr" ? "r" : "");
			$zweitbetmaildata['anrede'] = $zweitbetr->anrede;
			$zweitbetmaildata['betreuer_voller_name'] = $zweitbetr->voller_name;
			$zweitbetmaildata['student_anrede'] = $student->anrede;
			$zweitbetmaildata['student_voller_name'] = trim($student->titelpre." ".$student->vorname." ".$student->nachname." ".$student->titelpost);
			$zweitbetmaildata['abgabetyp'] = 'Endabgabe';
			$zweitbetmaildata['parbeituebersichtlink'] = "";
			$zweitbetmaildata['bewertunglink'] = "<p><a href='$mail_link'>Zur Beurteilung der Arbeit</a></p>";
			$zweitbetmaildata['token'] = "<p>Zugangstoken: " . $zweitbetr->zugangstoken . "</p>";

			sendSanchoMail(
				'ParbeitsbeurteilungEndupload',
				$zweitbetmaildata,
				$zweitbetr->email,
				"Masterarbeitsbetreuung",
				'sancho_header_min_bw.jpg',
				'sancho_footer_min_bw.jpg',
				$this->_uid . "@" . DOMAIN
			);

			return $this->outputJsonSuccess('E-Mail wurde erfolgreich gesendet');
		}
		else
			$this->terminateWithJsonError('Fehler beim Erstellen des Tokens.');
	}
}
