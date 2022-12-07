<input type="hidden" name="projektarbeit_id" id="projektarbeit_id" value="<?php echo $projektarbeit_id; ?>">
<input type="hidden" name="paarbeittyp" id="paarbeittyp" value="<?php echo isset($paarbeittyp) ? $paarbeittyp : ''; ?>">
<input type="hidden" name="betreuerart" id="betreuerart" value="<?php echo $projektarbeitsbeurteilung->betreuerart; ?>">
<input type="hidden" name="oldbetreuernote" id="oldbetreuernote" value="<?php echo $projektarbeitsbeurteilung->betreuernote; ?>">
<input type="hidden" name="language" id="language" value="<?php echo $language; ?>">
<form id="authtokenform" method="post" onsubmit="return false;" target="">
	<input type="hidden" name="authtoken" id="authtoken" value="<?php echo $authtoken; ?>">
</form>
