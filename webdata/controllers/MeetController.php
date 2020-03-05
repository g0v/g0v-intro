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

    public function channelAction()
    {
        list(, /*meet*/, /*channel*/, $event_id, $channel_id) = explode('/', $this->getURI());
        if (!$event = Event::find($event_id)) {
            return $this->alert("event not found {$event_id}", '/');
        }
        if (!$channel = Channel::find(intval($channel_id)) or $channel->event_id != $event_id) {
            return $this->alert("channel_id not found {$channel_id}", '/');
        }
        if (!$this->view->user) {
            return $this->alert("您需要登入才能進聊天室 You need to login first.", "/login?next=" . urlencode($this->getURI()));
        }
        $this->view->event = $event;
        $this->view->channel = $channel;
    }

    public function reportuserlistAction()
    {
        list(, /*meet*/, /*reportuserlist*/, $event_id, $channel_id) = explode('/', $this->getURI());
        if (!$event = Event::find($event_id)) {
            return $this->alert("event not found {$event_id}", '/');
        }
        if (!$channel = Channel::find(intval($channel_id)) or $channel->event_id != $event_id) {
            return $this->alert("channel_id not found {$channel_id}", '/');
        }
        $users = json_decode($_POST['data']);
        $channel->getStatus()->updateData(array(
            'user_reported_by' => $this->view->user->slack_id,
            'user_reported_at' => time(),
            'user_list' => $users,
        ));
        return $this->json(true);
    }

    public function dataAction()
    {
        list(, /*meet*/, /*channel*/, $event_id, $channel_id) = explode('/', $this->getURI());
        if (!$event = Event::find($event_id)) {
            return $this->alert("event not found {$event_id}", '/');
        }
        if (!$channel = Channel::find(intval($channel_id)) or $channel->event_id != $event_id) {
            return $this->alert("channel_id not found {$channel_id}", '/');
        }
        $ret = new StdClass;
        $ret->data = $channel->getData();
        $ret->meta = $channel->getStatus()->getMeta();
        $ret->status = $channel->getStatus()->getData();
        $ret->user = new StdClass;
        $ret->user->slack_id = $this->view->user->slack_id;
        $ret->user->avatar = $this->view->user->getImage();
        $ret->user->account = $this->view->user->account;
        return $this->json($ret);
    }
}
