<?php

class OAuthSession extends Pix_Table
{
    public function init()
    {
        $this->_name = 'oauth_session';
        $this->_primary = 'access_token';

        $this->_columns['access_token'] = array('type' => 'char', 'size' => 32);
        $this->_columns['app_id'] = array('type' => 'int');
        $this->_columns['slack_id'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('app_id', array('app_id'));
        $this->addIndex('slack_id', array('slack_id'));
    }

    public static function getNewAccessToken()
    {
        for ($retry = 0; $retry < 10; $retry ++) {
            $access_token = Helper::uniqid(32);
            if (!OAuthSession::find($access_token)) {
                return $access_token;
            }
        }
        throw new Exception("get access token failed");
    }
}
