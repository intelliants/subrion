<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=Edge">
		<title><?php echo $this->iaCore->get('site') ?> :: <?php echo $this->iaCore->get('suffix') ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="author" content="Intelliants LLC">

		<link rel="stylesheet" type="text/css" href="<?php echo $this->iaCore->get('baseurl') ?>js/bootstrap/css/bootstrap.min.css" />
	</head>
	<body>
		<div class="container" style="padding-top: 100px;">
			<h1><?php echo $this->iaCore->get('site') ?></h1>

			<div class="well">
				<?php echo $this->iaCore->get('underconstruction', 'We are sorry. Our site is under construction.') ?>
			</div>

			<div id="copyright">
				<p><?php echo iaLanguage::get('powered_by_subrion') ?> Version <?php echo IA_VERSION ?><br />
					Copyright &copy; <?php echo date('Y') ?> <a href="https://intelliants.com/" title="Software Development Company">Intelliants LLC</a></p>
			</div>
		</div>
	</body>
</html>