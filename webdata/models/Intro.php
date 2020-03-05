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
        $this->_columns['created_by'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['data'] = array('type' => 'text');

        $this->_relations['user'] = array('rel' => 'has_one', 'type' => 'User', 'foreign_key' => 'created_by');

        $this->addIndex('event_time', array('event', 'created_at'));
        $this->addIndex('event_user', array('event', 'created_by'));
    }
}
