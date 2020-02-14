<?php

class IntroVoice extends Pix_Table
{
    public function init()
    {
        $this->_name = 'intro_voice';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['voice'] = array('type' => 'text');
        $this->_columns['length'] = array('type' => 'int');

    }
}
