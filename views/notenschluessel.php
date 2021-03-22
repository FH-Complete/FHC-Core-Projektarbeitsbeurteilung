<div class="row">
	<div class="col-lg-12">
		<?php echo ucfirst($this->p->t('lehre', 'notenschluessel')) ?>:
		<table class="table-condensed table-bordered table-responsive">
			<tr>
				<td>
					<50%&nbsp;<b><?php echo $this->p->t('lehre', 'nichtGenuegend') ?></b>
				</td>
				<td>
					>=50% <?php echo $this->p->t('global', 'und') ?> <63%&nbsp;<b><?php echo $this->p->t('lehre', 'genuegend') ?></b>
				</td>
				<td>
					>=63% <?php echo $this->p->t('global', 'und') ?> <75%&nbsp;<b><?php echo $this->p->t('lehre', 'befriedigend') ?></b>
				</td>
				<td>
					>=75% <?php echo $this->p->t('global', 'und') ?> <88%&nbsp;<b><?php echo $this->p->t('lehre', 'gut') ?></b>
				</td>
				<td>
					>=88%&nbsp;<b><?php echo $this->p->t('lehre', 'sehrGut') ?></b>
				</td>
			</tr>
			<tr>
				<td colspan="5">
					<?php
						$notenschluesselHinweis = $this->p->t('projektarbeitsbeurteilung', 'notenschluesselHinweis');
						echo sprintf($notenschluesselHinweis, $arbeittypName);
					?>
				</td>
			</tr>
		</table>
	</div>
</div>