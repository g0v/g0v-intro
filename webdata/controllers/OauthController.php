<?php

class OauthController extends Pix_Controller
{
    public function init()
    {
        if ($user_id = Pix_Session::get('user_id') and $user = User::find($user_id)) {
            $this->view->user = $user;
        }
    }

    protected function errorReturn($values)
    {
        header('HTTP/1.1 400 Bad Request', true, 400);

        return $this->json($values);
    }

    public function accesstokenAction()
    {
        if ($_SERVER['HTTP_ORIGIN']) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        }
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, x-requested-with');
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            return $this->json('');
        }

        $client_id = $_REQUEST['client_id'];
        $code = $_REQUEST['code'];
        $redirect_uri = $_REQUEST['redirect_uri'];
        $client_secret = $_REQUEST['client_secret'];
        $grant_type = $_REQUEST['grant_type'];

        if ($grant_type != 'authorization_code') {
            return $this->errorReturn(array(
                'error' => 'unsupported_grant_type',
                'error_reason' => 'grant_type must be authorization_code',
            ));
        }

        if (!$client_id or !$app = OAuthApp::find(intval($client_id))) {
            return $this->errorReturn(array(
                'error' => 'invalid_client',
                'error_reason' => 'app not found',
            ));
        }

        $session_code = OAuthSessionCode::search(array(
            'app_id' => intval($client_id),
            'code' => strval($code),
        ))->first();

        if (!$session_code) {
            return $this->errorReturn(array(
                'error' => 'invalid_grant',
                'error_reason' => '找不到代碼 code not found',
            ));
        }

        if ($session_code->getData()->code_challenge_method) {
            // PKCE https://tools.ietf.org/html/rfc7636
            if ($session_code->getData()->code_challenge_method == 'plain') {
                if ($session_code->getData()->code_challenge != $_GET['code_verifier']) {
                    return $this->errorReturn(array(
                        'error' => 'invalid_request',
                        'error_reason' => 'wrong code_verifier',
                    ));
                }
            } else if ($session_code->getData()->code_challenge_method == 'S256') {
                if (rtrim(strtr(base64_encode(hex2bin(hash('sha256', $_GET['code_verifier']))), '+/', '-_'), '=') != $session_code->getData()->code_challenge) {
                    return $this->errorReturn($redirect_uri, array(
                        'error' => 'invalid_request',
                        'error_reason' => 'wrong code_verifier',
                    ));
                };
            } else {
                return $this->errorReturn(array(
                    'error' => 'invalid_request',
                    'error_reason' => 'unknown code_challenge_method',
                ));
            }
        }

        if ($session_code->getData()->redirect_uri != $redirect_uri) {
            return $this->errorReturn(array(
                'error' => 'invalid_request',
                'error_reason' => 'redirect_uri is wrong',
            ));
        }

        $slack_id = $session_code->slack_id;
        $session_code->delete();

        $access_token = OAuthSession::getNewAccessToken();
        OAuthSession::insert(array(
            'access_token' => $access_token,
            'app_id' => intval($client_id),
            'slack_id' => strval($slack_id),
            'created_at' => time(),
            'data' => '{}',
        ));

        return $this->json(array(
            'access_token' => $access_token,
        ));
    }

    public function authAction()
    {
        $client_id = $_GET['client_id'];
        $redirect_uri = $_GET['redirect_uri'];
        $response_type = $_GET['response_type'];
        $scope = $_GET['scope'];
        $state = $_GET['state'];
        $code_challenge = $_GET['code_challenge'];
        $code_challenge_method = $_GET['code_challenge_method'];

        if (!$redirect_uri) {
            return $this->alert("no redirect_uri", "/oauth");
        }
        if (strpos($redirect_uri, '?')) {
            $sep = '&';
        } else {
            $sep = '?';
        }
        if ($state) {
            $redirect_uri .= $sep . 'state=' . urlencode($state);
            $sep = '&';
        }

        if (!$client_id or !$app = OAuthApp::find(intval($client_id))) {
            return $this->redirect($redirect_uri . $sep . 'error=unauthorized_client&error_description=' . urlencode("client_id not found"));
        }

        if ($response_type != 'code') {
            return $this->redirect($redirect_uri . $sep . 'error=unsupported_response_type&error_description=' . urlencode("response_type must be code"));
        }

        if ($app->getData()->redirect_urls) {
            if (!in_array($_GET['redirect_uri'], $app->getData()->redirect_urls)) {
                return $this->redirect($redirect_uri . $sep . 'error=invalid_request&error_description=' . urlencode("redirect_uri is not in redirect urls"));
            }
        }

        if (!$this->view->user) {
            return $this->redirect("/login?next=" . urlencode($_SERVER['REQUEST_URI']));
        }

        // clean old code
        OAuthSessionCode::search(array('app_id' => intval($client_id)))->search("created_at < " . time() - 3600)->delete();

        $code = OAuthSessionCode::insert(array(
            'app_id' => intval($client_id),
            'slack_id' => $this->view->user->slack_id,
            'code' => Helper::uniqid(16),
            'data' => json_encode(array(
                'code_challenge' => strval($code_challenge),
                'code_challenge_method' => strval($code_challenge_method),
                'redirect_uri' => $_GET['redirect_uri'],
            )),
            'created_at' => time(),
        ));


        $redirect_uri .= $sep . 'code=' . urlencode($code->code);
        return $this->redirect($redirect_uri);
    }

    public function indexAction()
    {
        if (!$this->view->user) {
            return $this->alert("需要登入 You need to login first", "/login?next=/oauth");
        }
    }

    public function addappAction()
    {
        if (!$this->view->user) {
            return $this->alert("需要登入 You need to login first", "/login?next=/oauth");
        }
        if (!$_POST['sToken'] or $_POST['sToken'] != Session::getStoken()) {
            return $this->alert("sToken error", "/oauth");
        }

        $client_id = OAuthApp::getNewID();
        $app = OAuthApp::insert(array(
            'client_id' => $client_id,
            'created_at' => time(),
            'created_by' => $this->view->user->slack_id,
            'data' => json_encode(array(
                'name' => strval($_POST['name']),
                'document' => strval($_POST['doc']),
                'client_secret' => Helper::uniqid(32),
            )),
        ));

        return $this->alert("OK", "/oauth/app?id=" . $app->client_id);
    }

    public function updateappAction()
    {
        if (!$this->view->user) {
            return $this->alert("需要登入 You need to login first", "/login?next=/oauth");
        }
        if (!$_POST['sToken'] or $_POST['sToken'] != Session::getStoken()) {
            return $this->alert("sToken error", "/oauth");
        }

        if (!$app = OAuthApp::find($_GET['id'])) {
            return $this->alert("app not found", "/oauth");
        }
        $app->updateData(array(
            'name' => strval($_POST['name']),
            'document' => strval($_POST['doc']),
            'redirect_urls' => array_values(array_filter($_POST['redirect_urls'], 'strlen')),
        ));

        return $this->alert("OK", "/oauth/app?id=" . $app->client_id);
    }

    public function appAction()
    {
        if (!$this->view->user) {
            return $this->alert("需要登入 You need to login first", "/login?next=/oauth");
        }
        if (!$app = OAuthApp::find($_GET['id'])) {
            return $this->alert("App not found", "/oauth");
        }
        if ($app->created_by != $this->view->user->slack_id) {
            return $this->alert("App not found", "/oauth");
        }
        $this->view->app = $app;
    }
}
