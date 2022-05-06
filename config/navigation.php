<?php

$config['navigation_header']['*']['Lehre']['children']['Projektarbeiten'] = array(
	'link' => site_url('extensions/FHC-Core-Projektarbeitsbeurteilung/Projektuebersicht'),
	'sort' => 40,
	'description' => 'Projektarbeiten',
	'expand' => false,
	'requiredPermissions' => 'assistenz:r'
);
