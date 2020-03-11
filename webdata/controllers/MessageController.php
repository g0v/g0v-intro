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
}
