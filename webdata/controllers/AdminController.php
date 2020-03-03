<?php 

class AdminController extends Pix_Controller
{
    public function init()
    {
        if (!$user_id = Pix_Session::get('user_id') or !$user = User::find($user_id) or $user->type != 2) {
            return $this->alert('Access Denied', '/');
        }
        $this->view->user = $user;
    }

    public function memberAction()
    {
    }

    public function eventAction()
    {
    }
}
