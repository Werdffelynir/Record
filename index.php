<?php

require('record/Record.php');

use \record\Record;

$conf = [
    'lang'=>'ru',
    'debug'=>true,

    'path'=>'/record/',

    'views'=>'/views/',
    'models'=>'/models/',
    'controllers'=>'/controllers/',

    'layout'=>'views/layout/template',

    'title'=>'Web Application Record',

    //'messageError404'=>'pageError404 Web Application Record',
    'callError404'=>'showPageError404',
];

$app = new Record($conf);
/*
$app->map('/', function() {
    echo "<p>main page!</p>";
});

$app->map('/blog/item/{n:id}', function($id) use ($app) {

    $controller = new \controllers\Blog($app);
    $app->controller($controller);
    $controller->item($id);

    //$app->controller('Blog')->item($id);

    //var_dump($app->args);
    //var_dump(Record::arg('id'));
    //echo "<p>blog page! ID: $id</p>";
    //$app->layout = 'www/layout';
    //$app->render('',[]);
    //$partial = $app->partial('blog/item',[]);
    //var_dump($partial);

});

$app->map('/page/{!p:cat}/{n:id}', function($cat,$id) {
    echo "<p>page page!</p>";
    var_dump($cat,$id);
});
*/

$list = [
    [
        'link'=>'install_app',
        'title'=>'Установка приложения',
        'text'=>'Это простой и легкий Класс на 500 строк с комментариями. Framework Ractor хорошо подходит для небольших веб приложений.
        Если передан массив replacement в качестве аргумента, тогда удалённые элементы будут заменены элементами этого массива.',
    ],
    [
        'link'=>'routes_and_controllers',
        'title'=>'Адрисация и контролеры',
        'text'=>'Если параметр length опущен, будут удалены все элементы начиная с позиции offset и до конца массива.
        Если length указан и он положителен, то будет удалено именно столько элементов. Если же параметр length
        отрицателен, то конец удаляемой части элементов будет отстоять на это количество от конца массива.
        Совет: для того, чтобы удалить все элементы массива, начиная с позиции offset до конца массива, в то время
        как указан параметр replacement, используйте count($input) в качестве параметра length.',
    ],
    [
        'link'=>'database',
        'title'=>'Даза даннтых и виды',
        'text'=>'Совет: если replacement является просто одним элементом, нет необходимости заключать его в array(),
        если только этот элемент сам не является массивом, объектом или NULL.',
    ],
];

$app->layout = 'views/layout/template';

$app->setChunk('header','layout/header');
$app->setChunk('topmenu','layout/topmenu');
$app->setChunk('footer','layout/footer');

//error 404
function showPageError404() {
    $app = Record::instance();
    echo $app->partial('404');
    exit;
}

function showUserIp($str) {
    return " $str ".Record::ip();
}
function limitStr($str,$limit=10,$end='...') {
    $_str = explode(" ",$str);
    array_splice($_str, $limit);
    $str = join(" ",$_str);
    return $str.$end;
}
Record::addHook('showIp','showUserIp');
Record::addHook('limit','limitStr');

$app->before(function(){
    //echo 'before';
});
$app->after(function(){
   //echo 'after';
});

$app->map('/', function() use ($app) {

    $userIp = Record::callHook('showIp',['str'=>'User IP:']);

    $app->render('main',[
        'title'=>'Home Page',
        'content'=>'Home Page Content'.$userIp,
    ]);
});

$app->map('/docs', function() use ($app) {

    global $list;

    $app->render('main',[
        'title'=>'Documentation Page',
        'content'=>$app->partial('list',['list'=>$list]),
    ]);
});
$app->map('/doc/{p:link}', function($link) use ($app) {
    global $list;

    $data = null;

    foreach($list as $i){
        if($i['link']==$link){
            $data = $i;
            continue;
        }
    }

    //if($data == null)
    //    Record::apply('showPageError404');

    $app->render('main',[
        'title'=>'Documentation Page',
        'content'=>$app->partial('doc', ['data'=>$data]),
    ]);
});

$app->map('/download', function() use ($app) {
    $app->render('main',[
        'title'=>'Download Page',
        'content'=>'Download Page Content',
    ]);
});

$app->map('/bugs', function() use ($app) {
    $app->layout = 'views/layout/template';
    $app->setChunk('header','layout/header');

    $app->render('main',[
        'title'=>'Bug Report Page',
        'content'=>'Bug report content',
    ]);
});

$app->map('/link/{p:link}/{p:id}', function() use ($app) {
    echo 'test map';
});

$app->map('/contact/{p:type}', function($type) use ($app) {
    echo 'test map';
});


$app->run();