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
    //$records = $DB->PDO()->query("SELECT * FROM records LIMIT 6,3 ")->fetchAll();

/*SELECT t1.*, t2.email, t2.name
FROM table t1
INNER JOIN table2 t2 ON(t2.id = t1.user_id)
WHERE t2.user_id=:num'*/

    $records = $DB->select('*','records r','users u on' )
        ->fetchAll();

    $records = $DB->update('records')
        ->set('column1=:value1, column2=:value2',[':value1'=>3,':value2'=>3])
        ->where('r.id=:num',[':num'=>3])
        ->execute();

    $records = $DB->insert('records')
        ->set('column1, column2')
        ->values('value1, value2')
        ->execute();

    $records = $DB->select('*')
        ->from('records r')
        ->leftJoin('users u ON u.id = r.user_id')
        ->innerJoin('users u ON u.id = r.user_id')
        ->join('users u ON u.id = r.user_id')
        ->where('r.id=:num AND r.role!=:role',[ ':num'=>3, ':role'=>2 ])
        ->orderBy('r.id')
        ->limit(2, 1)
        ->union()
        ->groupBy()
        ->fetchAll();


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