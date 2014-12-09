<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?= $this->value('tpl_title')?></title>
	<link rel="stylesheet" href="<?= $this->url()?>public/css/main.css">
</head>
<body>

	<div class="page full clear">
	
		<div class="header full clear">
			<div class="logo_header grid_4 first">
                <a href="/"><?= $this->value('tpl_title')?></a>
			</div>
			<div class="menu_header grid_8">
				<a href="<?= $this->url()?>">Home</a>
				<a href="<?= $this->url()?>users">Учасники</a>
                <?php if(AUTH):?>
                    <a href="<?= $this->url()?>messages">Сообщения</a>
                    <a href="<?= $this->url()?>profile">Профиль</a>
                    <a href="<?= $this->url()?>login/out">Выйти</a>
                <?php else:?>
                    <a href="<?= $this->url()?>register">Ргистрация</a>
                    <a href="<?= $this->url()?>login">Войти</a>
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