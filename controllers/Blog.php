<?php

namespace controllers;

//new Blog();

class Blog {

    /** @var \record\Record */
    public $app;

    public function actions(){
        return [
            'article' => 'article',
        ];
    }

    public function __construct($app)
    {
        $this->app = $app;

        $this->app->setChunk('header','layout/header');
        $this->app->setChunk('topmenu','layout/topmenu');

        //var_dump( $this->record->args() );
        //echo __CLASS__;
    }

    public function init(){
        //echo 'nit';
    }

    public function index(){}

    public function item($id) {

        //$this->record->setChunk('header');
        //$this->record->setChunk('topmenu');

        $this->app->render('blog/item', [
            'content'=>'Blog class method '.$id
        ]);
    }

} 