<?php

class RPGRoomObject extends Pix_Table
{
    public function init()
    {
        $this->_name = 'rpg_room_object';
        $this->_primary = 'room_object_id';

        $this->_columns['room_object_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['room_id'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('room_id', array('room_id'));
    }
}
