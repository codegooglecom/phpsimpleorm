<?php

abstract class SimpleORM_Criteria {

    protected $entity;
    protected $values = array();
    protected $field;
    protected $children = array();

    abstract function __toString();

    public function getEntity() {
        return $this->entity;
    }

    public function getValues() {
        return $this->values;
    }

    public function getField() {
        return $this->field;
    }

    public static function like($field, $value, $entity) {
        return new Like($field, $value, $entity);
    }

    public static function eq($field, $value, $entity) {
        return new Eq($field, $value, $entity);
    }

    public static function btw($field, array $values) {
        return new Btw($field, $values);
    }

    public static function gt($field, $value, $entity) {
        return new Gt($field, $value, $entity);
    }

    public static function lt($field, $value, $entity) {
        return new Lt($field, $value, $entity);
    }

    public static function ge($field, $value, $entity) {
        return new Ge($field, $value, $entity);
    }

    public static function le($field, $value, $entity) {
        return new Le($field, $value, $entity);
    }

    public static function any(array $args) {

        //$args = func_get_args();
        foreach ($args as $arg)
            if (!is_a($arg, 'C') && $arg != null)
                throw new Exception('all parameters must be intances of C');

        return new Any($args);
    }

    public static function all(array $args) {
        foreach ($args as $arg)
            if (!is_a($arg, 'C') && $arg != null)
                throw new Exception('all parameters must be intances of C');

        return new All($args);
    }

}

class Btw extends C {

    public function __construct($field, $value, $entity) {
        $this->field = $field;
        $this->values[] = $value;
        $this->entity = $entity;
    }

    public function __toString() {
        return sprintf("%s.%s between '%s' and '%s'",
                $this->entity,
                $this->field,
                mysqli_real_escape_string($this->value[0]),
                mysqli_real_escape_string($this->value[1])
        );
    }

}

class Like extends C {

    public function __construct($field, $value, $entity) {
        $this->field = $field;
        $this->values[] = $value;
        $this->entity = $entity;
    }
    
    public function __toString() {
        return sprintf("%s like '%s'", $this->field, Connection::escape($this->values[0]));
    }

}

class Gt extends C {

    public function __construct($field, $value, $entity) {
        $this->field = $field;
        $this->values[] = $value;
        $this->entity = $entity;
    }

    public function __toString() {
        return sprintf("%s.%s > '%s'", $this->entity, $this->field, mysqli_real_escape_string($this->value[0]));
    }

}

class Lt extends C {

    public function __construct($field, $value, $entity) {
        $this->field = $field;
        $this->values[] = $value;
        $this->entity = $entity;
    }

    public function __toString() {
        return sprintf("%s.%s < '%s'", $this->entity, $this->field, mysqli_real_escape_string($this->value[0]));
    }

}

class Ge extends C {

    public function __construct($field, $value, $entity) {
        $this->field = $field;
        $this->values[] = $value;
        $this->entity = $entity;
    }

    public function __toString() {
        return sprintf("%s.%s >= '%s'", $this->entity, $this->field, mysqli_real_escape_string($this->value[0]));
    }

}

class Le extends C {

    public function __construct($field, $value, $entity) {
        $this->field = $field;
        $this->values[] = $value;
        $this->entity = $entity;
    }

    public function __toString() {
        return sprintf("%s.%s <= '%s'", $this->entity, $this->field, mysqli_real_escape_string($this->value[0]));
    }

}

class Eq extends C {

    public function __construct($field, $value, $entity) {
        $this->field = $field;
        $this->values[] = $value;
        $this->entity = $entity;
    }

    public function __toString() {
        return sprintf("%s = '%s'", $this->field, Connection::escape($this->values[0]));
    }

}

class All extends C {

    public function __construct($args) {
        $this->children = $args;
    }

    public function __toString() {
        $array = array();
        foreach ($this->children as $child) {
            if ($child == null)
                continue;
            $array[] = $child->__toString();
        }
        return implode(' and ', $array);
    }

}

class Any extends C {

    public function __construct($args) {
        $this->children = $args;
    }

    public function __toString() {
        $array = array();
        foreach ($this->children as $child) {
            if ($child == null)
                continue;
            $array[] = $child->__toString();
        }
        return implode(' or ', $array);
    }

}
?>