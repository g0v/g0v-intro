<?php

class OAuthAppRow extends Pix_Table_Row
{
    public function getData()
    {
        $data = json_decode($this->data);
        if (!property_exists($data, 'redirect_urls')) {
            $data->redirect_urls = array();
        }
        return $data;
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

class OAuthApp extends Pix_Table
{
    public function init()
    {
        $this->_name = 'oauth_app';
        $this->_primary = 'client_id';
        $this->_rowClass = 'OAuthAppRow';

        $this->_columns['client_id'] = array('type' => 'int');
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['created_by'] = array('type' => 'varchar', 'size' => 32);
        // * name
        // * document
        // * client_secret
        // * redirect_urls: array
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('created_by', array('created_by'));
    }

    public static function getNewID()
    {
        for ($i = 0; $i < 10; $i ++) {
            $client_id = rand(10000000, 99999999);
            if (!OAuthApp::find($client_id)) {
                return $client_id;
            }
        }
        throw new Exception("no new id");
    }
}
