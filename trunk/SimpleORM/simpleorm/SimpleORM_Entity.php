<?php

class SimpleORM_Entity {

    private $fields = array();
    private $id;
    private $withentity = array();
    private $auto;
    private $updatables;
    private $onetooneentities = array();
    private $singular = array();
    private $withoutentity = array();
    public $name;
    private $basicFields = array();
    private $singularEntities = array();
    private static $delete = 'cascade';
    private static $disallow = 'restrict';

    
    public function setID(Field $id) {
        $this->id = $id;
    }

    public function getID() {
        return $this->id;
    }

    public function getField($name) {
        foreach ($this->fields as $field) {
            if ($field->name == $name)
                return $field;
        }
        return null;
    }

    public function getAutoField() {
        return $this->auto;
    }

    public function getSingulars() {
        return $this->singular;
    }

    private function toColDefinition($field, $name) {
        $main = isset($name) ? "$name " : "$field->name ";
        $main .= $field->type == Field::auto_int ? "int auto_increment " : "$field->type ";
        $main .= $field->isNull ? ' null ' : ' not null ';
        $main .= $field->isUnique ? 'unique' : '';
        $main .= $field->isKey ? 'primary key' : '';
        return $main;
    }

    private function toReference($field, $table, $tablecol) {
        return "foreign key ($field) references $table($tablecol) on delete restrict on update restrict";
    }

    private function joinSQL($tablename, array $cols, array $constraints) {
        return sprintf('create table %s (%s)', $tablename, implode(',', array_merge($cols, $constraints)));
    }

    //TODO try include multilpe dialect support in the future
    public function createStmts() {
        //echo creating;
        $stmts = array();
        $columns = array();
        $constraints = array();

        foreach ($this->fields as $field) {

            //echo "<br> ahahahhaha $field->name $field->relationship<br><br>\n";
            if ($field->relationship == Field::one_to_one && $field->entity == null) {

                $columns[] = $this->toColDefinition($field);
                //print_r($columns);
            } elseif ($field->relationship == Field::one_to_many && $field->entity == null) {
                $linkcols = array();
                $linkconstraints = array();
                $fieldtemp = $this->getID();
                if ($fieldtemp->type == Field::auto_int) {
                    $fieldtemp->type = 'int';
                    $fieldtemp->isNull = FALSE;
                }
                $fieldtemp->isKey = FALSE;

                $linkcols[] = $this->toColDefinition($field);
                $linkcols[] = $this->toColDefinition($fieldtemp, $this->name);
                $linkconstraints[] = $this->toReference($this->name, $this->name, $this->getID()->name);

                $stmts[] = $this->joinSQL("{$this->name}_{$field->name}", $linkcols, $linkconstraints);
            } elseif (($field->relationship == Field::one_to_one || $field->relationship == Field::many_to_one) && $field->entity != null) {

                $fieldtemp = $field->entity->getID();
                if ($fieldtemp->type == Field::auto_int) {
                    $fieldtemp->type = 'int';
                    $fieldtemp->isNull = $field->isNull;
                }
                $fieldtemp->isKey = FALSE;
                $columns[] = $this->toColDefinition($fieldtemp, $field->name);
                $constraints[] = $this->toReference($field->name, $field->entity->name, $field->entity->getID()->name);
            } elseif (($field->relationship == Field::one_to_many || $field->relationship == Field::many_to_many) && $field->entity != null) {

                $linkcols = array();
                $linkconstraints = array();
                $fieldtemp = $this->getID();
                if ($fieldtemp->type == Field::auto_int) {
                    $fieldtemp->type = 'int';
                    $fieldtemp->isNull = FALSE;
                }
                $fieldtemp->isKey = FALSE;

                $enttemp = $field->entity->getID();
                if ($enttemp->type == Field::auto_int) {
                    $enttemp->type = 'int';
                    $enttemp->isNull = FALSE;
                }
                $enttemp->isKey = FALSE;


                $linkcols[] = $this->toColDefinition($fieldtemp, $this->name);
                $linkcols[] = $this->toColDefinition($enttemp, $field->name);
                $linkconstraints[] = $this->toReference($this->name, $this->name, $this->getID()->name);
                $linkconstraints[] = $this->toReference($field->name, $field->entity->name, $field->entity->getID()->name);

                $stmts[] = $this->joinSQL("{$this->name}_{$field->name}", $linkcols, $linkconstraints);
            }
        }
        $stmts['main'] = $this->joinSQL($this->name, $columns, $constraints);

        //print_r($stmts);
        return $stmts;
    }

    public function getName() {
        return $this->name;
    }

    public function getWithEntity() {
        return $this->withentity;
    }

    public function __toString() {
        return $this->getName();
    }

    public function addField(Field $f) {
        $this->fields[] = & $f;
        if ($f->isKey)
            $this->ids[] = & $f;
        if ($f->type == Field::auto_int)
            $this->auto = & $f;
        if ($f->isUpdatable)
            $this->updatables[] = & $f;
        if ($f->entity != null)
            $this->withentity[] &= $f;
        else
            $this->withoutentity[] = $f;
        if ($f->relationship == Field::one_to_one || $f->relationship == Field::many_to_one) {
            $this->singular[] = & $f->name;

        }

//        if ($f->entity == null && $f->relationship == Field::one_to_one) {
//            $this->basicFields[] = & $f->name;
//        } elseif ($f->entity != null && $f->relationship == Field::one_to_one) {
//            $this->singularEntities[] = & $f;
//        }
    }

    public function getIDs() {
        return $this->ids;
    }

    public function getFields() {
        return $this->fields;
    }

    public function getBasicField() {
        return $this->basicFields;
    }

}

class Field {

    public $isKey = false;
    public $isNull = true;
    public $isUnique = false;
    public $entity = null;
    public $name;
    public $index = false;
    public $isUpdatable = true;
    public $type = 'varchar(255)';
    public $relationship = Field::one_to_one;

    //private $parent;

    public function fillDefaults() {
        //if (!isset ())
    }

    public function setParent(Entity $parent) {
        $this->parent = $parent;
    }

    const auto_int = 'auto_int';
    const one_to_one = 'o-o';
    const one_to_many = 'o-m';
    const many_to_many = 'm-m';
    const many_to_one = 'm-o';

    /**
     * checks for illegall combination of properties
     */
    public function validate() {
        
    }

}

?>