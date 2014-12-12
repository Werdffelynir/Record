<?php

require('../record/Record.php');

use \record\Record;

$conf = [
    'lang'=>'ru',
    'debug'=>true,
    'root'=>dirname(__FILE__),
    'views'=>'views',
    'layout'=>'views/layout/template',
    'title'=>'Chat Record',
    'callError404'=>'error404',
];

$app = new Record($conf);

$DB = new \classes\DB([
    'dsn'=>'sqlite:database/database.sqlite',
    'opt'=>[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC],
]);

$ctrl = new controllers\Chat($app);
$app->controller($ctrl);


$cookieAuth = $app->cookies('auth');
if(!empty($cookieAuth))
    define('AUTH',true);
else
    define('AUTH',false);


$app->value('tpl_title','Chat Record');


function error404() {
    global $app;
    $app->render('one_column',[
        'data'=>$app->partial('404')
    ]);
}

$app->map('/',function() use ($app) {
    global $DB;

    //$records = $DB->select('records','*')->fetchAll();
    $records = $DB->PDO()->query("SELECT * FROM records LIMIT 6,3 ")->fetchAll();
    $records = '<pre>'.print_r($records,true).'</pre>';

    $app->render('one_column',[
        'data'=>$records
    ]);
});

$app->map('/login/{w:out}',function($out) use ($app,$ctrl) {
    $error = '';
    $user = '';

    if((bool) $out){
        $ctrl->logout();
    }else{
        $posts = $app->post();
        if(!empty($posts)) {
            $resultLogin = $ctrl->login($posts);
            if(!$resultLogin){
                $error = 'Не существующая запись или не верный пароль!';
                $user = $app->post('user');
            }
        }
    }

    $app->render('one_column',[
        'data'=>$app->partial('site/form_login',[
            'error'=>$error,
            'user'=>$user,
        ])
    ]);
});

$app->map('/users',function() use ($app,$ctrl) {
    $userBoxs = '';

    $app->render('site/users',[
        'userInfo'=> $app->partial('site/user_info'),
        'userBox' => $app->partial('site/user_box'),
    ]);
});

$app->map('/messages',function() use ($app,$ctrl) {
    $app->render('one_column',[
        'data'=>$app->partial('site/messages'),
    ]);
});

$app->map('/profile',function() use ($app,$ctrl) {

    $app->render('one_column',[
        'data'=>$app->partial('site/profile'),
    ]);
});

$app->map('/register',function() use ($app,$ctrl) {

    $app->render('one_column',[
        'data'=>$app->partial('site/form_register'),
    ]);
});


$app->run();