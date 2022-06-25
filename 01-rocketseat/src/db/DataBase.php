<?php

namespace DataBase;

class DataBase {
    private const SINGLE = 0;
    private const MULTI = 1;

    protected $db;
    private static $transactionMode = false;                                   // Status that difine if is on transaction or not.
    private static $typeTransaction;                                           // If is Multi or Single.
    private static $dbsInTransaction = [];                                     // Data bases in transaction.
    private $sql = null;

    public function __construct() {
        global $db;

        $this->db = $db;
    }

    // Start single transaction;
    public function startTransaction($sgbdName) {
        $this->dataBaseExists($sgbdName);
        self::$typeTransaction = self::SINGLE;
        self::$transactionMode = true;
        $this->db[$sgbdName]->beginTransaction();
    }

    // Start multi transaction;
    public function startMultiBDTransaction() {
        self::$typeTransaction = self::MULTI;
        self::$transactionMode = true;
    }

    // Set database trasaction mode and added him to array of databases on trasaction.
    public function addBdToTransaction($sgbdName) {
        $this->dataBaseExists($sgbdName);
        if(self::$typeTransaction != self::MULTI) {
            throw new \Exception(getErrorMessage('multiTransactionNotStarted'));
        }

        $this->db[$sgbdName]->beginTransaction();
        array_push(self::$dbsInTransaction, $sgbdName);
    }

    public function bindParam($index, $variable) {
        try {
            $this->sql->bindParam($index, $variable);
        } catch (\PDOException $e) {
            if(self::$transactionMode) {
                $this->rollbackAll();
            }
            throw new \Exception($e->getMessage() . " Index of error: " . $index . "\n");
        } catch (\Exception $e) {
            if(self::$transactionMode) {
                $this->rollbackAll();
            }
            throw new \Exception($e->getMessage() . " Index of error: " . $index . "\n");
        }
    }

    public function commit($sgbdName) {
        $this->dataBaseExists($sgbdName);
        try {
            $this->db[$sgbdName]->commit();
            if(self::$transactionMode && self::$typeTransaction == self::SINGLE) {
                self::$typeTransaction = null;
                self::$transactionMode = false;
            }
        } catch (\Exception $e) {
            if(self::$transactionMode) {
                $this->rollback($sgbdName);
            }
            throw $e;
        }
        self::$transactionMode = false;
    }

    // Commit all transactions. 
    // Parameter printMsg defines if the message of success will be printed 
    public function commitAll() {
        try{
            foreach(self::$dbsInTransaction as $dbTransaction) {       // Interact for all the databases in transaction and commite one by one.
                $this->db[$dbTransaction]->commit();
            }
        } catch (\PDOException $e) {
            if(self::$transactionMode) {
                $this->rollbackAll();
            }
            throw $e;
        }
        self::$typeTransaction = null;
        self::$transactionMode = false;
    }

    private function rollbackAll() {                            // If somenthing goes wrong, rollback all database that is in transaction.
        foreach ($this->db as $db) {
            $db->rollback();
        }
        self::$dbsInTransaction = [];
        self::$transactionMode = false;
    }

    public function rollback($sgbdName) {                       // If somenthing goes wrong, rollback all database that is in transaction.
        $this->dataBaseExists($sgbdName);
        if(self::$typeTransaction == self::SINGLE) {
            $this->db[$sgbdName]->rollback();
        }else if(self::$typeTransaction == self::MULTI) {
            foreach(self::$dbsInTransaction as &$dbTransaction) {
                $this->db[$dbTransaction]->rollback();
            }
            self::$dbsInTransaction = [];
        }
        self::$typeTransaction = null;
        self::$transactionMode = false;
    }

    protected function select($query, $sgbdName) {
        $this->dataBaseExists($sgbdName);
        try {
            if($this->sql == null) {
                $sql = $this->db[$sgbdName]->prepare($query);
            }else{
                $sql = $this->sql;
            }

            $sql->execute();
            $res = $sql->fetchAll(\PDO::FETCH_ASSOC);

            return $res;
        }catch(\PDOException $e){
            if(self::$transactionMode) {
                $this->rollback($sgbdName);
            }
            throw $e;
        }
    }

    protected function _select($sgbdName) {
        $this->dataBaseExists($sgbdName);
        try {
            $sql = $this->sql;

            $sql->execute();
            $res = $sql->fetchAll(\PDO::FETCH_ASSOC);

            return $res;
        }catch(\PDOException $e){
            if(self::$transactionMode) {
                $this->rollback($sgbdName);
            }
            throw $e;
        }
    }

    protected function setSqlManager($query, $sgbdName) {
        $this->sql = $this->db[$sgbdName]->prepare($query);
    }

