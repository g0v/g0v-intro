<?php

class User extends Pix_Table
{
    public function init()
    {
        $this->_name = 'user';
        $this->_primary = 'slack_id';

        $this->_columns['slack_id'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['account'] = array('type' => 'varchar', 'size' => 32);
        // 0 - admin, 1 - proposer, 2 - member
        $this->_columns['type'] = array('type' => 'int');
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['logined_at'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('account', array('account'), 'unique');
    }
}
