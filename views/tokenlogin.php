<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'Projektarbeitsbeurteilung Login',
		'jquery' => true,
		'jqueryui' => true,
		'bootstrap' => true,
		'fontawesome' => true,
		'dialoglib' => true,
		'ajaxlib' => true,
		'sbadmintemplate' => true,
		'customCSSs' => array(
			'public/css/sbadmin2/admintemplate_contentonly.css'
		)
	)
);
?>
<body>
<div id="wrapper">
	<div id="page-wrapper">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header">
						<?php echo $this->p->t('projektarbeitsbeurteilung', 'beurteilung') ?>&nbsp;Login
					</h3>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<form id="tokenloginform" method="post"
						action="<?php echo base_url() ?>index.ci.php/extensions/FHC-Core-Projektarbeitsbeurteilung/Projektarbeitsbeurteilung">
						<label>Token</label>
						<div class="input-group">
							<input class="form-control" name="authtoken" value="<?php echo isset($authtoken) ? $authtoken : '' ?>">
							<span class="input-group-btn">
								<button type="submit" class="btn btn-default">
									Login
								</button>
							</span>
						</div>
					</form>
				</div>
			</div>
			<?php if (isset($authtoken)): ?>
			<br />
			<div class="row">
				<div class="col-lg-12">
					<span class="text-danger"><?php echo $this->p->t('projektarbeitsbeurteilung', 'ungueltigerToken') ?></span>
				</div>
			</div>
			<?php endif; ?>
			<br />
			<div class="row">
				<div class="col-lg-12">
					<span>
						<a href="<?php echo base_url() . 'cis/private/lehre/abgabe_lektor.php' ?>">
							<i class="fa fa-external-link"></i>
							<?php echo $this->p->t('projektarbeitsbeurteilung', 'zurProjektarbeitsUebersicht') ?>
						</a>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>
</body>