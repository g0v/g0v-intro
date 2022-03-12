<?php

class BotController extends Pix_Controller
{
    public function init()
    {
        if (!$user_id = Pix_Session::get('user_id') or !$user = User::find($user_id)) {
            return $this->redirect("/login?next=" . urlencode($_SERVER['REQUEST_URI']));
        }
        $this->view->user = $user;
    }

    public function indexAction()
    {
        if (array_key_exists('id', $_GET) and $bot = BotToken::find(intval($_GET['id'])) and $bot->owner == $this->view->user->slack_id) {
            $this->view->token = $bot;
        }
    }

    public function addbotAction()
    {
        if ($_POST['sToken'] != Session::getStoken()) {
            return $this->alert('stoken error', '/bot');
        }
        $bot = BotToken::insert([
            'owner' => $this->view->user->slack_id,
            'created_at' => time(),
            'token' => Helper::uniqid(32),
            'data' => json_encode([
                'name' => strval($_POST['name']),
                'purpose' => strval($_POST['purpose']),
                'channels' => strval($_POST['channels']),
                'displayname' => strval($_POST['displayname']),
            ]),
        ]);
        return $this->redirect('/bot/?id=' . $bot->token_id);
    }
}
