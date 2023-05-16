<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once('AbstractProjektarbeitsbeurteilung.php');

/**
 * This controller is just for redirection if called from older emails with an old link pointing to Projektarbeitsbeurtielung instead of
 * the Erst- or Zweitbegutachter controller
 */
class Projektarbeitsbeurteilung extends AbstractProjektarbeitsbeurteilung
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
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
			$projektarbeit_id = isset($authObj->projektarbeit_id) ? $authObj->projektarbeit_id : $this->input->get('projektarbeit_id');

			if (!is_numeric($projektarbeit_id))
				show_error('invalid Projektarbeitsbeurteilung');

			// get type of logged in Betreuer
			$betreuerartRes = $this->ProjektbetreuerModel->getBetreuerart($projektarbeit_id, $betreuer_person_id);

			if (hasData($betreuerartRes))
			{
				$betreuerart = getData($betreuerartRes)[0]->betreuerart_kurzbz;

				// pass the get parameters in url
				$getParamsStr = http_build_query($this->input->get());

				// show correct form depending on who is logged in, Zweitbegutachter or other
				if ($betreuerart == self::BETREUERART_ZWEITBEGUTACHTER)
					redirect('extensions/FHC-Core-Projektarbeitsbeurteilung/ProjektarbeitsbeurteilungZweitbegutachter/showProjektarbeitsbeurteilung?'.$getParamsStr);
				else
					redirect('extensions/FHC-Core-Projektarbeitsbeurteilung/ProjektarbeitsbeurteilungErstbegutachter/showProjektarbeitsbeurteilung?'.$getParamsStr);
			}
			else
			{
				show_error('Projektbetreuer not found');
			}
		}
	}

	public function saveProjektarbeitsbeurteilung()
	{
		show_error('not implemented');
	}
}
