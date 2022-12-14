<tr>
	<td><b><?php echo $this->p->t('projektarbeitsbeurteilung', 'nameStudierende') ?></b></td>
	<td><?php echo $projektarbeitsbeurteilung->titelpre_student . ' ' . $projektarbeitsbeurteilung->vorname_student . ' ' . $projektarbeitsbeurteilung->nachname_student . ' ' . $projektarbeitsbeurteilung->titelpost_student?></td>
	<td><b><?php echo $this->p->t('projektarbeitsbeurteilung', 'personenkennzeichen') ?></b></td>
	<td><?php echo $projektarbeitsbeurteilung->personenkennzeichen_student ?></td>
</tr>
<tr>
	<td><b><?php echo $this->p->t('projektarbeitsbeurteilung', 'beurteiltVon') ?></b></td>
	<td>
		<?php
			if (isset($isKommission) && $isKommission === true)
			{
				// display Vorsitz
				if (isset($kommission_vorsitz))
				{
					echo '<b>'.$this->p->t('projektarbeitsbeurteilung', 'senatsvorsitz') . ':</b>&nbsp;' . $kommission_vorsitz->voller_name;
				}
				// if given, display Kommissionsbetreuer (Pruefer)
				if (isset($kommission_betreuer) && !isEmptyArray($kommission_betreuer))
				{
					echo '<br><b>'.$this->p->t('projektarbeitsbeurteilung', 'kommissionsmitglieder') . ':</b><br>';

					for ($i = 0; $i < count($kommission_betreuer); $i++)
					{
						if ($i != 0) echo ',&nbsp;';
						echo $kommission_betreuer[$i]->voller_name;
					}
				}
			}
			else
			{
				echo $projektarbeitsbeurteilung->titelpre_betreuer . ' ' . $projektarbeitsbeurteilung->vorname_betreuer
				. ' ' . $projektarbeitsbeurteilung->nachname_betreuer . ' ' . $projektarbeitsbeurteilung->titelpost_betreuer;
			}
		?>
	</td>
	<td><b><?php echo ucfirst($this->p->t('global', 'datum')) ?></b></td>
	<td><?php echo isset($projektarbeitsbeurteilung->abgeschicktamum) ? date("d.m.Y", strtotime($projektarbeitsbeurteilung->abgeschicktamum)): date('d.m.Y'); ?></td>
</tr>
<tr>
	<td>
		<b>
			Download
		</b>
	</td>
	<td colspan="3">
		<a
			href="ProjektarbeitsbeurteilungErstbegutachter/downloadProjektarbeit?projektarbeit_id=<?php echo $projektarbeit_id ?>"
			alt="<?php echo $this->p->t('projektarbeitsbeurteilung', 'parbeitDownload') ?>"
			title="<?php echo $this->p->t('projektarbeitsbeurteilung', 'parbeitDownload') ?>"
			target="_blank"
		>
			<i class="fa fa-file-pdf-o"></i>
		</a>
	</td>
</tr>
