<?php

namespace core\controllers;

use core\exceptions\api_exceptions;
use core\models\model;

abstract class base_controller
{

    protected array $user_data;

    protected string $controller;
    protected array $params;

    protected \core\models\model $model;

    private static string $crypt_method = 'AES-128-CBC';
    private static string $hash_algoritm = 'sha256';
    private static int $hash_length = 32;

    public function route() {

        $controller_path = "\\core\\controllers\\$this->controller";

        try {

            $object = new \ReflectionMethod($controller_path, 'request');

            $object->invoke(new $controller_path, $this->params);

        }
        catch (\ReflectionException $e) {

            throw new \Exception($e->getMessage());

        }

    }

    protected function init() {

        if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(json_encode(['status' => 'success']));

        $this->model = model::instance();

        if($this->controller === 'login_controller' || $this->controller === 'registration_controller') return;

        if(!isset($_SERVER['HTTP_AUTHORIZATION'])) throw new api_exceptions('Ошибка авторизации!', 403);

        $user_data = json_decode($this->decrypt($_SERVER['HTTP_AUTHORIZATION']), true);

        $this->user_data = $this->model->get('users', ['id' => $user_data['id'], 'password' => $user_data['password']]);

        if(isset($this->user_data[0])) {

            $this->user_data = $this->user_data[0];

            return;

        };

        throw new api_exceptions('Ошибка авторизации!', 403);

    }

    protected function output(array|string $data) {

        exit(json_encode($data));

    }

    protected function validation(array $data, array $values): array {

        $result = [];

        $not_valid_values = '';

        foreach ($values as $value) {

            if(!isset($data[$value['key']])) {

                if(!isset($value['default'])) $not_valid_values .= $value['key'] . ', ';
                else $result[$value['key']] = isset($value['slashes']) && $value['slashes']  ? addslashes($value['default']) : $value['default'];

            } else {

                $result[$value['key']] = isset($value['slashes']) && $value['slashes'] ? addslashes($data[$value['key']]) : $data[$value['key']];

            }

        }

        if(!empty($not_valid_values)) {

            $not_valid_values = rtrim($not_valid_values, ', ');

            throw new api_exceptions("Отсутствуют следующие значения: $not_valid_values");

        }

        return $result;

    }

    protected function encrypt(string $str): string {

        $ivlen = openssl_cipher_iv_length(self::$crypt_method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($str, self::$crypt_method, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac(self::$hash_algoritm, $ciphertext_raw, CRYPT_KEY, true);
        $ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );

        return $ciphertext;

    }

    protected function decrypt(string $str): string|bool {

        $str = base64_decode($str);

        $ivlen = openssl_cipher_iv_length(self::$crypt_method);
        $iv = substr($str, 0, $ivlen);
        $hmac = substr($str, $ivlen, self::$hash_length);
        $ciphertext_raw = substr($str, $ivlen+self::$hash_length);
        $original_plaintext = openssl_decrypt($ciphertext_raw, self::$crypt_method, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac(self::$hash_algoritm, $ciphertext_raw, CRYPT_KEY, true);

        if (hash_equals($hmac, $calcmac)) {
            return $original_plaintext;
        } else {
            return false;
        }

    }

    protected function create_image_link(string $image): string {

        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . PATH . UPLOAD_DIR . $image;

    }

    protected function upload_image(array $image_data): string {

        $filename = $this->get_unique_filename($image_data['name']);

        move_uploaded_file($image_data['tmp_name'], UPLOAD_DIR . $filename);

        return $filename;

    }

    protected function delete_image(string $image) {

        if(file_exists(UPLOAD_DIR . $image)) {

            unlink(UPLOAD_DIR . $image);

        }

    }

    protected function get_unique_filename(string $filename): string {

        if(!file_exists(UPLOAD_DIR . $filename)) {

            return $filename;

        }

        return $this->get_unique_filename(hash('crc32', time() . rand(0, 100)) . "_$filename");

    }

}