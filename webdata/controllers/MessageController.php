<?php

class MessageController extends Pix_Controller
{
    public function callbackAction()
    {
        $data = json_decode(file_get_contents('php://input'));
        if (!$data or !property_exists($data, 'type')) {
            return $this->json(0);
        }

        if ($data->type == 'url_verification') {
            return $this->json(array('challenge' => $data->challenge));
        }

        if ($data->type == 'event_callback') {
            $event = $data->event;
            if ($event->type != 'message') {
                return $this->json(0);
            }

            if (!User::find($event->user)) {
                User::fetchUser($event->user);
            }

            MessageLog::insert(array(
                'channel' => $event->channel,
                'ts' => $event->ts,
                'data' => json_encode(array(
                    'user' => $event->user,
                    'text' => $event->text,
                )),
			));
        }

        return $this->json(1);
    }

    public function getmessageAction()
    {
        $channel_id = $_GET['channel'];
        if (!$channel_id) {
            return $this->json(0);
        }

        $after = floatval($_GET['after']);
        $ret = new StdClass;
        $ret->messages = array();
        if (!$after) {
            $messages = MessageLog::search(array('channel' => $channel_id))->order('ts DESC')->limit(3);
        } else {
            $messages = MessageLog::search(array('channel' => $channel_id))->order('ts DESC')->search('ts > ' . $after)->limit(3);
        }
        foreach ($messages as $message) {
            $data = json_decode($message->data);
            $data->user = User::find($data->user)->account;
            $ret->messages[] = $data;
            $after = max($after, $message->ts);
        }
        $ret->next_url = '/message/getmessage?channel=' . urlencode($channel_id) . '&after=' . $after;
        return $this->json($ret);
    }
}
