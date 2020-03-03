<?php

class ChannelOwner extends Pix_Table
{
    public function init()
    {
        $this->_name = 'channel_owner';
        $this->_primary = array('channel_id', 'slack_id');

        $this->_columns['channel_id'] = array('type' => 'int');
        $this->_columns['slack_id'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['created_by'] = array('type' => 'varchar', 'size' => 32);

        $this->addIndex('slack_id', array('slack_id'));
    }
}
