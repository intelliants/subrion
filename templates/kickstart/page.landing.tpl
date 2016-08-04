<div class="section">
	<div class="container">
		<div class="row">
			<div class="col-md-4">
				<div class="b-feature">
					<img src="{$img}dummy/icon-box.png" alt="" class="img-responsive center-block">
					<h3>Some feature name</h3>
					<p>Lorem ipsum dolor sit amet, consectetur adipisicing. Quam quas vel a alias at, facilis aspernatur dolores aperiam.</p>
				</div>
			</div>
			<div class="col-md-4">
				<div class="b-feature">
					<img src="{$img}dummy/icon-share.png" alt="" class="img-responsive center-block">
					<h3>Some feature name</h3>
					<p>Lorem ipsum dolor sit amet, consectetur adipisicing. Quam quas vel a alias at, facilis aspernatur dolores aperiam.</p>
				</div>
			</div>
			<div class="col-md-4">
				<div class="b-feature">
					<img src="{$img}dummy/icon-mobile.png" alt="" class="img-responsive center-block">
					<h3>Some feature name</h3>
					<p>Lorem ipsum dolor sit amet, consectetur adipisicing. Quam quas vel a alias at, facilis aspernatur dolores aperiam.</p>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="section call-to-action">
	<div class="container">
		<div class="row">
			<div class="col-md-7">
				<h2>Join our community</h2>
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Suscipit sint ad aliquam enim, libero praesentium distinctio labore eum sit quod totam mollitia omnis. Illo dolore itaque fugit hic minus cumque!</p>
				<p>Illo dolore itaque fugit hic minus cumque!</p>
				<p><a href="{$smarty.const.IA_URL}registration/" class="btn btn-warning-outline btn-lg">Sign up</a> <a href="{$smarty.const.IA_URL}login/" class="btn btn-warning-outline btn-lg">Log in</a></p>
			</div>
			<div class="col-md-5">
				<img src="{$img}dummy/mountains.jpg" alt="" class="img-responsive img-rounded">
			</div>
		</div>
	</div>
</div>

<div class="section call-to-action call-to-action--center">
	<div class="container">
		<h2>Mobile ready from the ground up.</h2>
		<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Suscipit sint ad aliquam enim, libero praesentium distinctio labore eum sit quod totam mollitia omnis.</p>
		<img src="{$img}dummy/phone.jpg" alt="" class="img-responsive img-rounded center-block">
	</div>
</div>

{if isset($iaBlocks.verybottom)}
	<div class="section section-blog">
		<div class="container">
			<h2>What we are up to</h2>
			{ia_blocks block='verybottom'}
		</div>
	</div>
{/if}