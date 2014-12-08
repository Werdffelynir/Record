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

    </div>

    <div class="content">
        <? $this->output()?>
    </div>

    <div class="footer">
        <p>Page load by <?= $this->getTimer();?></p>
        <p>Memory use <?= $this->getMemoryUsage();?></p>
    </div>

</body>
</html>