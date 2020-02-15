<?php

class LoginController extends Pix_Controller
{
    public function init()
    {
        if (Pix_Session::get('user_name')) {
            $user = new StdClass;
            $user->name = Pix_Session::get('user_name');
            $user->id = Pix_Session::get('user_id');
            $this->view->user = $user;
        }
    }
    public function indexAction()
    {
        $client_id = getenv('SLACK_CLIENT_ID');
        $redirect_uri = 'https://' . getenv('SLACK_CALLBACK_HOST') . '/login/callback';

        $url = sprintf("https://g0v-tw.slack.com/oauth/authorize?client_id=%s&scope=%s&redirect_uri=%s&state=%s&team=%s",
            urlencode($client_id), // client_id
            'identity.basic,identity.avatar', // scope
            urlencode($redirect_uri), // redirect_uri
            $_GET['next'], // state
            "" // team
        );
        return $this->redirect($url);
    }

    public function callbackAction()
    {
        $client_id = getenv('SLACK_CLIENT_ID');
        $client_secret = getenv('SLACK_CLIENT_SECRET');
        $redirect_uri = 'https://' . getenv('SLACK_CALLBACK_HOST') . '/login/callback';
        if (!$code = $_GET['code']) {
            return $this->alert("Error", '/');
        }

        $url = "https://slack.com/api/oauth.access";
        $url .= "?client_id=" . urlencode($client_id);
        $url .= "&client_secret=" . urlencode($client_secret);
        $url .= "&code=" . urlencode($code);
        $url .= "&redirect_uri=" . urlencode($redirect_uri);
        $obj = json_decode(file_get_contents($url));
        if (!$obj->ok) {
            return $this->alert($obj->error, '/');
        }
        $next = $_GET['state'];
        $access_token = $obj->access_token;
        $user_id = $obj->user_id;
        $url = sprintf('https://slack.com/api/users.identity?token=%s', urlencode($access_token));
        $obj = json_decode(file_get_contents($url));
        if (!$obj->ok) {
            return $this->alert($obj->error, '/');
        }

        Pix_Session::set('user_name', $obj->user->name);
        Pix_Session::set('image', $obj->user->image_512);

        $url = sprintf('https://slack.com/api/users.info?token=%s&user=%s', urlencode(getenv('SLACK_ACCESS_TOKEN')), urlencode($user_id));
        $obj = json_decode(file_get_contents($url));
        if (!$obj->ok) {
            return $this->alert($obj->error, '/');
        }
        $account = $obj->user->name;

        Pix_Session::set('user_id', $user_id);
        Pix_Session::set('account', $account);
		Pix_Session::set('access_token', $access_token);

        return $this->redirect($next);
    }

    public function logoutAction()
    {
        Pix_Session::set('user_id', '');
        Pix_Session::set('user_name', '');
        Pix_Session::set('access_token', '');
        Pix_Session::set('image', '');
        if ($_GET['next']) {
            return $this->redirect($_GET['next']);
        } else {
            return $this->redirect('/');
        }
    }
}
