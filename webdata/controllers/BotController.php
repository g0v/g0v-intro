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

        if (array_key_exists('id', $_GET)) {
            if (!$bot = BotToken::find(intval($_GET['id'])) or $bot->owner != $this->view->user->slack_id) {
                return $this->alert('bot not found', '/bot');
            }
            $bot->update([
                'data' => json_encode([
                    'name' => strval($_POST['name']),
                    'purpose' => strval($_POST['purpose']),
                    'channels' => strval($_POST['channels']),
                    'displayname' => strval($_POST['displayname']),
                ]),
            ]);
            $text = sprintf("%s(%s) 更新機器人資料，機器人名稱=%s, 機器人目的=%s, 機器人發言頻道=%s, 機器人顯示名稱=%s", $this->view->user->getDisplayName(), $this->view->user->slack_id, $_POST['name'], $_POST['purpose'], $_POST['channels'], $_POST['displayname']);
        } else {
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
            $text = sprintf("%s(%s) 新增機器人，機器人名稱=%s, 機器人目的=%s, 機器人發言頻道=%s, 機器人顯示名稱=%s", $this->view->user->getDisplayName(), $this->view->user->slack_id, $_POST['name'], $_POST['purpose'], $_POST['channels'], $_POST['displayname']);
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, sprintf('https://slack.com/api/chat.postMessage?token=%s&channel=%s&username=%s', urlencode(getenv('SLACK_ACCESS_TOKEN')), urlencode('#jothonbot-log'), urlencode('揪松機器人記錄')));
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'text=' . urlencode($text));
        curl_exec($curl);
        return $this->redirect('/bot/?id=' . $bot->token_id);
    }
}
