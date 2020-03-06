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

    public function eventdataAction()
    {
        $start = microtime(true);
        list(, /*meet*/, /*eventdata*/, $event_id) = explode('/', $this->getURI());
        if (!$event = Event::find($event_id)) {
            return $this->alert("event not found {$event_id}", '/');
        }

        $users = array();

        $ret = new StdClass;
        $ret->spent = 0;
        $ret->error = false;

        $ret->event = new STdClass;
        $ret->event->id = $event->id;
        $ret->event->name = $event->name;
        $ret->event->data = $event->GetData();

        $ret->channels = array();
        $ret->users = array();
        foreach (Channel::search(array('event_id' => $event->id)) as $channel) {
            if (!$channel->canSee($this->view->user)) {
                continue;
            }
            $ret->channels[$channel->channel_id] = array(
                'channel_id' => $channel->channel_id,
                'data' => $channel->getData(),
                'order' => $channel->order,
                'name' => $channel->name,
            );
            foreach ($ret->channels[$channel->channel_id]['data']->owners as $id) {
                $users[$id] = true;
            }
            foreach ($ret->channels[$channel->channel_id]['data']->invite_list as $id) {
                $users[$id] = true;
            }
        }

        if ($ret->channels) {
            foreach (ChannelStatus::search(1)->searchIn('channel_id', array_keys($ret->channels)) as $channel_status) {
                $ret->channels[$channel_status->channel_id]['meta'] = $channel_status->getMeta();
                $ret->channels[$channel_status->channel_id]['status'] = $channel_status->getData();
                if ($ret->channels[$channel_status->channel_id]['status']->user_reported_at < time() - 5 * 60) {
                    $ret->channels[$channel_status->channel_id]['status']->user_list = array();
                } elseif (!property_exists($ret->channels[$channel_status->channel_id]['status'], 'user_list')) {
                    $ret->channels[$channel_status->channel_id]['status']->user_list = array();
                }

                foreach ($ret->channels[$channel_status->channel_id]['status']->user_list as $list) {
                    $users[$list[0]] = true;
                }
            }
        }

        if ($users) {
            foreach (User::search(1)->searchIn('slack_id', array_keys($users)) as $user) {
                $ret->users[$user->slack_id] = array(
                    'slack_id' => $user->slack_id,
                    'account' => $user->account,
                    'display_name' => $user->getDisplayName(),
                    'avatar' => $user->getImage(),
                );
            }
            foreach (Intro::search(array('event' => $event->id))->searchIn('created_by', array_keys($users)) as $intro) {
                $ret->users[$intro->created_by]['intro'] = json_decode($intro->data);
            }
        }

        $ret->channels = array_values($ret->channels);
        $ret->users = array_values($ret->users);
        $ret->spent = microtime(true) - $start;
        return $this->json($ret);
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
        $intro = Intro::search(array('event' => $event_id, 'created_by' => $ret->user->slack_id))->first();
        if ($intro) {
            $data = json_decode($intro->data);
            $ret->user->keyword = $data->keyword;
        } else {
            $ret->user->keyword = '';
        }
        return $this->json($ret);
    }
}
