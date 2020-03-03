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
        if (array_key_exists('event_id', $_GET) and $event = Event::find(strval($_GET['event_id']))) {
            $this->view->event = $event;
        }
    }

    public function editeventAction()
    {
        if ($_POST['sToken'] != Session::getStoken()) {
            return $this->alert('sToken error', '/admin/event');
        }
        if (array_key_exists('event_id', $_GET)) {
            if (!$event = Event::find(strval($_GET['event_id']))) {
                return $this->alert("event {$_GET['event_id']} not found", '/admin/event');
            }
            $event->update(array(
                'id' => $_POST['id'],
                'name' => $_POST['name'],
                'status' => intval($_POST['status']),
            ));
        } else {
            $event = Event::insert(array(
                'id' => $_POST['id'],
                'name' => $_POST['name'],
                'status' => intval($_POST['status']),
            ));
        }
        return $this->alert('ok', '/admin/event?event_id' . urlencode($event->id));
    }
}
