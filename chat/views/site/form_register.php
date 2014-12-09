<?php
/**
 * @var $user
 * @var $error
 */
$error = (isset($error))?$error:'';
$user = (isset($user))?$user:'';
?>
<div class="login_box">

    <h2>Регистрация</h2>

    <?php if(!empty($error)):?>
        <div class="login_error">
            <?= $error?>
        </div>
    <?php endif;?>

    <form action="<?=$this->url()?>login" method="post">
        <input name="user" type="text" value="<?= $user?>" />
        <input name="password" type="password" value="" />
        <input type="submit" value="Login"/>
    </form>

</div>