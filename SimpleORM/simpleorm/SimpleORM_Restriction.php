<?php

class SimpleORM_Restriction {

    private $page_num, $size, $order_by, $order;

    const asc = 'asc';
    const desc = 'desc';

    public function __construct($page_num, $size, $order_by, $order) {
        $this->page_num = $page_num;
        $this->size = $size;
        $this->order_by = $order_by;
        $this->order = $order;
    }

    public function __toString() {
        if (!empty($this->order_by)) {
            $string = "order by $this->order_by $this->order";
        }
        if (!empty($this->size)) {
            $string .= " limit $this->size ";
            if (!empty($this->page_num))
                $string .= sprintf(" offset %s", ($this->page_num-1) * $this->size);
        }
        return $string;
    }

}
?>
