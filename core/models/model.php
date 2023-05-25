<?php

namespace core\models;

use core\traits\singleton;

class model
{

    protected \mysqli $db;

    use singleton;

    private function connect() {

        $this->db = new \mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if($this->db->connect_error) {
            throw new \Error('Ошибка подключения к базе данных!');
        }

        $tables = $this->show_tables();

        $this->init_tables($tables);

    }

    protected function show_tables(): array {

        $tables = $this->query('SHOW TABLES');

        if(empty($tables)) return [];

        $key = '';

        foreach ($tables[0] as $database => $value) {

            $key = $database;

            break;

        }

        $result = [];

        foreach ($tables as $table) $result[] = $table[$key];

        return $result;

    }

    private function init_tables(array $tables) {

        $queries = [
            'users' => 'CREATE TABLE users (
                id int PRIMARY KEY AUTO_INCREMENT NOT NULL,
                name VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                date_birthday date,
                image VARCHAR(1000)
            )',
            'attempts_entry' => 'CREATE TABLE attempts_entry (
                id int PRIMARY KEY AUTO_INCREMENT NOT NULL,
                ip VARCHAR(32) UNIQUE NOT NULL,
                attempts tinyint NOT NULL,
                timestamp bigint NOT NULL
            )',
        ];

        foreach ($queries as $table => $query) {

            if(in_array($table, $tables)) continue;

            $this->query($query, 'c');

        }

    }

    private function query(string $query, string $flag = 'r', bool $return_id = false) {

        $result = $this->db->query($query);

        if($this->db->affected_rows === -1) {

            throw new \Exception('Ошибка в SQL запросе' . $query . '-' . $this->db->errno . ' ' . $this->db->error);

        }

        switch ($flag) {

            case 'r':

                return $result->fetch_all(MYSQLI_ASSOC);

            case 'i':

                if($return_id) return $this->db->insert_id;

                return true;

            default:

                return true;

        }

    }

    public function insert(string $table, array $data) {

        $fields = '(';
        $values = '(';

        foreach ($data as $field => $value) {

            $fields .= "$field, ";
            $values .= "'$value', ";

        }

        $fields = rtrim($fields, ', ') . ')';
        $values = rtrim($values, ', ') . ')';

        return $this->query("INSERT INTO $table $fields VALUES $values", 'i', true);

    }

    private function create_where(array $conditions): string {

        $where = 'WHERE ';

        foreach ($conditions as $key => $value) {

            $where .= "$key='$value' AND ";

        }

        return rtrim($where, 'AND ');

    }

    public function get(string $table, array $conditions = []): array {

        $where = '';

        if(!empty($conditions)) $where = $this->create_where($conditions);

        return $this->query("SELECT * FROM $table $where");

    }

    public function increase_login_attempts() {

        $ip = $_SERVER['REMOTE_ADDR'];
        $timestamp = time();

        $this->query("INSERT INTO attempts_entry (ip, attempts, timestamp) VALUES ('$ip', '1', '$timestamp') 
                            ON DUPLICATE KEY UPDATE attempts=attempts+1, timestamp='$timestamp'
        ", 'iu');

    }

    public function delete(string $table, array $conditions) {

        $where = $this->create_where($conditions);

        $this->query("DELETE FROM $table $where", 'd');

    }

    public function check_access(): bool {

        $ip = $_SERVER['REMOTE_ADDR'];
        $timestamp = time();

        $data = $this->get('attempts_entry', ['ip' => $ip]);

        if(!empty($data)) {

            $data = $data[0];

            if( (int) $data['timestamp'] + DELAY_ATTEMPTS < $timestamp) {

                $this->delete('attempts_entry', ['ip' => $ip]);

                return true;

            }

            if($data['attempts'] < LIMIT_ATTEMPTS) return true;

            return false;

        }

        return true;

    }

    public function update(string $table, array $data, array $conditions) {

        $values = '';
        $where = $this->create_where($conditions);

        foreach ($data as $key => $value) {

            $values .= "$key='$value', ";

        }

        $values = rtrim($values, ', ');

        $this->query("UPDATE $table SET $values $where", 'u');


    }

}