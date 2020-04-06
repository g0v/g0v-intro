<?php

class ApiController extends Pix_Controller
{
    public function init()
    {
        if ($_SERVER['HTTP_ORIGIN']) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Methods: GET');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            if (in_array($_SERVER['HTTP_ORIGIN'], explode(',', getenv('ALLOW_CORS_ORIGIN')))) {
                if ($user_id = Pix_Session::get('user_id') and $user = User::find($user_id)) {
                    $this->view->user = $user;
                }
            }
        } else {
            if ($user_id = Pix_Session::get('user_id') and $user = User::find($user_id)) {
                $this->view->user = $user;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            return $this->json('');
        }

        if ($_SERVER['HTTP_AUTHORIZATION'] and strpos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer') === 0) {
            $access_token = explode(' ', $_SERVER['HTTP_AUTHORIZATION'], 2)[1];
            $session = OAuthSession::find($access_token);
            $this->view->user = User::find($session->slack_id);
        }
    }

    public function meAction()
    {
        if (!$user = $this->view->user) {

            header('HTTP/1.1 403 Bad Request', true, 403);
            return $this->json(array(
                'error' => true,
                'message' => 'need login',
            ));
        }

        $intros = array();
        foreach (Intro::search(array('created_by' => $user->slack_id)) as $intro) {
            $data = json_decode($intro->data);
            $intros[$intro->event] = array(
                'intro' => array(
                    'keyword' => $data->keyword,
                    'voice_path' => $data->voice_path,
                    'created_at' => date('c', $intro->created_at),
                ),
            );
        }

        if ($intros) {
            foreach (Event::search(1)->searchIn('id', array_keys($intros)) as $event) {
                $intros[$event->id]['event'] = array(
                    'name' => $event->name,
                    'id' => $event->id,
                    'intro_count' => count(Intro::search(array('event' => $event->id))),
                    'status' => $event->status,
                );
            }
        }

        return $this->json(array(
            'error' => false,
            'data' => array(
                'user' => array(
                    'slack_id' => $user->slack_id,
                    'account' => $user->account,
                    'display_name' => $user->getDisplayName(),
                    'avatar' => $user->getImage(),
                ),
                'intros' => array_values($intros),
            ),
        ));
    }

    protected function _subRouter()
    {
        list(, /*api*/, $main, $sub) = explode('/', $this->getURI());
        if (!is_callable(array($this, "{$main}_{$sub}Action"))) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return $this->json(array(
                'error' => true,
                'message' => "method not found: {$main}/{$sub}",
            ));
        }
        return $this->{"{$main}_{$sub}Action"}();
    }

    public function eventAction()
    {
        return $this->_subRouter();
    }

    public function event_listAction()
    {
        $ret = new StdClass;
        $ret->error = false;
        $ret->data = array();

        foreach (Event::search(1) as $event) {
            $ret->data[] = array(
                'name' => $event->name,
                'id' => $event->id,
                'intro_count' => count(Intro::search(array('event' => $event->id))),
                'status' => $event->status,
            );
        }
        return $this->json($ret);
    }

    public function event_introAction()
    {
        $ret = new StdClass;
        $ret->error = false;
        $ret->data = array();

        if (!$event = Event::find(strval($_GET['event_id']))) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return $this->json(array(
                'error' => true,
                'message' => "Event {$_GET['event_id']} not found",
            ));
        }

        foreach (Intro::search(array('event' => $event->id))->order('created_at ASC') as $intro) {
            $data=  json_Decode($intro->data);
            $ret->data[] = array(
                'user' => array(
                    'slack_id' => $intro->user->slack_id,
                    'account' => $intro->user->account,
                    'display_name' => $intro->user->getDisplayName(),
                    'avatar' => $intro->user->getImage(),
                ),
                'intro' => array(
                   'keyword' => $data->keyword,
                   'voice_path' => $data->voice_path,
                   'created_at' => date('c', $intro->created_at),
                ),
            );
        }

        return $this->json($ret);
    }

    public function event_channelAction()
    {
        $ret = new StdClass;
        $ret->error = false;
        $ret->data = new StdClass;

        if (!$event = Event::find(strval($_GET['event_id']))) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return $this->json(array(
                'error' => true,
                'message' => "Event {$_GET['event_id']} not found",
            ));
        }

        $ret->data->event = array(
            'seq' => $event->seq,
            'name' => $event->name,
            'id' => $event->id,
            'intro_count' => count(Intro::search(array('event' => $event->id))),
            'status' => $event->status,
            'data' => $event->getData(),
        );

        $ret->data->channels = array();
        $ret->data->users = array();

        foreach (Channel::search(array('event_id' => $event->id)) as $channel) {
            if (!$channel->canSee($this->view->user)) {
                continue;
            }
            $ret->data->channels[$channel->channel_id] = array(
                'channel_id' => intval($channel->channel_id),
                'data' => $channel->getData(),
                'order' => $channel->order,
                'name' => $channel->name,
            );
            foreach ($ret->data->channels[$channel->channel_id]['data']->owners as $id) {
                $users[$id] = true;
            }
            foreach ($ret->data->channels[$channel->channel_id]['data']->invite_list as $id) {
                $users[$id] = true;
            }
        }

        if ($ret->data->channels) {
            foreach (ChannelStatus::search(1)->searchIn('channel_id', array_keys($ret->data->channels)) as $channel_status) {
                $ret->data->channels[$channel_status->channel_id]['meta'] = $channel_status->getMeta();
                $ret->data->channels[$channel_status->channel_id]['status'] = $channel_status->getData();
                if ($ret->data->channels[$channel_status->channel_id]['status']->user_reported_at < time() - 5 * 60) {
                    $ret->data->channels[$channel_status->channel_id]['status']->user_list = array();
                } elseif (!property_exists($ret->data->channels[$channel_status->channel_id]['status'], 'user_list')) {
                    $ret->data->channels[$channel_status->channel_id]['status']->user_list = array();
                }

                foreach ($ret->data->channels[$channel_status->channel_id]['status']->user_list as $list) {
                    $users[$list[0]] = true;
                }
            }
        }

        if ($users) {
            foreach (User::search(1)->searchIn('slack_id', array_keys($users)) as $user) {
                $ret->data->users[$user->slack_id] = array(
                    'slack_id' => $user->slack_id,
                    'account' => $user->account,
                    'display_name' => $user->getDisplayName(),
                    'avatar' => $user->getImage(),
                );
            }
            foreach (Intro::search(array('event' => $event->id))->searchIn('created_by', array_keys($users)) as $intro) {
                $data = json_decode($intro->data);
                $ret->data->users[$intro->created_by]['intro'] = array(
                    'keyword' => $data->keyword,
                    'voice_path' => $data->voice_path,
                    'created_at' => date('c', $intro->created_at),
                );
            }
        }

        $ret->data->channels = array_values($ret->data->channels);
        $ret->data->users = array_values($ret->data->users);
        return $this->json($ret);
    }

    public function rpgAction()
    {
        return $this->_subRouter();
    }

    public function rpg_getroomAction()
    {
        if (!$room = RPGRoom::find_by_room_name(strval($_GET['room']))) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return $this->json(array(
                'error' => true,
                'message' => "room not found",
            ));
        }

        $ret = new StdClass;
        $ret->error = false;
        $ret->data = new StdClass;
        $ret->data->room_data = array(
            'updated_at' => intval($room->updated_at),
            'data' => json_decode($room->data),
        );
        $ret->data->objects = array();
        foreach (RPGRoomObject::search(array('room_id' => $room->room_id)) as $object) {
            $ret->data->objects[] = array(
                'object_id' => intval($object->room_object_id),
                'data' => json_decode($object->data),
            );
        }
        return $this->json($ret);
    }

    public function rpg_updateroomAction()
    {
        $now = time();
        if ($room = RPGRoom::find_by_room_name(strval($_GET['room']))) {
            $room->update(array(
                'updated_at' => $now,
                'data' => strval($_POST['data']),
            ));
        } else {
            RPGRoom::insert(array(
                'room_name' => strval($_GET['room']),
                'updated_at' => $now,
                'data' => strval($_POST['data']),
            ));
        }
        return $this->json(array(
            'error' => false,
            'updated_at' => $now,
        ));
    }

    public function rpg_addobjectAction()
    {
        if (!$room = RPGRoom::find_by_room_name(strval($_GET['room']))) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return $this->json(array(
                'error' => true,
                'message' => "room not found",
            ));
        }
        RPGRoomObject::insert(array(
            'room_id' => $room->room_id,
            'data' => strval($_POST['data']),
        ));
        $t = time();
        $room->update(array('updated_at' => $t));
        return $this->json(array(
            'error' => false,
            'updated_at' => $t,
        ));
    }

    public function rpg_deleteobjectAction()
    {
        if (!$room = RPGRoom::find_by_room_name(strval($_GET['room']))) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return $this->json(array(
                'error' => true,
                'message' => "room not found",
            ));
        }
        if (!$object = RPGRoomObject::search(array('room_id' => $room->room_id, 'room_object_id' => intval($_GET['room_object_id'])))) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return $this->json(array(
                'error' => true,
                'message' => "room not found",
            ));
        }
        $object->delete();
        $t = time();
        $room->update(array('updated_at' => $t));
        return $this->json(array(
            'error' => false,
            'updated_at' => $t,
        ));
    }

    public function rpg_updateobjectAction()
    {
        if (!$room = RPGRoom::find_by_room_name(strval($_GET['room']))) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return $this->json(array(
                'error' => true,
                'message' => "room not found",
            ));
        }
        if (!$object = RPGRoomObject::search(array('room_id' => $room->room_id, 'room_object_id' => intval($_GET['room_object_id'])))) {
            header('HTTP/1.1 404 Not Found', true, 404);
            return $this->json(array(
                'error' => true,
                'message' => "room not found",
            ));
        }
        $object->update(arraY(
            'data' => strval($_POST['data']),
        ));
        $t = time();
        $room->update(array('updated_at' => $t));
        return $this->json(array(
            'error' => false,
            'updated_at' => $t,
        ));
    }
}
