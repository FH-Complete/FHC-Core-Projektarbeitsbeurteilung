<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'Projektarbeitsbeurteilung Zweitbegutachter',
		'jquery3' => true,
		'jqueryui1' => true,
		'bootstrap3' => true,
		'fontawesome4' => true,
		'dialoglib' => true,
		'ajaxlib' => true,
		'sbadmintemplate3' => true,
		'phrases' => array(
			'projektarbeitsbeurteilung'
		),
		'customCSSs' => array(
			'public/css/sbadmin2/admintemplate_contentonly.css',
			'public/extensions/FHC-Core-Projektarbeitsbeurteilung/css/projektarbeitsbeurteilung.css'
		),
		'customJSs' => array(
			'public/extensions/FHC-Core-Projektarbeitsbeurteilung/js/projektarbeitsbeurteilung_zweitbegutachter.js'
		)
	)
);
?>
<body>
<div id="wrapper">
	<div id="page-wrapper">
		<div class="container-fluid" id="containerFluid">
            <?php if (!isset($projektarbeitsbeurteilung)):
					echo "Keine Projektarbeit eingetragen.";
            else:
            	$sent = isset($projektarbeitsbeurteilung->abgeschicktamum);
            	$paarbeittyp = $projektarbeitsbeurteilung->parbeit_typ === 'Bachelor' ? 'b' : 'm';
            	$arbeittypName = $paarbeittyp === 'b' ? $this->p->t('projektarbeitsbeurteilung', 'arbeitBachelor') : $this->p->t('abschlusspruefung', 'arbeitMaster');

				$titel = isset($projektarbeitsbeurteilung->projektarbeit_titel) ? $projektarbeitsbeurteilung->projektarbeit_titel : $projektarbeitsbeurteilung->projektarbeit_titel_english;
				$projektarbeit_bewertung = $projektarbeitsbeurteilung->projektarbeit_bewertung;
				?>
			<br />
			<br />
				<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/fhtechnikum_header.php', array()); ?>
			<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header">
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'beurteilung') ?>
						<?php echo $arbeittypName . ($paarbeittyp === 'm' ? '&nbsp-&nbsp' . $this->p->t('projektarbeitsbeurteilung', 'zweitBegutachter') : '') ?>
					</h3>
				</div>
			</div>
			<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/hiddenfields.php'); ?>
			<form id="beurteilungform">
			<div class="row">
				<div class="col-lg-12">
					<table class="table-condensed table-bordered table-responsive">
						<tr>
							<td>
								<b>
									<?php echo ucfirst($this->p->t('projektarbeitsbeurteilung', 'titelDerArbeit')) . ' ' . $arbeittypName ?>
								</b>
							</td>
							<td colspan="3"><?php echo $titel ?></td>
						</tr>
						<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/stammdaten.php'); ?>
					</table>
				</div>
			</div>
			<br />
            <div class="row">
                <div class="col-lg-12">
					<table class="table-condensed table-bordered table-responsive" id="beurteilungtbl">
						<thead>
							<tr>
								<th class="tableWidthHalf">&nbsp;</th>
								<th class="tableWidthHalf text-center"><?php echo $this->p->t('projektarbeitsbeurteilung', 'kurzeSchriftlicheBeurteilung') ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<b><?php echo $this->p->t('projektarbeitsbeurteilung', 'thema') ?></b><br />
									<ul>
										<li><?php echo $this->p->t('projektarbeitsbeurteilung', 'fragestellungRelevant') ?></li>
									</ul><br />
									<b><?php echo $this->p->t('projektarbeitsbeurteilung', 'inhaltMethode') ?></b><br />
									<ul>
										<li><?php echo $this->p->t('projektarbeitsbeurteilung', 'aufgabenstellungNachvollziehbar') ?></li>
										<li><?php echo $this->p->t('projektarbeitsbeurteilung', 'methodischeVorgangsweiseAngemessen') ?></li>
										<li><?php echo $this->p->t('projektarbeitsbeurteilung', 'mehrwertBerufspraxis') ?></li>
									</ul><br />
									<b><?php echo $this->p->t('projektarbeitsbeurteilung', 'eigenstaendigkeitErgebnis') ?></b><br />
									<ul>
										<li><?php echo $this->p->t('projektarbeitsbeurteilung', 'arbeitEigenstaendigVerfasst') ?></li>
									</ul><br />
									<b><?php echo $this->p->t('projektarbeitsbeurteilung', 'struktur') . ', ' . $this->p->t('projektarbeitsbeurteilung', 'form') ?></b><br />
									<ul>
										<li><?php echo $this->p->t('projektarbeitsbeurteilung', 'arbeitGutStrukturiert') ?></li>
										<li><?php echo $this->p->t('projektarbeitsbeurteilung', 'gliederungInhaltlichVerstaendlich') ?></li>
									</ul><br />
								</td>
								<td>
									<?php $readonly = (isset($projektarbeit_bewertung->beurteilung_zweitbegutachter) && $sent) ? ' readonly' : '' ?>
									<textarea class="form-control" cols="5" rows="16" name="beurteilung_zweitbegutachter"<?php echo $readonly ?>><?php echo isset($projektarbeit_bewertung->beurteilung_zweitbegutachter) ? $projektarbeit_bewertung->beurteilung_zweitbegutachter : '' ?></textarea>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<br />
			</form>
				<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/footer.php', array('sent' => $sent)); ?>
			<br />
			<?php endif; ?>
		</div>
	</div>
</div>
</body>
