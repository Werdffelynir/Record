<?php
use \record\Record as App;
/**
 * @var $list
 */
?>

<?php foreach ($list as $item) :
    $url =  $this->url().'doc/'. $item['link'];
    ?>
    <div style="border-bottom: 1px solid #FA0">
        <a href="<?= $url?>">
            <h2><?= $item['title']?></h2>
        </a>
        <p><?= App::callHook('limit',[$item['text'],15," <a href=\"{$url}\">смотреть все</a>"]);?></p>
    </div>
<?php endforeach;?>