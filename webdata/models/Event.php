<?php

class Event extends Pix_Table
{
    public function init()
    {
        $this->_name = 'event';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'varchar', 'size' => 16);
        $this->_columns['name'] = array('type' => 'text');
        $this->_columns['created_at'] = array('type' => 'int');
        // 0 - 徵集中, 1 - 已結束
        $this->_columns['status'] = array('type' => 'int');
    }
}
