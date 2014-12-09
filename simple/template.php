<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?= $this->value('tpl_title')?></title>
	<link rel="stylesheet" href="<?= $this->url()?>css/main.css">
</head>
<body>

	<div class="page full clear">
	
		<div class="header full clear">
			<div class="logo_header full">
                <?= $this->value('tpl_logo')?>
			</div>
			<div class="menu_header full">
				<a href="<?= $this->url()?>">Home</a>
				<a href="<?= $this->url()?>documentation">Documentation</a>
				<a href="<?= $this->url()?>download">Download</a>
                <?php if(AUTH):?>
                    <a href="<?= $this->url()?>messages">Messages</a>
                    <a href="<?= $this->url()?>login/out">Logout</a>
                <?php else:?>
                    <a href="<?= $this->url()?>login">Login</a>
                <?php endif;?>
			</div>
		</div>

		<div class="content full clear">
			<? $this->output()?>
		</div>

		<div class="footer grid clear">
			Copyright © - 2014 SunLight, Inc. All rights reserved. <br>
			Was compiled per: <?= $this->getTimer()?> sec. Memory used: <?= $this->getMemoryUsage()?>.
		</div>

	</div>

</body>
</html>