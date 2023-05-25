<?php

namespace core\controllers;

use core\exceptions\api_exceptions;

class registration_controller extends base_controller
{

    public function request($params) {

        $this->controller = 'registration_controller';
        $this->params = $params;

        $this->init();

        $method = $_SERVER['REQUEST_METHOD'];

        if(!method_exists($this, $method)) throw new api_exceptions('Метод не поддерживается');

        $result = $this->$method();

        $this->output($result);

    }

    private function post(): array {

        $data = $this->validation($_POST, [
           ['key' => 'name', 'slashes' => true],
           ['key' => 'password', 'slashes' => true]
        ]);

        $user = $this->model->get('users', ['name' => $data['name']]);

        if(!empty($user)) {

            throw new api_exceptions('Такой пользователь уже существует!');

        }

        $data['password'] = $this->encrypt($data['password']);

        $id = $this->model->insert('users', $data);

        return [
            'user_data_encrypt' => $this->encrypt(json_encode([
                'id' => $id,
                'password' => $data['password']
            ]))
        ];

    }

}