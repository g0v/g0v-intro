<?php

class ApiController extends Pix_Controller
{
    public function init()
    {
        if ($_SERVER['HTTP_ORIGIN']) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        }
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
            $intros[] = array(
                'event' => $intro->event,
                'keyword' => $data->keyword,
                'voice_path' => $data->voice_path,
                'created_at' => date('c', $intro->created_at),
            );
        }

        return $this->json(array(
            'error' => false,
            'data' => array(
                'slack_id' => $user->slack_id,
                'account' => $user->account,
                'display_name' => $user->getDisplayName(),
                'avatar' => $user->getImage(),
                'intros' => $intros,
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
}
