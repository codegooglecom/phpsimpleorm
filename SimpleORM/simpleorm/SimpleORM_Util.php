<?php

require_once 'SimpleORM_Connection.php';
require_once 'SimpleORM_Criteria.php';
require_once 'SimpleORM_DAO.php';
require_once 'SimpleORM_Entity.php';
require_once 'SimpleORM_Logger.php';
require_once 'SimpleORM_Restriction.php';
require_once 'SimpleORM_Settings.php';
require_once 'SimpleORM_Field.php';

class SimpleORM_Util {

    /**
     * Hash table to hold all the entities defines in the entities xml file
     * @var <type>
     */
    private static $entities = array();

    private static $loaded = false;

    public static function load() {
        
        self::loadSettings();
        self::connect();
        self::loadEntities();
        self::$loaded = true;
    }

    private static function connect() {
        SimpleORM_Connection::start(Settings::get('mysql_host'), Settings::get('mysql_username'), Settings::get('mysql_password'), Settings::get('mysql_database'));
    }

    private static function loadEntities() {
        $str = file_get_contents(Settings::get('entities'));
        echo $str;
        $entities = new SimpleXMLElement($str);
        foreach ($entities->entity as $entity) {
            $ent = new SimpleORM_Entity();

            $ent->name = "{$entity['name']}";

            foreach ($entity->field as $field) {
                $f = new SimpleORM_Field();
                $f->name = "{$field['name']}";
                $f->isKey = "{$field['key']}" == 'yes' ? true : false;
                $f->isNull = "{$field['null']}" == 'yes' ? true : false;
                $f->relationship = strlen("{$field['relationship']}") == 0 ? SimpleORM_Field::one_to_one : "{$field['relationship']}";
                $f->isUnique = "{$field['unique']}" == 'yes' ? true : false;
                $f->entity = strlen("{$field['entity']}") == 0 ? null : self::getEntity("{$field['entity']}");
                if (strlen($f->name) == 0) {
                    $f->name = $f->entity->name;
                }
                
                $f->type = strlen("{$field['type']}") == 0 ? 'varchar(255)' : "{$field['type']}";

                if ($f->isKey) 
                    $ent->setID($f);
                
                $ent->addField($f);
                //echo "name: $f->name unique:$f->isUnique relationship:$f->relationship entity:$f->entity<br>";
            }
            
            self::$entities[$ent->name] =& $ent;
        }
        
        if (Settings::get('auto_create_table') == 'yes')
            foreach (self::$entities as $entity) {
                foreach ($entity->createStmts() as $stmt) {
                    try {
                        SimpleORM_Connection::execute($stmt);
                    } catch (Exception $e) {
                        //Logger::logException($e);
                        echo $e->getMessage();
                        echo "<br>";
                        echo $e->getTraceAsString();
                    }
                }
            }

        
    }

    public static function getEntity($name) {
        return self::$entities[$name];
    }

    private static function loadSettings() {
        global $simpleORMSetting;
        foreach ($simpleORMSetting as $key=>$value) {
            Settings::set($key, $value);
        }
    }

    static function getDao($entity) {
        if (!self::$loaded)
            self::load();
        return new SimpleORM_DAO(self::getEntity($entity));
    }

}


class Settings {

    private static $array = array();

    public static function set($key, $value) {
        self::$array[$key] = $value;
    }

    public static function get($key) {
        return self::$array[$key];
    }
}
?>
