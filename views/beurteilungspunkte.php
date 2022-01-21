<?php $punkteArr = array( // punkte => phrasename
	'0' => 'unzureichendErfuellt',
	'5' => 'genuegendErfuellt',
	'8' => 'gutErfuellt',
	'10' => 'sehrGutErfuellt',
);
?>
<td class="beurteilungpoints text-center">
	<?php
		if ($readOnlyAccess):
			echo isset($projektarbeit_bewertung) ? $projektarbeit_bewertung->{'bewertung_'.$name} : '';
		else: ?>
		<select name="<?php echo 'bewertung_'.$name ?>">
			<option value="null">--&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'bitteBeurteilen'); ?>&nbsp;--</option>
			<?php foreach ($punkteArr as $punktewert => $phrasenname):
				$selected = isset($projektarbeit_bewertung->{'bewertung_'.$name}) && $punktewert == $projektarbeit_bewertung->{'bewertung_'.$name} ? " selected" : ""?>
				<option value="<?php echo $punktewert ?>"<?php echo $selected ?>><?php echo $punktewert . ' ' . $this->p->t('projektarbeitsbeurteilung', $phrasenname) ?></option>
			<?php endforeach; ?>
		</select>
	<?php endif; ?>
</td>
