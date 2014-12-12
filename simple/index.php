<?php

require('../record/Record.php');

use \record\Record;

$conf = [
    'debug'=>true,
    'lang'=>'ru',
    //'path'=>'/record/simple/',
    'layout'=>'template',
    'title'=>'Web Application Record',
    'callError404'=>'error404',
];


$app = new Record($conf);


$cookieAuth = $app->cookies('auth');
if(!empty($cookieAuth))
    define('AUTH',true);
else
    define('AUTH',false);


$app->value('tpl_title','Web Application Added');
$app->value('tpl_logo','Simple application');


function error404(){
    global $app;
    $app->render('one_column',[
        'data'=>$app->partial('views/404')
    ]);
}


$app->map('/', function() use ($app) {
    $app->render('two_column',[
        'dataL'=>$app->partial('views/page'),
        'dataR'=>$app->partial('views/sidebar'),
    ]);
});


$app->map('/documentation/{p:item}', function($item) use ($app) {
    
    $app->render('one_column',[
        'data'=>$app->partial('views/documentation'),
    ]);
});


$app->map('/download', function() use ($app) {
    $app->render('one_column',[
        'data'=>$app->partial('views/page'),
    ]);
});


$app->map('/messages', function() use ($app) {
    //$app->cookies('test_record','cookie data records');
    $app->render('one_column',[
        'data'=>$app->partial('views/messages'),
    ]);
});


$app->map('/login/{w:out}', function($out) use ($app)
{
    # logout
    if($out=='out'){
        $app->cookies('auth',null,-1,'/');
        $app->redirect('login');
    }

    $user = '';
    $error = '';

    # login
    $postUser = $app->post('user');
    $postPass = $app->post('password');

    $userFromDb = $passFromDb ='admin';
    if(!empty($postUser) && !empty($postPass)){
        if($postUser==$userFromDb && $postPass==$passFromDb){
            $app->cookies('auth',$userFromDb,0,'/');
            $app->redirect();
        }else{
            $user = $postUser;
            $error = 'Не верный логин или пароль!';
        }
    }

    $app->render('views/login',[
        'error'=>$error,
        'user'=>$user,
    ]);
});

$app->run();