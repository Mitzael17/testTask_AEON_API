<?php

namespace core\controllers;

use core\traits\singleton;

class route_controller extends base_controller
{

    use singleton;

    private function __construct() {

        $url = rtrim($_SERVER['REQUEST_URI'], '/');

        $url = substr($url, strlen(PATH));

        if(preg_match('/\?/', $url)) {

            $url = substr($url, 0,strpos($url, '?'));

        }

        if(empty($url)) throw new \Exception('Укажите параметры!');

        $urlArray = explode('/', $url);

        $this->controller = $urlArray[0] . '_controller';

        array_shift($urlArray);

        $this->params = $urlArray;

    }

}