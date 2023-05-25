<?php

namespace core\controllers;

use core\exceptions\api_exceptions;

class login_controller extends base_controller
{

    public function request($params) {

        $this->controller = 'login_controller';
        $this->params = $params;

        $this->init();

        $method = $_SERVER['REQUEST_METHOD'];

        if(!method_exists($this, $method)) throw new api_exceptions('Метод не поддерживается');

        $result = $this->$method();

        $this->output($result);

    }

    private function post() {

        $access = $this->model->check_access();

        if(!$access) throw new api_exceptions('Превышен лимит попыток!');

        $data = $this->validation($_POST, [
            ['key' => 'name', 'slashes' => true],
            ['key' => 'password', 'slashes' => true]
        ]);

        $user = $this->model->get('users', ['name' => $data['name']]);

        if(empty($user)) throw new api_exceptions('Такого пользователя не существует!');

        $user = $user[0];

        if($data['password'] === $this->decrypt($user['password'])) {

            $this->model->delete('attempts_entry', ['ip' => $_SERVER['REMOTE_ADDR']]);

            return [
                'user_data_encrypt' => $this->encrypt(json_encode([
                    'id' => $user['id'],
                    'password' => $user['password']
                ]))
            ];

        }

        $this->model->increase_login_attempts();

        throw new api_exceptions('Неправильный пароль!');

    }

}