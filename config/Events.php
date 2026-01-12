<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use CI3_Events as Events;
use FHCAPI_Controller as FHCAPI_Controller;

Events::on('projektbeurteilung_download_link', function ($projektarbeit_id, $betreuerart_kurzbz, $person_id, $downloadLinkFunc) {

	$ci =& get_instance();

	$ci->load->model('extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung_model', 'ProjektarbeitsbeurteilungModel');

	$ci->ProjektarbeitsbeurteilungModel->addSelect('1');
	$ci->ProjektarbeitsbeurteilungModel->db->where('abgeschicktvon IS NOT NULL', NULL, FALSE);
	$result = $ci->ProjektarbeitsbeurteilungModel->loadWhere(
		[
			'projektarbeit_id' => $projektarbeit_id,
			'betreuerart_kurzbz' => $betreuerart_kurzbz,
			'betreuer_person_id' => $person_id
		]
	);

	$downloadLink = hasData($result)
		? APP_ROOT.'addons/fhtw/content/projektbeurteilung/projektbeurteilungDocumentExport.php'.
			'?projektarbeit_id='.$projektarbeit_id.'&betreuerart_kurzbz='.$betreuerart_kurzbz.'&person_id='.$person_id
		: '';

	$downloadLinkFunc($downloadLink);
});

// checks if there is a projektarbeitsbeurteilung for a projektarbeit, deletes the Beurteilung if authorized
Events::on('projektarbeitsbeurteilung_delete', function ($projektarbeit_id, $checkDeleteFunc) {

	$ci =& get_instance();

	$ci->load->model('extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung_model', 'ProjektarbeitsbeurteilungModel');
	$ci->load->library('PermissionLib');


	$ci->ProjektarbeitsbeurteilungModel->addSelect('projektarbeitsbeurteilung_id');
	$result = $ci->ProjektarbeitsbeurteilungModel->loadWhere(['projektarbeit_id' => $projektarbeit_id]);

	if (isError($result))
	{
		$ci->addError(getError($result), FHCAPI_Controller::ERROR_TYPE_GENERAL);
		return;
	}

	$isBerechtigt = $ci->permissionlib->isBerechtigt('paarbeit/beurteilung_loeschen', 'suid');

	// successfull if no Projektarbeitsbeurteilung or authorized for deletion
	$checkDeleteFunc(!hasData($result) || $isBerechtigt);

	if (hasData($result) && $isBerechtigt)
	{
		foreach (getData($result) as $beurteilung)
		{
			// delete the Projektarbeitsbeurteilung
			$projektarbeitsbeurteilung_id = $beurteilung->projektarbeitsbeurteilung_id;
			$result = $ci->ProjektarbeitsbeurteilungModel->delete($projektarbeitsbeurteilung_id);
			if (isError($result)) $ci->addError(getError($result), FHCAPI_Controller::ERROR_TYPE_GENERAL);
		}
	}
});
