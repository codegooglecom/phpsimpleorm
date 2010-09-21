<?php

class SimpleORM_Connection {

    private static $connection;

    private function __construct() {
    }

    public static function start($host, $root, $password, $database) {
        if (!isset(self::$connection)) {
            self::$connection = new mysqli($host, $root, $password, $database);
            if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                exit();
            }
        }
    }
    
    private static function &getConnection() {
        return self::$connection;
    }

    private static function prepare($sql, $args) {
        $conn =& self::getConnection();

        if (isset($args)) {
            $query = "\$sql = call_user_func('sprintf', '$sql'";
            foreach ($args as $value) {
                $value = $conn->real_escape_string($value);
                $query.=",  \"'\".\$conn->real_escape_string('$value').\"'\"";
            }
            
            $query.=');';
            eval ($query);
            
        }
//        echo "$sql\n<br>";
        return $sql;
    }


    public static function execute($sql, $args) {
        $conn =& self::getConnection();
        

        if ($conn->query(self::prepare($sql, $args))) {
            if (!is_null($conn->insert_id))
                return $conn->insert_id;
            else {
                return true;
            }
        }
        else
            throw new Exception($conn->error);
    }


    public static function query($sql, $args, $datalister) {
        
        $conn =& self::getConnection();

        $result = $conn->query(self::prepare($sql, $args));

        if (!$result)
            throw new Exception($conn->error);

        if (isset ($datalister)) {
            $datalister->dataStarted();

            while($row = $result->fetch_array())
                $datalister->dataReady($row);

            $datalister->completion();
        } else {
            $array = array();
            while($row = $result->fetch_array())
                $array[]=$row;
            return $array;
        }
    }

    public static function value($colname, $tablename, $idcolname, $idcolvalue) {
        $sql = "select $colname from $tablename where $idcolname = %s ";
        $arr = self::query($sql, array($idcolvalue));
        return $arr[0][0];
    }

    public static function escape($string) {
        return self::getConnection()->real_escape_string($string);
    }
}
?>