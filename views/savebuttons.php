<hr>
<div class="row">
	<?php if ($readOnlyAccess): ?>
		<?php if (isset($projektarbeitsbeurteilung->abgeschicktamum)): ?>
			<div class="col-lg-12">
				<span id="gesendetText">
					<?php echo '&nbsp;&nbsp;' . ucfirst($this->p->t('global', 'gesendetAm')) . ' ' .
					date_format(date_create($projektarbeitsbeurteilung->abgeschicktamum), 'd.m.Y') ?>
				</span>
			</div>
		<?php endif; ?>
	<?php else:
		$columnSizes = $isKommission ? array(4, 4) :  array(6, 6);
		?>
		<div class="col-lg-<?php echo $columnSizes[0] ?>">
			<p>
				<button id="saveBeurteilungBtn" class="btn btn-default"><?php echo $this->p->t('ui', 'speichern') ?></button>
			</p>
		</div>
		<?php if ($isKommission):?>
		<div class="col-lg-4">
			<p>
				<button id="sendKommissionMail" class="btn btn-default"><?php echo $this->p->t('projektarbeitsbeurteilung', 'kommissionMailSenden') ?></button>
			</p>
		</div>
		<?php endif; ?>
		<div class="col-lg-<?php echo $columnSizes[1] ?> text-right">
			<p>
				<button id="saveSendBeurteilungBtn" class="btn btn-default"><?php echo $this->p->t('ui', 'speichernAbsenden') ?></button>
			</p>
		</div>
	<?php endif; ?>
</div>