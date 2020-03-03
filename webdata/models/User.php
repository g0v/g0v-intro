<?php

class UserRow extends Pix_Table_Row
{
    public function getDisplayName()
    {
        if ($data = json_decode($this->data)) {
            return $data->display_name;
        }
    }

    public function getImage()
    {
        if ($data = json_decode($this->data)) {
            return $data->image;
        }
    }
}

class User extends Pix_Table
{
    public function init()
    {
        $this->_name = 'user';
        $this->_primary = 'slack_id';
        $this->_rowClass = 'UserRow';

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
