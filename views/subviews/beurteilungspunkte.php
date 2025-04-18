<td class="beurteilungpoints text-center" id="<?php echo $name ?>">
	<?php
		if ($readOnlyAccess):
			if (isset($projektarbeit_bewertung->{$name}))
			{
				echo "<span data-points='".$projektarbeit_bewertung->{$name}."' class='readOnlyPoints'>";
				echo $projektarbeit_bewertung->{$name};
				echo "</span>%";
			}
			else
				echo '';
		else: ?>
		<span class="selectTooltip" data-toggle="tooltip">
			<div class="input-group">
				<input
					type="text"
					name="<?php echo $name ?>"
					class="form-control pointsInput"
					value="<?php echo $projektarbeit_bewertung->{$name} ?? '' ?>"
					aria-label="<?php echo $this->p->t('projektarbeitsbeurteilung', 'eingabefeld').' '.$name ?>"  />
				<div class="input-group-addon">%</div>
			</div>
		</span>
	<?php endif; ?>
</td>
