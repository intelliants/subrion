<?php if ($this->error): ?>
	<div class="widget widget-default">
		<div class="widget-content">
			<div class="alert alert-error">Unable to download the patch file.</div>
			<p>Could not continue.</p>
		</div>
	</div>

	<div class="form-actions">
		<a href="<?php echo URL_INSTALL ?><?php echo $this->module ?>/<?php echo $this->step ?>/" class="btn btn-default btn-lg"><i class="i-loop"></i> Try again</a>
	</div>
<?php else: ?>
	<form method="get" action="<?php echo URL_INSTALL ?><?php echo $this->module ?>/finish/">

		<div class="widget widget-default">
			<div class="widget-content">
				<div class="alert alert-info">Patch file downloaded. File size is <code><?php echo number_format($this->size / 1024) ?> Kb</code>. Click <strong>&laquo;Upgrade&raquo;</strong> button to perform the upgrade.</div>
				<h4><i class="i-cog"></i> Advanced upgrade options</a></h4>
				<ul class="list-unstyled">
					<li>
						<label for="option-force" class="checkbox"><input type="checkbox" name="mode" id="option-force" value="force" />Force file re-upload</label>
					</li>
				</ul>
			</div>
		</div>

		<div class="form-actions">
			<button type="submit" class="btn btn-lg btn-primary">Upgrade</button>
		</div>

	</form>
<?php endif ?>