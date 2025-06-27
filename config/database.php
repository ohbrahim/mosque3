<?php
/**
 * كلاس قاعدة البيانات المحسن
 */

class Database {
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
        }
    }
    
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database Fetch Error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database FetchAll Error: " . $e->getMessage() . " SQL: " . $sql);
            return [];
        }
    }
    
    public function insert($table, $data) {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            
            $stmt = $this->pdo->prepare($sql);
            
            // تسجيل محاولة الإدراج
            error_log("Attempting insert: " . $sql . " with data: " . json_encode($data));
            
            $result = $stmt->execute($data);
            
            if ($result) {
                error_log("Insert successful, affected rows: " . $stmt->rowCount());
            } else {
                error_log("Insert failed, no rows affected");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Database Insert Error: " . $e->getMessage() . " SQL: " . $sql . " Data: " . json_encode($data));
            return false;
        }
    }
    
    public function update($table, $data, $where, $params = []) {
        try {
            $set = [];
            $allParams = [];
            
            // إعداد البيانات للتحديث
            foreach ($data as $key => $value) {
                $set[] = "{$key} = ?";
                $allParams[] = $value;
            }
            
            // إضافة معاملات WHERE
            foreach ($params as $param) {
                $allParams[] = $param;
            }
            
            $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($allParams);
            
            // تسجيل نجاح العملية
            if ($result) {
                error_log("Update successful: " . $sql . " with params: " . json_encode($allParams));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Database Update Error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database Delete Error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Query Error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    public function getErrorInfo() {
        return $this->pdo->errorInfo();
    }
}
?>