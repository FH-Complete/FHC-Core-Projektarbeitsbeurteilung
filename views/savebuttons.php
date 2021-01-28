<hr>
<div class="row">
	<?php if ($sent): ?>
		<div class="col-lg-12">
						<span id="gesendetText">
							<?php echo '&nbsp;&nbsp;' . ucfirst($this->p->t('global', 'gesendetAm')) . ' ' . date_format(date_create($projektarbeitsbeurteilung->abgeschicktamum), 'd.m.Y') ?>
						</span>
		</div>
	<?php else: ?>
		<div class="col-lg-6">
			<p>
				<button id="saveBeurteilungBtn" class="btn btn-default"><?php echo $this->p->t('ui', 'speichern') ?></button>
			</p>
		</div>
		<div class="col-lg-6 text-right">
			<p>
				<button id="saveSendBeurteilungBtn" class="btn btn-default"><?php echo $this->p->t('ui', 'speichernAbsenden') ?></button>
			</p>
		</div>
	<?php endif; ?>
</div>