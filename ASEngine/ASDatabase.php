<?php
class ASDatabase extends PDO
{
    private static $instance;

    public function __construct($DB_TYPE, $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS)
    {
        try {
            parent::__construct($DB_TYPE.':host='.$DB_HOST.';dbname='.$DB_NAME.';charset=utf8', $DB_USER, $DB_PASS);
            $this->exec('SET CHARACTER SET utf8');

            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

        } catch (PDOException $e) {
            die ( 'Connection failed: ' . $e->getMessage() );
        }
    }

    public static function getInstance() {
        if ( self::$instance === null )
            self::$instance = new self(DB_TYPE, DB_HOST, DB_NAME, DB_USER, DB_PASS);

        return self::$instance;
    }
    
    public function select($sql, $array = array(), $fetchMode = PDO::FETCH_ASSOC)
    {
        $db = self::getInstance();

        $sth = $db->prepare($sql);
        foreach ($array as $key => $value) {
            $sth->bindValue(":$key", $value);
        }
        
        $sth->execute();
        return $sth->fetchAll($fetchMode);
    }
    
    public function insert($table, $data)
    {
        $db = self::getInstance();

        ksort($data);
        
        $fieldNames = implode('`, `', array_keys($data));
        $fieldValues = ':' . implode(', :', array_keys($data));
        
        $sth = $db->prepare("INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)");
        
        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }
        
        $sth->execute();
    }
    
    public function update($table, $data, $where, $whereBindArray = array())
    {
        $db = self::getInstance();

        ksort($data);
        
        $fieldDetails = NULL;
        
        foreach($data as $key=> $value) {
            $fieldDetails .= "`$key`=:$key,";
        }
        $fieldDetails = rtrim($fieldDetails, ',');
        
        $sth = $db->prepare("UPDATE $table SET $fieldDetails WHERE $where");
        
        foreach ($data as $key => $value) {
            $sth->bindValue(":$key", $value);
        }
        
        foreach ($whereBindArray as $key => $value) {
            $sth->bindValue(":$key", $value);
        }
        
        
        $sth->execute();
    }
    
    public function delete($table, $where, $bind = array(), $limit = 1)
    {
        $db = self::getInstance();

        $sth = $db->prepare("DELETE FROM $table WHERE $where LIMIT $limit");
        
        foreach ($bind as $key => $value) {
            $sth->bindValue(":$key", $value);
        }
        
        $sth->execute();
    }
}