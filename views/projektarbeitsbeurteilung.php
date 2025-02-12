<?php
	$includesArray = array(
		'title' => 'Projektarbeitsbeurteilung',
		'jquery3' => true,
		'jqueryui1' => true,
		'bootstrap3' => true,
		'fontawesome4' => true,
		'dialoglib' => true,
		'ajaxlib' => true,
		'sbadmintemplate3' => true,
		'phrases' => array(
			'projektarbeitsbeurteilung',
			'lehre'
		),
		'customCSSs' => array(
			'public/css/sbadmin2/admintemplate_contentonly.css',
			'public/extensions/FHC-Core-Projektarbeitsbeurteilung/css/projektarbeitsbeurteilung.css'
		),
		'customJSs' => array(
			'public/extensions/FHC-Core-Projektarbeitsbeurteilung/js/projektarbeitsbeurteilung_lib.js',
			'public/extensions/FHC-Core-Projektarbeitsbeurteilung/js/projektarbeitsbeurteilung.js'
		)
	);
	$this->load->view('templates/FHC-Header', $includesArray);
?>
<div id="wrapper">
	<div id="page-wrapper">
		<div class="container-fluid" id="containerFluid">
			<?php
			if (!isset($projektarbeitsbeurteilung)):
					echo "Keine Projektarbeit eingetragen.";
			else:
				$paarbeittyp = $projektarbeitsbeurteilung->parbeit_typ === 'Bachelor' ? 'b' : 'm';
				$arbeittypName = $paarbeittyp === 'b' ? $this->p->t('projektarbeitsbeurteilung', 'arbeitBachelor') : $this->p->t('abschlusspruefung', 'arbeitMaster');

				$titel = isset($projektarbeitsbeurteilung->projektarbeit_titel) ? $projektarbeitsbeurteilung->projektarbeit_titel : $projektarbeitsbeurteilung->projektarbeit_titel_english;
				$projektarbeit_bewertung = $projektarbeitsbeurteilung->projektarbeit_bewertung;
				$plagiatscheck_unauffaellig = isset($projektarbeit_bewertung->plagiatscheck_unauffaellig) && $projektarbeit_bewertung->plagiatscheck_unauffaellig === true ? $projektarbeit_bewertung->plagiatscheck_unauffaellig : false;
				?>
			<br />
			<br />
				<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/subviews/header.php', array()); ?>
			<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header">
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'beurteilung') ?>
						<?php echo $arbeittypName . ($paarbeittyp === 'm' ? '&nbsp-&nbsp' . $this->p->t('projektarbeitsbeurteilung', 'erstBegutachter') : '') ?>
					</h3>
				</div>
			</div>
				<?php
					$this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/subviews/hiddenfields.php', array('paarbeittyp' => $paarbeittyp));
				?>
			<div class="row">
				<div class="col-lg-12">
					<table class="table-condensed table-bordered table-responsive">
						<tr>
							<td class="tableWidthThirty">
								<b>
									<?php echo ucfirst($this->p->t('projektarbeitsbeurteilung', 'titelDerArbeit')) . ' ' . $arbeittypName ?>
								</b>
							</td>
							<td colspan="3">
								<?php if ($readOnlyAccess): ?>
									<?php echo $titel ?>
								<?php else: ?>
									<span id="titleField"> <!-- filled by js -->
										<?php echo $titel ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td class="tableWidthThirty">
								<b>
									<?php echo $paarbeittyp === 'm' ? $this->p->t('projektarbeitsbeurteilung', 'plagiatscheckBeschreibungMaster')
										: $this->p->t('projektarbeitsbeurteilung', 'plagiatscheckBeschreibung') ?>
								</b>
							</td>
							<td colspan="3">
								<?php if ($readOnlyAccess): ?>
									<?php echo $plagiatscheck_unauffaellig ? ucfirst($this->p->t('ui', 'ja')) : ucfirst($this->p->t('ui', 'nein')) ?>
								<?php else: ?>
									<input type="checkbox" form="beurteilungform" name="plagiatscheck_unauffaellig" id="plagiatscheck_unauffaellig" value="true"<?php echo $plagiatscheck_unauffaellig === true ? ' checked="checked"' : ''?>>
									&nbsp;<span class="text-warning noDisplay" id="plagiatscheckHinweisNegativ"><?php echo $this->p->t('projektarbeitsbeurteilung', 'plagiatscheckHinweisNegativeBeurteilung') ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/subviews/stammdaten.php'); ?>
					</table>
				</div>
			</div>
			<br />
			<form id="beurteilungform" onsubmit="return false;">
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
								<th class="text-center">
									<?php echo $this->p->t('projektarbeitsbeurteilung', 'maxPunkte') ?>
								</th>
								<th>
									Details
								</th>
								<th class="text-center">
									<b>
										<?php echo $this->p->t('projektarbeitsbeurteilung', 'bewertung') ?>
									</b>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php $counter = 1;?>
							<?php foreach ($pointFields as $pointFieldName => $pointField): ?>
							<tr>
								<td>
									<b>
										&nbsp;<?php echo $counter.'. '.$this->p->t('projektarbeitsbeurteilung', $pointField['phrase']) ?>
									</b>
								</td>
								<td id ="gewichtung_<?php echo $pointFieldName ?>" class="text-center">
									&nbsp;
								</td>
								<td>
									<?php echo $this->p->t('projektarbeitsbeurteilung', $pointField['phrase'].'Text') ?>
								</td>
								<?php
									$this->load->view(
										'extensions/FHC-Core-Projektarbeitsbeurteilung/subviews/beurteilungspunkte.php',
										array('name' => $pointFieldName, 'projektarbeit_bewertung' => $projektarbeit_bewertung)
									);
								?>
							</tr>
							<?php $counter++; ?>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan="3" class="text-right">
									<b>
										<?php
											if ($paarbeittyp === 'm')
											{
												echo ucfirst($this->p->t('projektarbeitsbeurteilung', 'gewichtet')).'&nbsp;';
												echo $this->p->t('projektarbeitsbeurteilung', 'gesamtpunkte');
											}
											else
												echo ucfirst($this->p->t('projektarbeitsbeurteilung', 'gesamtpunkte'));
										?>
									</b>
								</td>
								<td class="text-center">
									<b>
									<span id="gesamtpunkte">
										<?php echo isset($projektarbeit_bewertung->gesamtpunkte) ? $projektarbeit_bewertung->gesamtpunkte : '' ?></span>/<span id="maxpunkte">100</span>
									</b>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
			<br />
				<?php $this->load->view(
					'extensions/FHC-Core-Projektarbeitsbeurteilung/subviews/notenschluessel.php',
					array(
						'paarbeittyp' => $paarbeittyp,
						'arbeittypName' => $arbeittypName
					)
				); ?>
			<br />
			<?php if ($paarbeittyp === 'm'): ?>
				<div class="row">
					<div class="col-lg-12">
						<?php if (isset($zweitbetreuer_person_id)): ?>
							<?php if (isset($zweitbetreuer_abgeschicktamum)): ?>
								<?php echo $this->p->t('projektarbeitsbeurteilung', 'gutachtenZweitBegutachtung') ?>
								<br />
								<a href="<?php echo site_url() . '/extensions/FHC-Core-Projektarbeitsbeurteilung/ProjektarbeitsbeurteilungZweitbegutachter?projektarbeit_id=' . $projektarbeit_id . '&uid=' . $student_uid . '&zweitbetreuer_id=' . $zweitbetreuer_person_id ?>" target="_blank">
									<i class="fa fa-external-link"></i>&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'zurZweitbegutachterBewertung') ?>
								</a>
							<?php else: ?>
								<span class="text-warning"><?php echo $this->p->t('projektarbeitsbeurteilung', 'zweitbegutachterFehltWarnung') ?></span>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
				<br />
			<?php endif; ?>
			<div class="row">
				<div class="col-lg-12">
					<b><?php echo $this->p->t('lehre', 'note') ?></b>:
						<h4 id="betreuernote"><?php echo isset($projektarbeitsbeurteilung->betreuernote) ? $projektarbeitsbeurteilung->betreuernote : '' ?></h4>
				</div>
			</div>
			<br />
			<div class="row">
				<div class="col-lg-12">
					<div class="form-group">
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'begruendungVerpflichtend') ?>:
						<?php $readonly = $readOnlyAccess ? ' readonly' : '' ?>
						<textarea class="form-control" rows="5" name="begruendung"<?php echo $readonly ?>><?php echo isset($projektarbeit_bewertung->begruendung) ? $projektarbeit_bewertung->begruendung : '' ?></textarea>
					</div>
				</div>
			</div>
			</form>
			<?php if ($isKommission): ?>
			<div class="row">
				<div class="col-lg-12">
					<div class="alert alert-warning">
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'kommissionellePruefungHinweis') ?>
						<br>
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'senatsvorsitz') ?>:
						<?php
							$vsStr = '';
							$vsStr .= $kommission_vorsitz->voller_name;
							$vsStr .= '&nbsp;<a href="mailto:'.$kommission_vorsitz->univEmail.'" title="'.$kommission_vorsitz->univEmail.'"><i class="fa fa-envelope text-warning"></i></a>';
							echo $vsStr;
						?>
						<br>
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'kommissionsmitglieder') ?>:
						<?php
							$kbStr = '';
							$first = true;
							foreach ($kommission_betreuer as $kb)
							{
								if (!$first)
									$kbStr .= ', ';
								$kbStr .= $kb->voller_name;
								$kbStr .= '&nbsp;<a href="mailto:'.$kb->zustellung_mail.'" title="'.$kb->zustellung_mail.'"><i class="fa fa-envelope text-warning"></i></a>';
								$first = false;
							}
							echo $kbStr;
						?>
						<?php if (!$readOnlyAccess): ?>
						<br>
						<div class="text-center">
							<button id="sendKommissionMail" class="btn btn-warning text-center">
								<?php echo $this->p->t('projektarbeitsbeurteilung', 'kommissionMailSenden') ?>
							</button>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>
				<?php $this->load->view('extensions/FHC-Core-Projektarbeitsbeurteilung/subviews/footer.php'); ?>
			<br />
			<?php endif; ?>
		</div>
	</div>
</div>
<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>
