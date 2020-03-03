<?php

class MeetController extends Pix_Controller
{
    public function init()
    {
        if ($user_id = Pix_Session::get('user_id') and $user = User::find($user_id)) {
            $this->view->user = $user;
        }
    }

    public function showAction()
    {
        list(, /*meet*/, /*show*/, $event_id) = explode('/', $this->getURI());
        if (!$event = Event::find($event_id)) {
            return $this->alert("event not found {$event_id}", '/');
        }
        $this->view->event = $event;
    }
}
