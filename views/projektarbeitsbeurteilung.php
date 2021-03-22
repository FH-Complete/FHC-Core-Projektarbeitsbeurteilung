<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'Projektarbeitsbeurteilung',
		'jquery' => true,
		'jqueryui' => true,
		'bootstrap' => true,
		'fontawesome' => true,
		'dialoglib' => true,
		'ajaxlib' => true,
		'sbadmintemplate' => true,
		'phrases' => array(
			'projektarbeitsbeurteilung',
			'lehre'
		),
		'customCSSs' => array(
			'public/css/sbadmin2/admintemplate_contentonly.css',
			'public/extensions/FHC-Core-Projektarbeitsbeurteilung/css/projektarbeitsbeurteilung.css'
		),
		'customJSs' => array(
			'public/extensions/FHC-Core-Projektarbeitsbeurteilung/js/projektarbeitsbeurteilung.js'
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
            	$arbeittypName = $paarbeittyp === 'b' ? $this->p->t('abschlusspruefung', 'arbeitBachelor') : $this->p->t('abschlusspruefung', 'arbeitMaster');

				$titel = isset($projektarbeitsbeurteilung->projektarbeit_titel) ? $projektarbeitsbeurteilung->projektarbeit_titel : $projektarbeitsbeurteilung->projektarbeit_titel_english;
				$projektarbeit_bewertung = $projektarbeitsbeurteilung->projektarbeit_bewertung;
				$plagiatscheck_unauffaellig = isset($projektarbeit_bewertung->plagiatscheck_unauffaellig) && $projektarbeit_bewertung->plagiatscheck_unauffaellig === true ? $projektarbeit_bewertung->plagiatscheck_unauffaellig : false;
				?>
			<br />
			<br />
				<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/fhtechnikum_header.php', array()); ?>
			<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header">
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'beurteilung') ?>&nbsp;
						<?php echo $arbeittypName . ($paarbeittyp === 'm' ? '&nbsp' . $this->p->t('projektarbeitsbeurteilung', 'erstBegutachter') : '') ?>
					</h3>
				</div>
			</div>
			<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/hiddenfields.php'); ?>
			<form id="beurteilungform">
			<div class="row">
				<div class="col-lg-12">
					<table class="table-condensed table-bordered table-responsive">
						<tr>
							<td><b><?php echo ucfirst($this->p->t('global', 'titel')) ?></b></td>
							<td colspan="3"><?php echo $titel ?></td>
						</tr>
						<tr>
							<td><b><?php echo $this->p->t('projektarbeitsbeurteilung', 'plagiatscheckUnauffaellig') ?></b></td>
							<td colspan="3">
								<?php if ($sent): ?>
									<?php echo $plagiatscheck_unauffaellig ? ucfirst($this->p->t('ui', 'ja')) : ucfirst($this->p->t('ui', 'nein')) ?>
								<?php else: ?>
									<input type="checkbox" name="plagiatscheck_unauffaellig" id="plagiatscheck_unauffaellig" value="true"<?php echo $plagiatscheck_unauffaellig === true ? ' checked="checked"' : ''?>>
								<?php endif; ?>
							</td>
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
								<th>
									<b>
										<?php echo $this->p->t('projektarbeitsbeurteilung', 'kriterien') ?>
									</b>
								</th>
								<th>
									&nbsp;
								</th>
								<th class="text-center">
									<b>
										<?php echo $this->p->t('projektarbeitsbeurteilung', 'punkte') ?>
									</b>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<b>
										1.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'thema') ?>
									</b>
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'themaText') ?>
								</td>
									<?php //projektarbeit_bewertung needs to be passed only first time
										$this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php',
											array('name' => 'thema', 'projektarbeit_bewertung' => $projektarbeit_bewertung));?>
							</tr>
							<tr>
								<td>
									<b>
										2.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'loesungsansatz') ?>
									</b>
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'loesungsansatzText') ?>
								</td>
									<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php', array('name' => 'loesungsansatz')); ?>
							</tr>
							<tr>
								<td>
									<b>
										3.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'methode') ?>
									</b>
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'methodeText') ?>
								</td>
									<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php', array('name' => 'methode')); ?>
							</tr>
							<tr>
								<td>
									<b>
										4.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'ereignisseDiskussion') ?>
									</b>
								</td>
								<td>
									<?php echo $paarbeittyp === 'm' ? $this->p->t('projektarbeitsbeurteilung', 'ereignisseDiskussionTextMaster') : $this->p->t('projektarbeitsbeurteilung', 'ereignisseDiskussionText') ?>
								</td>
									<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php', array('name' => 'ereignissediskussion')); ?>
							</tr>
							<tr>
								<td>
									<b>
										5.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'eigenstaendigkeit') ?>
									</b>
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'eigenstaendigkeitText') ?>
								</td>
									<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php', array('name' => 'eigenstaendigkeit')); ?>
							</tr>
							<tr>
								<td>
									<b>
										6.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'struktur') ?>
									</b>
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'strukturText') ?>
								</td>
									<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php', array('name' => 'struktur')); ?>
							</tr>
							<tr>
								<td>
									<b>
										7.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'stil') ?>
									</b>
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'stilText') ?>
								</td>
									<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php', array('name' => 'stil')); ?>
							</tr>
							<tr>
								<td>
									<b>
										8.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'form') ?>
									</b>
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'formText') ?>
								</td>
									<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php', array('name' => 'form')); ?>
							</tr>
							<tr>
								<td>
									<b>
										9.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'literatur') ?>
									</b>
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'literaturText') ?>
								</td>
									<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php', array('name' => 'literatur')); ?>
							</tr>
							<tr>
								<td>
									<b>
										10.&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'zitierregeln') ?>
									</b>
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'zitierregelnText') ?>
								</td>
									<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/beurteilungspunkte.php', array('name' => 'zitierregeln')); ?>
							</tr>
							<tr>
								<td colspan="2" class="text-right">
									<b><?php echo $this->p->t('projektarbeitsbeurteilung', 'gesamtpunkte') ?></b>
								</td>
								<td>
									<b>
									<span id="gesamtpunkte">
										<?php echo isset($projektarbeitsbeurteilung->bewertung_gesamtpunkte) && is_numeric($projektarbeitsbeurteilung->bewertung_gesamtpunkte) ? $projektarbeitsbeurteilung->bewertung_gesamtpunkte : 0 ?></span><?php echo isset($projektarbeitsbeurteilung->bewertung_maxpunkte) && is_numeric($projektarbeitsbeurteilung->bewertung_maxpunkte) ? '/' . $projektarbeitsbeurteilung->bewertung_maxpunkte : '' ?>
									</b>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<br />
				<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/notenschluessel.php', array('arbeittypName' => $arbeittypName)); ?>
			<br />
			<?php if ($paarbeittyp === 'm'): ?>
				<div class="row">
					<div class="col-lg-12">
						<?php if (isset($zweitbetreuer_person_id)): ?>
							<?php echo $this->p->t('projektarbeitsbeurteilung', 'gutachtenZweitBegutachtung') ?>
							<br />
							<a href="<?php echo site_url() . '/extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung?projektarbeit_id=' . $projektarbeit_id . '&uid=' . $student_uid . '&zweitbetreuer_id=' . $zweitbetreuer_person_id ?>" target="_blank">
								<i class="fa fa-external-link"></i>&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'zurZweitbegutachterBewertung') ?>
							</a>
						<?php else: ?>
							<span class="text-warning"><?php echo $this->p->t('projektarbeitsbeurteilung', 'zweitbegutachterFehltWarnung') ?></span>
						<?php endif; ?>
					</div>
				</div>
				<br />
			<?php endif; ?>
			<div class="row">
				<div class="col-lg-12">
					<b><?php echo $this->p->t('lehre', 'note') ?></b>:
						<h4 id="betreuernote"></h4>
				</div>
			</div>
			<br />
			<div class="row">
				<div class="col-lg-12">
					<div class="form-group">
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'begruendungText') ?>:
						<?php $readonly = (isset($projektarbeit_bewertung->begruendung) && $sent) ? ' readonly' : '' ?>
						<textarea class="form-control" rows="5" name="begruendung"<?php echo $readonly ?>><?php echo isset($projektarbeit_bewertung->begruendung) ? $projektarbeit_bewertung->begruendung : '' ?></textarea>
					</div>
				</div>
			</div>
			</form>
				<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/savebuttons.php', array('sent' => $sent)); ?>
			<br />
			<?php endif; ?>
		</div>
	</div>
</div>
</body>
