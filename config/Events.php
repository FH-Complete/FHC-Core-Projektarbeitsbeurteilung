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
