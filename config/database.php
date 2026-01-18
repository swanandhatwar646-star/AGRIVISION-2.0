<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    public $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8mb4",
                $this->user,
                $this->pass
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $this->conn;
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function query($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function execute($stmt, $params = []) {
        return $stmt->execute($params);
    }
    
    public function fetchAll($stmt, $params = []) {
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function fetch($stmt, $params = []) {
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
?>
