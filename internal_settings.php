<?php

defined('ACCESS') or die('Access denied');

const PATH = '/';
const UPLOAD_DIR = 'uploads/';

const CRYPT_KEY = 'H@McQfTjWnZr4u7x?E(H+MbQeThWmZq4y/A?D(G+KbPeShVm5u8x/A%D*G-KaPdSnZr4u7x!z%C*F-JaThWmZq4t7w!z$C&FbPeShVmYq3t6w9z$G-KaPdSgVkYp3s6v';

const LIMIT_ATTEMPTS = 3;
const DELAY_ATTEMPTS = 3600;


function autoload($class_name) {

    $class_name = str_replace('\\', '/', $class_name);

    if(!@include_once $class_name . '.php') {

        throw new Exception('Ошибка в загрузке класса!');

    }

}

spl_autoload_register('autoload');