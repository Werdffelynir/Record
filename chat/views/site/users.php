<?php

/**
 * $var $dataL
 * $var $dataR
 */
$userInfo = (isset($userInfo))?$userInfo:'';
$userBox = (isset($userBox))?$userBox:'';
?>
<div class="users full clear">

    <div class="grid_9 first">
        <?= $userInfo?>
    </div>

    <div class="users_side grid_3">

        <a class="btn" href="#">Добавить в друзья</a>
        <a class="btn" href="#">Написать сообщение</a>

        <?php for($i=0; $i<15;$i++):?>
            <?= $userBox?>
        <?php endfor;?>
    </div>

</div>