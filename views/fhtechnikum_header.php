<?php
	$language = isset($language) ? $language : 'German';
	$studiengang = $language == 'English' ? $projektarbeitsbeurteilung->studiengangbezeichnung_englisch : $projektarbeitsbeurteilung->studiengangbezeichnung;

$orgform_bezeichnung = '';
	if (isset($projektarbeitsbeurteilung->orgform_bezeichnung))
		$orgform_bezeichnung = $language == 'English' ? $projektarbeitsbeurteilung->orgform_bezeichnung[1] : $projektarbeitsbeurteilung->orgform_bezeichnung[0];
?>
<div class="row">
	<div class="col-lg-12 text-right">
		<img src="<?php echo base_url() ?>/public/images/logo-300x160.png" title="FH Technikum Wien Logo" alt="FH Technikum Wien Logo" id="technikumlogo">
	</div>
</div>
<br />
<div class="row">
	<div class="col-lg-12">
		<table class="table-condensed table-bordered table-responsive">
			<tr>
				<td><b><?php echo ucfirst($this->p->t('projektarbeitsbeurteilung', 'studiengang')) ?></b></td>
				<td><?php echo $studiengang ?></td>
			</tr>
			<tr>
				<td><b><?php echo ucfirst($this->p->t('projektarbeitsbeurteilung', 'organisationsform')) ?></b></td>
				<td><?php echo $orgform_bezeichnung . ' (' . (isset($projektarbeitsbeurteilung->orgform_kurzbz) ? $projektarbeitsbeurteilung->orgform_kurzbz : '') . ')' ?></td>
			</tr>
		</table>
	</div>
</div>