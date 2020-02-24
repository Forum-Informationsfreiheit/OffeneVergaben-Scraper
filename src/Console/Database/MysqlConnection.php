<?php

namespace OffeneVergaben\Console\Database;

use PDO;

class MysqlConnection implements ConnectionInterface
{
    /**
     * @var PDO $connection;
     */
    protected $connection;

    public function __construct() {
    }

    public function connect() {
        if ($this->connection) {
            $this->disconnect();
        }

        $this->connection = new PDO(
            join(';',[
                "mysql:host=".getenv('DB_HOST'),
                "dbname=".getenv('DB_DATABASE'),
                "port=".getenv('DB_PORT'),
                "charset=utf8mb4"
            ])
            ,getenv('DB_USERNAME'),getenv('DB_PASSWORD'));
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_PERSISTENT, false);
    }

    public function disconnect() {
        $this->connection = null;
    }

    public function insert($query, array $data){
        $this->connection->prepare($query)->execute($data);
        return $this->connection->lastInsertId();
    }

    public function update($query, array $data) {
        $st = $this->connection->prepare($query);
        $st->execute($data);
        return $st->rowCount();
    }

    public function delete($query, array $data) {
        return $this->update($query,$data);
    }

    public function get($query, array $data = null){
        $st = $this->connection->prepare($query);
        $st->execute($data);
        return $st->fetchAll();
    }

    /**
     * @param $query
     * @param array $data
     * @return \PDOStatement
     */
    public function query($query, array $data) {
        $st = $this->connection->prepare($query);
        $st->execute();
        return $st;
    }
}