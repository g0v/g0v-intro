<?php 

class IndexController extends Pix_Controller
{
    public function init()
    {
        if ($user_id = Pix_Session::get('user_id') and $user = User::find($user_id)) {
            $this->view->user = $user;
        }
    }

    public function indexAction()
    {
    }
}
