<?php
/**
 * @var \record\Record $this
 */
?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

    <div class="header">
        <? $this->chunk('header')?>
        <? $this->chunk('topmenu')?>
    </div>

    <div class="content">
        <? $this->output()?>
    </div>

    <div class="footer">
        <? $this->chunk('footer')?>
    </div>

</body>
</html>