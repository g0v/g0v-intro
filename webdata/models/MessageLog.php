<?php

class MessageLog extends Pix_Table
{
    public function init()
    {
        $this->_name = 'message_log';
        $this->_primary = array('channel', 'ts');

        $this->_columns['channel'] = array('type' => 'char', 'size' => 16);
        $this->_columns['ts'] = array('type' => 'double');
        $this->_columns['data'] = array('type' => 'text');
    }
}
