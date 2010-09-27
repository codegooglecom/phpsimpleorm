<?php

class SimpleORM_DAO {

    protected $entity;

    public function __construct(SimpleORM_Entity $entity) {
        $this->entity = $entity;
    }

    public function insert($object) {


        $direct = array();
        $insertqueue = array();

        foreach ($this->entity->getFields() as $field) {
            $fieldname = $field->name;
            $value = & $object->$fieldname;

            if ($field->type == SimpleORM_Field::auto_int)
                continue;

            if ($field->relationship == SimpleORM_Field::one_to_one && $field->entity == null) {
                $direct[] = array($fieldname, $value);
            } elseif (($field->relationship == SimpleORM_Field::one_to_one || $field->relationship == SimpleORM_Field::many_to_one) && $field->entity != null) {
                $dao = SimpleORM_Util::getDao($field->entity->name);
                $value = $dao->persist($value);
//                if($this->entity->name == 'project')
//                    print_r($value);
                $object->$fieldname = $value;


                $id = $field->entity->getID();
                $idname = $id->name;
                $idvalue = $value->$idname;
                $direct[] = array($fieldname, $idvalue);
            } elseif ($field->entity == null && $field->relationship == SimpleORM_Field::one_to_many) {
                foreach ($value as $val)
                    $insertqueue[] = array($fieldname, $val);
            } elseif ($field->entity != null && ($field->relationship == SimpleORM_Field::one_to_many || $field->relationship == SimpleORM_Field::many_to_many)) {
                $dao = SimpleORM_Util::getDao($field->entity->name);
                $array = array();
                foreach ($value as $val) {
                    $array[] = $dao->persist($val);
                    $id = $field->entity->getID();
                    $idname = $id->name;
                    $idvalue = $val->$idname;
                    $insertqueue[] = array($fieldname, $idvalue);
                }
                $object->$fieldname = $array;
            }
        }

        $stmt = "insert into {$this->entity->name} set ";
        $params = array();
        foreach ($direct as $dir) {
            $stmt .= "{$dir[0]} = %s, ";
            $params[] = $dir[1];
        }
        $stmt = substr($stmt, 0, strlen($stmt) - 2);
        $ret = SimpleORM_Connection::execute($stmt, $params);
        if (($auto = $this->entity->getAutoField()) != NULL) {
            $name = $auto->name;
            $object->$name = $ret;
        }

        //print_r($insertqueue);
        foreach ($insertqueue as $top) {
            $params = array();
            $stmt = "insert into {$this->entity->name}_{$top[0]} set ";
            $stmt .= " {$top[0]} = %s , {$this->entity->name} = %s";
            $params[] = $top[1];
            $params[] = $this->getIDValue($object);
            SimpleORM_Connection::execute($stmt, $params);
        }
        return $this->get($this->getIDValue($object));
    }

    public function update($object) {

        $direct = array();
        $deletequeue = array();
        $insertqueue = array();

        foreach ($this->entity->getFields() as $field) {
            $fieldname = $field->name;
            $value = & $object->$fieldname;

            if ($field->type == SimpleORM_Field::auto_int)
                continue;

            if ($field->relationship == SimpleORM_Field::one_to_one && $field->entity == null) {
                $direct[] = array($fieldname, $value);
            } elseif ($field->relationship == SimpleORM_Field::one_to_one && $field->entity != null) {
                $originalobj = $this->get($this->get($object))->$fieldname;

                $dao = SimpleORM_Util::getDao($field->entity->name);
                $dao->delete($originalobj);
                $value = $dao->persist($value);
                $object->$fieldname = $value;
                $id = $field->entity->getID();
                $idname = $id->name;
                $idvalue = $value->$idname;
                if ($idvalue != $originalobj->$idname)
                    $dao->delete($originalobj);
                $direct[] = array($fieldname, $idvalue);
            }

            elseif ($field->relationship == SimpleORM_Field::many_to_one && $field->entity != null) {
                $dao = SimpleORM_Util::getDao($field->entity->name);
                $value = $dao->persist($value);
                $object->$fieldname = $value;
                $id = $field->entity->getID();
                $idname = $id->name;
                $idvalue = $value->$idname;
                $direct[] = array($fieldname, $idvalue);
            } elseif ($field->entity == null && $field->relationship == SimpleORM_Field::one_to_many) {
                foreach ($value as $val)
                    $insertqueue[] = array($fieldname, $val);
                $deletequeue[] = "{$this->entity->name}_$field->name";
            } elseif ($field->entity != null && $field->relationship == SimpleORM_Field::one_to_many) {
                $dao = SimpleORM__Util::getDao($field->entity->name);
                $array = array();
                foreach ($value as $val) {
                    $array[] = $dao->persist($val);
                    $id = $field->entity->getID();
                    $idname = $id->name;
                    $idvalue = $val->$idname;
                    $insertqueue[] = array($fieldname, $idvalue);
                }
                $object->$fieldname = $array;
            } elseif ($field->entity != null && ($field->relationship == SimpleORM_Field::one_to_many || $field->relationship == SimpleORM_Field::many_to_many)) {
                $dao = SimpleORM_Util::getDao($field->entity->name);
                $array = array();
                foreach ($value as $val) {
                    $array[] = $dao->persist($val);
                    $id = $field->entity->getID();
                    $idname = $id->name;
                    $idvalue = $val->$idname;
                    $insertqueue[] = array($fieldname, $idvalue);
                }
                $object->$fieldname = $array;
            }
        }

        $stmt = "update {$this->entity->name} set ";
        $params = array();
        foreach ($direct as $dir) {
            $stmt .= "{$dir[0]} = %s, ";
            $params[] = $dir[1];
        }
        $stmt = substr($stmt, 0, strlen($stmt) - 2);
        $stmt .= " where {$this->entity->getID()->name} = %s";
        ///echo $this->getIDValue($object).'<br>';

        $params[] = $this->getIDValue($object);


        SimpleORM_Connection::execute($stmt, $params);

        foreach ($deletequeue as $table) {
            $stmt = "delete from $table where {$this->entity->name} =%s";
            SimpleORM_Connection::execute($stmt, array($this->getIDValue($object)));
        }

        foreach ($insertqueue as $top) {
            $params = array();
            $stmt = "insert into {$this->entity->name}_{$top[0]} set ";
            $stmt .= " {$top[0]} = %s , {$this->name} = %s";
            $params[] = $top[1];
            $params[] = $this->getIDValue($object);
        }
        SimpleORM_Connection::execute($stmt, $params);

        return $this->get($this->getIDValue($object));
    }

