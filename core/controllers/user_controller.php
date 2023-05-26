<?php

namespace core\controllers;

use core\exceptions\api_exceptions;

class user_controller extends base_controller
{

    public function request($params) {

        $this->controller = 'user_controller';
        $this->params = $params;

        $this->init();

        $method = $_SERVER['REQUEST_METHOD'];

        if(!method_exists($this, $method)) throw new api_exceptions('Метод не поддерживается');

        $result = $this->$method();

        $this->output($result);

    }

    private function get(): array {

        $result = $this->user_data;

        if(!empty($result['image'])) {

            $result['image'] = $this->create_image_link($result['image']);

        }

        $result['password'] = $this->decrypt($result['password']);

        return $result;

    }

    private function post(): array {

        $data = $this->validation($_POST, [
            ['key' => 'name', 'default' => '', 'slashes' => true],
            ['key' => 'password', 'default' => '', 'slashes' => true],
            ['key' => 'date_birthday', 'default' => ''],
        ]);

        if(!empty($data['name'])) {

            $user = $this->model->get('users', ['name' => $data['name']]);

            if(isset($user[0]) && $user[0]['id'] !== $this->user_data['id']) throw new api_exceptions('Такой пользователь уже существует!');

        }

        foreach ($data as $key => $value) {

            if(empty($value)) {

                unset($data[$key]);
                continue;

            }

            if($key === 'password') {

                $data['password'] = $this->encrypt($data['password']);

                $this->user_data['password'] = $data['password'];

            }

        }

        if(isset($_FILES['image'])) {

            if(!preg_match('/^image/', $_FILES['image']['type'])) throw new api_exceptions('Формат файла не поддерживается!');

            if(!empty($this->user_data['image'])) $this->delete_image($this->user_data['image']);

            $data['image'] = addslashes($this->upload_image($_FILES['image']));

        }

        if(empty($data)) throw new api_exceptions('Передайте параметры!');

        $this->model->update('users', $data, ['id' => $this->user_data['id']]);

        if(!empty($data['image'])) $data['image'] = $this->create_image_link($data['image']);

        return [
            'user_data_encrypt' => $this->encrypt(json_encode([
                'id' => $this->user_data['id'],
                'password' => $this->user_data['password']
            ]))
        ];

    }

}