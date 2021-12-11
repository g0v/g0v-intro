<?php

class OAuthSessionCodeRow extends Pix_Table_Row
{
    public function getData()
    {
        return json_decode($this->data);
    }

    public function updateData($data)
    {
        $old_data = $this->getData();
        foreach ($data as $k => $v) {
            $old_data->{$k} = $v;
        }
        $this->update(array(
            'data' => json_encode($old_data),
        ));
    }
}

class OAuthSessionCode extends Pix_Table
{
    public function init()
    {
        $this->_name = 'oauth_session_code';
        $this->_primary = 'session_id';
        $this->_rowClass = 'OAuthSessionCodeRow';

        $this->_columns['session_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['app_id'] = array('type' => 'int');
        $this->_columns['slack_id'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['code'] = array('type' => 'char', 'size' => 16);
        $this->_columns['data'] = array('type' => 'text');
        $this->_columns['created_at'] = array('type' => 'int');

        $this->addIndex('app_slack', array('app_id', 'slack_id'));
    }
}