    public function getIDValue($object) {
        $idfield = $this->entity->getID()->name;
        $idv = $object->$idfield;
        return $idv;
    }

    public function get($id) {
        $query = & $this->query(SimpleORM_Criteria::eq($this->entity->getID()->name, $id));
        return $query[0];
    }

    private function toObject(array $results) {
        $objects = array();

        foreach ($results as $row) {
            $object = new stdClass();
            $id = $row[$this->entity->getID()->name];
            foreach ($this->entity->getFields() as $field) {

                $fieldname = $field->name;

                if ($field->relationship == SimpleORM_Field::one_to_one && $field->entity == null) {
                    $object->$fieldname = stripcslashes($row[$fieldname]);
                } elseif (($field->relationship == SimpleORM_Field::many_to_one || $field->relationship == SimpleORM_Field::one_to_one ) && $field->entity != null) {
                    $fieldentity = $field->entity;
                    $fieldentityname = $fieldentity->name;

                    $fielddao = SimpleORM_Util::getDao($fieldentityname);

                    //print_r( $row );
                    $object->$fieldname = $fielddao->get($row[$fieldname]);
                } elseif ($field->relationship == Field::one_to_many && $field->entity == null) {
                    $params = array();
                    $query = "select {$field->name} from {$this->entity->name}_{$field->name} where {$this->entity->name} = %s";
                    $params[] = $id;
                    $values = array();
                    foreach (Connection::query($query, $params) as $resultrow) {
                        $values[] = $resultrow[0];
                    }
                    $object->$fieldname = $values;
                } elseif (($field->relationship == Field::one_to_many || $field->relationship == Field::many_to_many ) && $field->entity != null) {
                    $fieldentity = $field->entity;
                    $fieldentityname = $fieldentity->name;
                    $fielddao = SimpleORM_Util::getDao($fieldentityname);

                    $params = array();
                    $query = "select {$field->name} from {$this->entity->name}_{$field->name} where {$this->entity->name} = %s";
                    $params[] = $id;
                    $values = array();
                    foreach (SimpleORM_Connection::query($query, $params) as $resultrow)
                        $values[] = $fielddao->get($resultrow[0]);

                    $object->$fieldname = $values;
                }
            }
            $objects[] = $object;
        }

        return $objects;
    }

    public function persist($object) {
        if ($this->get($this->getIDValue($object)) == null)
            return $this->insert($object);
        else
            return $this->update($object);
    }

    public function delete($object) {

        foreach ($this->entity->getFields() as $field) {
            $entity = $field->entity;
            $entname = $field->name;


            if ($field->relationship == SimpleORM_Field::one_to_one && $field->entity != null) {
                $entobj = $object->$entname;
                $dao = SimpleORM_Util::getDao($entity->name);
                $dao->delete($entobj);
            } elseif ($field->relationship == SimpleORM_Field::one_to_many) {

                $query = "delete from {$this->entity->name}_{$field->name} where {$this->entity->name} = %s";
                $params = array();
                $params[] = $this->getIDValue($object);
                SimpleORM_Connection::execute($query, $params);

                if ($field->entity != null) {
                    $object2 = SimpleORM_Util::getDao($this->entity->name)->get($this->getIDValue($object));
                    foreach ($object2->$entname as $entobj)
                        $dao->delete($entobj);
                }
            }
        }
        $stmt = "delete from {$this->entity->name} where {$this->entity->getID()->name} = %s";
        SimpleORM_Connection::execute($stmt, array($this->getIDValue($object)));
    }

    public function query($criterium=null, $restriction=null) {

        $stmt = sprintf('select  %s from %s %s %s',
                        implode(', ', $this->entity->getSingulars()),
                        $this->entity->name,
                        strlen(trim("$criterium")) == 0 ? '' : " where $criterium",
                        $restriction == null ? '' : " $restriction"
        );
        return $this->toObject(SimpleORM_Connection::query($stmt));
    }

    public function count($criterium) {
        //TODO do this a more sensible way
        return count($this->query($criterium));
    }

}
?>