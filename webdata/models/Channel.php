<?php

class ChannelRow extends Pix_Table_Row
{
    public function canSee($user)
    {
        if ($user and $user->type == 2) {
            return true; // admin can see all
        }

        if ($this->getData()->type == 1) {
            return true; // channel is public
        }

        if ($user and in_array($user->slack_id, $this->getData()->owners)) {
            return true; // is owner
        }

        if ($user and in_array($user->slack_id, $this->getData()->invite_list)) {
            return true; // is invited
        }

        return false;
    }

    public function getData()
    {
        return json_decode($this->data);
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

    public function getStatus()
    {
        if (!$status = ChannelStatus::find($this->channel_id)) {
            $status = ChannelStatus::insert(array(
                'channel_id' => $this->channel_id,
                'meta' => '{}',
                'data' => '{}',
                'updated_at' => time(),
            ));
        }
        return $status;
    }
}

class Channel extends Pix_Table
{
    public function init()
    {
        $this->_name = 'channel';
        $this->_primary = array('channel_id');
        $this->_rowClass = 'ChannelRow';

        $this->_columns['channel_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['event_id'] = array('type' => 'varchar', 'size' => 16);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 64);
        $this->_columns['order'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('event_order', array('event_id', 'order'));
    }
}
