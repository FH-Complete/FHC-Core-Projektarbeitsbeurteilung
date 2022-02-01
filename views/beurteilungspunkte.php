<?php $punkteArr = array( // punkte => phrasename
	'0' => 'nichtErfuellt',
	'2.5' => 'unzureichendErfuellt',
	'5' => 'mindestanforderungErfuellt',
	'7.5' => 'inWeitenTeilenErfuellt',
	'10' => 'vollstaendigErfuellt',
);
?>
<td class="beurteilungpoints text-center">
	<?php
		if ($readOnlyAccess):
			if (isset($projektarbeit_bewertung))
			{
				$punktewert = $projektarbeit_bewertung->{'bewertung_'.$name};
				echo $language === 'German' ? formatDecimalGerman($punktewert) : $punktewert;
			}
			else
				echo '';
		else: ?>
		<span class="selectTooltip" tabindex="0" data-toggle="tooltip">
			<select name="<?php echo 'bewertung_'.$name ?>">
				<option value="null">--&nbsp;<?php echo $this->p->t('projektarbeitsbeurteilung', 'bitteBeurteilen'); ?>&nbsp;--</option>
				<?php foreach ($punkteArr as $punktewert => $phrasenname):
					$punktewertDisplay = $language === 'German' ? formatDecimalGerman($punktewert) : $punktewert;
					$selected = isset($projektarbeit_bewertung->{'bewertung_'.$name}) && $punktewert == $projektarbeit_bewertung->{'bewertung_'.$name} ? " selected" : ""?>
					<option value="<?php echo $punktewert ?>"<?php echo $selected ?>><?php echo $punktewertDisplay . ' ' . $this->p->t('projektarbeitsbeurteilung', $phrasenname) ?></option>
				<?php endforeach; ?>
			</select>
		</span>
	<?php endif; ?>
</td>
