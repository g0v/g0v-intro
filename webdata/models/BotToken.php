<?php

class BotToken extends Pix_Table
{
    public function init()
    {
        $this->_name = 'bot_token';
        $this->_primary = 'token_id';

        $this->_columns['token_id'] = ['type' => 'int', 'auto_increment' => true];
        $this->_columns['owner'] = ['type' => 'varchar', 'size' => 32];
        $this->_columns['created_at'] = ['type' => 'int'];
        $this->_columns['token'] = ['type' => 'char', 'size' => 32];
        $this->_columns['data'] = ['type' => 'text'];

        $this->addIndex('owner', ['owner']);
        $this->addIndex('token', ['token'], 'unique');
    }
}
