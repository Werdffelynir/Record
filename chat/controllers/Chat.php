<?php

namespace controllers;


class Chat
{
    /** @var \record\Record */
    public $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function init()
    {

    }


    public function register()
    {

    }
    public function login($posts)
    {
        if(!empty($posts['user']) && !empty($posts['password'])){

            if($posts['user']=='admin' && $posts['password']=='admin'){
                $this->app->cookies('auth','admin', time()+360,'/');
                $this->app->redirect();
            }
        }
        return false;
    }
    public function logout()
    {
        $this->app->cookies('auth',null, -360,'/');
        $this->app->redirect('/login');
    }

}