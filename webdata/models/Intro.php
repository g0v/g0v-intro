<?php

class Intro extends Pix_Table
{
    public function init()
    {
        $this->_name = 'intro';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['event'] = array('type' => 'varchar', 'size' => 16);
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');
    }
}
