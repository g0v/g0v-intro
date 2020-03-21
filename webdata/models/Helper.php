<?php

class Helper
{
    public static function uniqid($length)
    {
        $set = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $ret = '';
        for ($i = 0; $i < $length; $i ++) {
            $ret .= $set[rand(0, strlen($set) - 1)];
        }
        return $ret;
    }

}