    public function _prepareSql($query, $dbname)
    {
        $this->dataBaseExists($dbname);
        try {
            $this->sql = $this->db[$dbname]->prepare($query);
            return $this;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private function dataBaseExists($dbName) {
        if(!isset($this->db[$dbName])) throw new \Exception(getErrorMessage('dataBaseNotFound'));

        return true;
    }

    private static function _replaceDotOnBindKey($key) {
        if(str_contains($key, '.')) {
            return str_replace('.', '_', $key);
        }
        return $key;
    }

    private static function _arrangeGraveAccentOnBindKey($key) {
        if(!str_contains($key, '.')) {
            return "`$key`";
        }else {
            return (str_replace('.', '.`', $key) . '`');
        }
    }

    public static function _createBindParams($array, $ignorekey = [])
    {
        $query = '';
        foreach ($array as $key => $value) {
            if (in_array($key, $ignorekey) == false) {
                $query .= (self::_arrangeGraveAccentOnBindKey($key) . ' = :' . self::_replaceDotOnBindKey($key) . ', ');
            }
        }

        $query = substr($query, 0, -2) . ' '; //remove os dois ultimos caracters, uma virgula e um espaco ", " e depois aciociona um espaço " "

        return $query;
    }

    public static function _createBindParamsWAND($array, $ignorekey = [])
    {
        $query = '';
        foreach ($array as $key => $value) {
            if (in_array($key, $ignorekey) == false) {
                $query .= self::_arrangeGraveAccentOnBindKey($key) . ' = :' . self::_replaceDotOnBindKey($key) . ' AND ';
            }
        }

        $query = substr($query, 0, -5) . ' '; //remove os cinco ultimos caracters, um espaco, AND e um espaco ", " e depois aciociona um espaço " "

        return $query;
    }

    public function _bindValues($params)
    {
        $actualKey = '';
        try {
            foreach ($params as $key => $value) {
                $actualKey = $key;
                $this->sql->bindValue(':' . self::_replaceDotOnBindKey($key), $value);
            }
            return $this;
        } catch (\Exception $e) {
            throw new \Exception("Key: " . $actualKey . $e->getMessage());
        }
    }

    protected function insertPreparedQuery($sgbdName) {
        $this->dataBaseExists($sgbdName);
        try{
            // echo "<pre>";
            // print_r($this->sql);
            $this->sql->execute();
            // print_r($this->sql->debugDumpParams());
        }catch (\Exception $e) {
            if(self::$transactionMode) {
                $this->rollback($sgbdName);
            }
            throw $e;
        }
        $this->sql= null;
    }

    protected function insert($query, $sgbdName) {
        $this->dataBaseExists($sgbdName);
        try{
            if($this->sql == null) {
                $sql = $this->db[$sgbdName]->prepare($query);
            }else{
                $sql = $this->sql;
            }
            $sql->execute();
        }catch(\PDOException $e) {
            if(self::$transactionMode) {
                $this->rollback($sgbdName);
            }
            throw $e;
        }
        $this->sql= null;
    }

    protected function delete($query, $sgbdName) {
        $this->dataBaseExists($sgbdName);
        try {
            if($this->sql == null) {
                $sql = $this->db[$sgbdName]->prepare($query);
            }else{
                $sql = $this->sql;
            }
            // echo "<pre>";
            // print_r($this->sql->debugDumpParams());
            // echo "</pre>";
            $sql->execute();            
        }catch(\PDOException $e) {
            if(self::$transactionMode) {
                $this->rollback($sgbdName);
            }
            throw $e;
        }
        $this->sql= null;
    }

    protected function _update($query, $sgbdName){
        $this->dataBaseExists($sgbdName);
        try {
            if($this->sql == null) {
                $sql = $this->db[$sgbdName]->prepare($query);
            }else{
                $sql = $this->sql;
            }
            
            $sql->execute();
        }catch(\PDOException $e) {
            if(self::$transactionMode) {
                $this->rollback($sgbdName);
            }
            // print_r($e->getTrace);
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
        $this->sql= null;
    }

    protected function updatePreparedQuery($sgbdName) {
        $this->dataBaseExists($sgbdName);
        try {
            $this->sql->execute();
        }catch(\PDOException $e) {
            if(self::$transactionMode) {
                $this->rollback($sgbdName);
            }
            // print_r($e->getTrace);
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
        $this->sql= null;
    }

    protected function lastInsertId($sgbdName) {
        $this->dataBaseExists($sgbdName);
        return $this->db[$sgbdName]->lastInsertId();
    }
    // ################################

    public function formatSQLOperatorIn($values) {
        $inOperator = ' IN (';

        $inOperator .= array_reduce($values, function ($carry, $value) {
            if($carry) {
                $carry .= ", ";
            }

            $carry .= "'$value'";
            return $carry;
        }) . ") ";

        return $inOperator;
    }

    // ################################
}