<?php

class SimpleORM_Field {

    public $isKey = false;
    public $isNull = true;
    public $isUnique = false;
    public $entity = null;
    public $name;
    public $index = false;
    public $isUpdatable = true;
    public $type = 'varchar(255)';
    public $relationship = SimpleORM_Field::one_to_one;

    public function setParent(Entity $parent) {
        $this->parent = $parent;
    }

    const auto_int = 'auto_int';
    const one_to_one = 'o-o';
    const one_to_many = 'o-m';
    const many_to_many = 'm-m';
    const many_to_one = 'm-o';


}

?>
