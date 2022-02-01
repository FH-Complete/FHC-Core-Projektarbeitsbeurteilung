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
	<?php else: ?>
		<div class="col-lg-6">
			<p>
				<button id="saveBeurteilungBtn" class="btn btn-default"><?php echo $this->p->t('ui', 'speichern') ?></button>
			</p>
		</div>
		<?php
			$zweitbetreuerBewertungMissing = isset($zweitbetreuer_person_id) && !isset($zweitbetreuer_abgeschicktamum)
												&& $projektarbeitsbeurteilung->betreuerart == Projektarbeitsbeurteilung::BETREUERART_ERSTBEGUTACHTER;
			$disabled = $zweitbetreuerBewertungMissing ? ' disabled = "disabled"' : '';
			$quickinfo = $zweitbetreuerBewertungMissing ? 'data-toggle="tooltip" title="'.$this->p->t('projektarbeitsbeurteilung', 'zweitbetreuerBewertungFehlt').'"' : '';
		?>
		<div class="col-lg-6 text-right">
			<p>
				<button id="saveSendBeurteilungBtn" class="btn btn-default"<?php echo $disabled ?><?php echo $quickinfo ?>>
					<?php echo $this->p->t('ui', 'speichernAbsenden') ?>
				</button>
			</p>
		</div>
	<?php endif; ?>
</div>