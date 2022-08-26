<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'Projektübersicht',
		'jquery' => true,
		'jqueryui' => true,
		'bootstrap' => true,
		'fontawesome' => true,
		'sbadmintemplate' => true,
		'tablesorter' => true,
		'ajaxlib' => true,
		'filterwidget' => true,
		'tablewidget' => true,
		'navigationwidget' => true,
		'dialoglib' => true,
		'tabulator' => true,
		'phrases' => array(
			'projektarbeitsbeurteilung' => array(
					'projektarbeitsbeurteilungUebersicht',
					'abgabedatum',
					'erstBegutachter',
					'zweitBegutachter',
					'freischalten',
					'freischaltung',
					'kommissionsmitglieder'
			),
			'person' => array('vorname', 'nachname', 'uid'),
			'global' => array('titel', 'abgeschickt', 'uploaddatum'),
			'lehre' => array('note', 'studiengang'),
			'ui' => array('bitteEintragWaehlen', 'projektarbeit', 'senden')

		),
		'customCSSs' => array('public/css/sbadmin2/tablesort_bootstrap.css', 'public/extensions/FHC-Core-Projektarbeitsbeurteilung/css/projektuebersicht.css'),
		'customJSs' => array('public/js/bootstrapper.js', 'public/extensions/FHC-Core-Projektarbeitsbeurteilung/js/projektUebersicht.js'),
	)
);
?>

<body>
<div id="wrapper">

	<?php echo $this->widgetlib->widget('NavigationWidget'); ?>

	<div id="page-wrapper">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header">
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'projektarbeitsbeurteilungUebersicht') ?>
					</h3>
				</div>
			</div>
			<div>
				<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/projektuebersicht/projektUebersichtData.php'); ?>
			</div>
		</div>
	</div>
</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>
