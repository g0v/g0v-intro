<?php

class RPGRoom extends Pix_Table
{
    public function init()
    {
        $this->_name = 'rpg_room';
        $this->_primary = 'room_id';

        $this->_columns['room_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['room_name'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['updated_at'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('room_name', array('room_name'), 'unique');
    }
}
