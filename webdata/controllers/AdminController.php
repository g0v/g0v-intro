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

    public function channelAction()
    {
        if (!array_key_exists('event_id', $_GET) or !$event = Event::find(strval($_GET['event_id']))) {
            return $this->alert("event not found", "/admin/event");
        }

        $this->view->event = $event;
        if (array_key_exists('channel_id', $_GET) and $channel = Channel::search(array('event_id' => $event->id, 'channel_id' => intval($_GET['channel_id'])))->first()) {
            $this->view->channel = $channel;
        }
    }

    public function editchannelAction()
    {
        if ($_POST['sToken'] != Session::getStoken()) {
            return $this->alert('sToken error', '/admin/event');
        }
        if (!array_key_exists('event_id', $_GET) or !$event = Event::find(strval($_GET['event_id']))) {
            return $this->alert('event not found', '/admin/event');
        }
        if (array_key_exists('channel_id', $_GET)) {
            if (!$channel = Channel::find(intval($_GET['channel_id']))) {
                return $this->alert("channel not found", '/admin/channel?event_id=' . urlencode($event->event_id));
            }
            $channel->update(array(
                'name' => $_POST['name'],
            ));
        } else {
            $order = Channel::search(array('event_id' => $event->id))->max('order')->order + 1;
            $channel = Channel::insert(array(
                'id' => $_POST['id'],
                'event_id' => $event->id,
                'name' => $_POST['name'],
                'status' => intval($_POST['status']),
                'data' => '{}',
                'order' => $order,
            ));
        }
        $status = $channel->getStatus();
        $status->updateMeta(array(
            'title' => $_POST['title'],
            'hackmd' => $_POST['hackmd'],
            'jitsi_room' => $_POST['jitsi_room'],
        ));
        $channel->updateData(array(
            'owners' => User::parseUsers($_POST['owners']),
            'invite_list' => User::parseUsers($_POST['invite_list']),
            'type' => intval($_POST['type']),
        ));
        return $this->alert('ok', '/admin/channel?event_id=' . urlencode($event->id) . '&channel_id=' . intval($channel->channel_id));
    }

    public function channelorderAction()
    {
        if ($_POST['sToken'] != Session::getStoken()) {
            return $this->alert('sToken error', '/admin/event');
        }
        if (!array_key_exists('channel_id', $_GET) or !$channel = Channel::find($_GET['channel_id'])) {
            return $this->alert('channel not found', '/admin/event');
        }
        $channels1 = array();
        $channels2 = array();

        foreach (Channel::search(array('event_id' => $channel->event_id))->order('order ASC') as $current_channel) {
            if ($current_channel->channel_id == $channel->channel_id) {
                continue;
            }
            if ($current_channel->order < $_POST['order']) {
                $channels1[] = $current_channel;
            } else {
                $channels2[] = $current_channel;
            }
        }
        foreach (array_merge($channels1, array($channel), $channels2) as $order => $c) {
            if ($c->order != $order) {
                $c->update(array(
                    'order' => $order,
                    'updated_at' => time(),
                ));
            }
        }

        return $this->alert('ok', "/admin/channel?event_id=" . urlencode($channel->event_id));
    }
}
