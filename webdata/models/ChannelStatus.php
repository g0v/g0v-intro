<?php

class ChannelStatusRow extends Pix_Table_Row
{
    public function updateMeta($meta)
    {
        $old_meta = json_decode($this->meta);
        foreach ($meta as $k => $v) {
            $old_meta->{$k} = $v;
        }
        $this->update(array(
            'updated_at' => time(),
            'meta' => json_encode($old_meta),
        ));

    }

    public function updateData($data)
    {
        $old_data = json_decode($this->data);
        foreach ($data as $k => $v) {
            $old_data->{$k} = $v;
        }
        $this->update(array(
            'updated_at' => time(),
            'data' => json_encode($old_data),
        ));

    }

    public function getMeta()
    {
        return json_decode($this->meta);
    }

    public function getData()
    {
        return json_decode($this->data);
    }

}

class ChannelStatus extends Pix_Table
{
    public function init()
    {
        $this->_name = 'channel_status';
        $this->_primary = 'channel_id';
        $this->_rowClass = 'ChannelStatusRow';

        $this->_columns['channel_id'] = array('type' => 'int');
        // title, description ...
        $this->_columns['meta'] = array('type' => 'text');
        $this->_columns['updated_at'] = array('type' => 'int');
        // viewers ...
        $this->_columns['data'] = array('type' => 'text');
    }
}
