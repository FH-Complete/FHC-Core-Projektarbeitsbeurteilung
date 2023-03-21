<?php
	// language the page should be displayed in
	$language = isset($language) ? $language : 'German';
	// all languages the page can be displayed in, 'languageValue' => 'languageName'
	$languages = array(
		'German' => 'Deutsch',
		'English' => 'English',
	);
	$studiengang = $language == 'English' ? $projektarbeitsbeurteilung->studiengangbezeichnung_englisch : $projektarbeitsbeurteilung->studiengangbezeichnung;

$orgform_bezeichnung = '';
	if (isset($projektarbeitsbeurteilung->orgform_bezeichnung))
		$orgform_bezeichnung = $language == 'English' ? $projektarbeitsbeurteilung->orgform_bezeichnung[1] : $projektarbeitsbeurteilung->orgform_bezeichnung[0];
?>
<div class="row">
	<div class="col-lg-6 form-inline">
		<label><?php echo ucfirst($this->p->t('projektarbeitsbeurteilung', 'sprache')); ?>&nbsp;</label>
		<select class="form-control" id="lang">
			<?php
				foreach($languages as $lang => $langName):
					$selected = $lang == $language ? ' selected' : '';
			?>
				<option value="<?php echo $lang ?>"<?php echo $selected ?>><?php echo $langName; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="col-lg-6 text-right">
		<img src="<?php echo base_url() ?>/<?php echo $logoPath ?>" title="FH Technikum Wien Logo" alt="FH Technikum Wien Logo" id="technikumlogo">
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
