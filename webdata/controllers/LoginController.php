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
            urlencode($_GET['next']), // state
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


        $url = sprintf('https://slack.com/api/users.info?token=%s&user=%s', urlencode(getenv('SLACK_ACCESS_TOKEN')), urlencode($user_id));
        $obj = json_decode(file_get_contents($url));
        if (!$obj->ok) {
            return $this->alert($obj->error, '/');
        }
        if ($obj->user->profile->display_name) {
            $account = $obj->user->profile->display_name;
        } elseif ($obj->user->profile->real_name) {
            $account = $obj->user->profile->real_name;
        } else {
            return $this->alert("error, account not found", "/");
        }

        if (!$u = User::find($user_id)) {
            $u = User::insert(array(
                'slack_id' => $user_id,
                'account' => $account,
                'type' => 0,
                'created_at' => time(),
                'logined_at' => time(),
                'data' => '',
            ));
        }
        $u->update(array(
            'logined_at' => time(),
            'data' => json_encode(array(
                'display_name' => $obj->user->real_name,
                'image' => $obj->user->profile->image_original ?: $obj->user->profile->image_512,
            )),
        ));

        Pix_Session::set('user_id', $user_id);

        return $this->redirect($next);
    }

    public function logoutAction()
    {
        Pix_Session::set('user_id', '');
        if ($_GET['next']) {
            return $this->redirect($_GET['next']);
        } else {
            return $this->redirect('/');
        }
    }
}
