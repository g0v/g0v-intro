<?php

class EventRow extends Pix_Table_Row
{
    public function getData()
    {
        if (!$this->data) {
            $d = new StdClass;
        } else {
            $d = json_decode($this->data);
        }
        if (!property_exists($d, 'permission')) {
            $d->permission = 0;
        }
        if (!property_exists($d, 'jitsi-site')) {
            $d->{'jitsi-site'} = 'meet.jit.si';
        }
        return $d;
    }

    public function updateData($values)
    {
        $data = $this->getData();
        foreach ($values as $k => $v) {
            $data->{$k} = $v;
        }
        $this->update(array('data' => json_encode($data)));
    }
}

class Event extends Pix_Table
{
    public function init()
    {
        $this->_name = 'event';
        $this->_primary = 'seq';
        $this->_rowClass = 'EventRow';

        $this->_columns['seq'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['id'] = array('type' => 'varchar', 'size' => 16);
        $this->_columns['name'] = array('type' => 'text');
        $this->_columns['created_at'] = array('type' => 'int');
        // 0 - 徵集中, 1 - 已結束
        $this->_columns['status'] = array('type' => 'int');
        $this->_columns['data'] = array('type' => 'text');

        $this->addIndex('id', array('id'), 'unique');
    }
}
