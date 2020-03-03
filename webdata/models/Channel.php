<?php

class Channel extends Pix_Table
{
    public function init()
    {
        $this->_name = 'channel';
        $this->_primary = array('channel_id');

        $this->_columns['channel_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['event_id'] = array('type' => 'varchar', 'size' => 16);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 64);
        $this->_columns['order'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('event_order', array('event_id', 'order'));
    }
}
