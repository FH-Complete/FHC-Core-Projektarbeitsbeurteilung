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
					echo $kommission_vorsitz->voller_name;
				}
				// if given, display Kommissionsbetreuer (Pruefer)
				if (isset($kommission_betreuer))
				{
					foreach ($kommission_betreuer as $betr)
					{
						echo ', ' . $betr->voller_name;
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
