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
				<div class="alert alert-info">Patch file downloaded.</div>
				<p>Patch size is <code><?php echo number_format($this->size / 1024) ?> Kb</code>.</p>
				<p>Click <strong>&laquo;Upgrade&raquo;</strong> button to perform the upgrade.</p>
				<p><a href="#" id="js-open-details"><i class="i-cog"></i> Advanced options</a>.</p>
				<div class="span3" id="js-advanced-section">
					<label for="option-force">
						<input type="checkbox" name="mode" id="option-force" value="force" /> 
						<span class="label label-info">force file re-upload</span>
					</label>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		$(function()
		{
			$('#js-open-details').click(function(e)
			{
				e.preventDefault();
				$('#js-advanced-section').slideToggle();
			});
		});
		</script>

		<div class="form-actions">
			<button type="submit" class="btn btn-lg btn-primary">Upgrade</button>
		</div>

	</form>
<?php endif ?>