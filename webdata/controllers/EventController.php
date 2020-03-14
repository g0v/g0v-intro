<?php

class EventController extends Pix_Controller
{
    public function init()
    {
        if ($user_id = Pix_Session::get('user_id') and $user = User::find($user_id)) {
            $this->view->user = $user;
        }
    }

    public function showAction()
    {
        list(, /*event*/, /*show*/, $id) = explode('/', $this->getURI());
        if (!$event = Event::find(strval($id))) {
            return $this->redirect('/');
        }
        $this->view->event = $event;
        if ($this->view->user) {
            $this->view->intro = Intro::search(array('event' => $event->id, 'created_by' => $this->view->user->slack_id))->first();
            if ($this->view->intro) {
                $this->view->intro_voice = IntroVoice::find($this->view->intro->id);
            }
        }
    }

    public function downloadcsvAction()
    {
        list(, /*event*/, /*downloadcsv*/, $id) = explode('/', $this->getURI());
        if (!$event = Event::find(strval($id))) {
            return $this->alert("{$id} not found", '/');
        }

        $output = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $id . '.csv"');
        fputcsv($output, array(
            'slack帳號', '顯示名稱', '關鍵字', '建立時間', '頭像位置', '自介錄音',
        ));
        foreach (Intro::search(array('event' => $id))->order('created_at ASC') as $intro) {
            $data = json_decode($intro->data);
            fputcsv($output, array(
                $intro->user->account,
                $intro->user->getDisplayName(),
                $data->keyword,
                date('c', $intro->created_at),
                $intro->user->getImage(),
                $data->voice_path,
            ));
        }
        return $this->noview();
    }

    public function dataAction()
    {
        list(, /*event*/, /*data*/, $id) = explode('/', $this->getURI());
        if (!$event = Event::find(strval($id))) {
            return $this->alert("{$id} not found", '/');
        }

        $ret = array();
        foreach (Intro::search(array('event' => $id))->order('created_at ASC') as $intro) {
            $data = json_decode($intro->data);
            $obj = new StdClass;
            $obj->created_at = $intro->created_at;
            $obj->keyword = $data->keyword;
            $obj->voice_path = $data->voice_path;
            $user = User::find($intro->created_by);
            $obj->display_name = $user->getDisplayName();
            $obj->avatar = $user->getImage();
            $obj->account = $user->account;
            $ret[] = $obj;
        }
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        return $this->json($ret);
    }


    public function saveintroAction()
    {
        list(, /*event*/, /*saveintro*/, $id) = explode('/', $this->getURI());
        if (!$event = Event::find(strval($id))) {
            return $this->alert("{$id} not found", '/');
        }

        if (!$this->view->user) {
            return $this->alert("must login", '/');
        }

        $data = array(
            'keyword' => $_POST['keyword'],
        );

        if ($_POST['record_data']) {
            if (strpos($_POST['record_data'], 'no-change:') === 0) {
                $data['voice_path'] = explode(':', $_POST['record_data'], 2)[1];
                $data['voice_length'] = intval($_POST['record_length']);
            } else {
                $tmpfile = tempnam('/tmp', 'tmp-file');
                file_put_contents($tmpfile, base64_decode($_POST['record_data']));
                $cmd = sprintf("ffmpeg -i %s %s", escapeshellarg($tmpfile), escapeshellarg($tmpfile . '.mp3'));
                system($cmd, $return_var);
                unlink($tmpfile);
                if ($return_var) {
                    throw new Exception("失敗");
                }
                $path = date('Ymd') . '/' . $this->view->user->slack_id . '-' . crc32(uniqid()) . '.mp3';
                include(__DIR__ . '/../stdlibs/aws/aws-autoloader.php');
                $s3 = new Aws\S3\S3Client([
                    'region' => 'ap-northeast-1',
                    'version' => 'latest',
                ]);
                $s3->putObject([
                    'Bucket' => 'g0v-intro',
                    'Key' => $path,
                    'Body' => file_get_contents($tmpfile . '.mp3'),
                    'ACL' => 'public-read',
                    'ContentType' => 'audio/mpeg',
                    'CacheControl' => 'max-age=31536000,public'
                ]);
                unlink($tmpfile . '.mp3');
                $data['voice_path'] = $path;
                $data['voice_length'] = intval($_POST['record_length']);
            }
        }


        if ($intro = Intro::search(array('event' => $id, 'created_by' => $this->view->user->slack_id))->first()) {
            $intro->update(array(
                'data' => json_encode($data),
            ));
        } else {
            $intro = Intro::insert(array(
                'event' => $id,
                'created_at' => time(),
                'created_by' => $this->view->user->slack_id,
                'data' => json_encode($data),
            ));
        }

        return $this->alert("自介儲存成功", "/event/show/{$id}");
    }

    public function slideAction()
    {
        list(, /*event*/, /*slide*/, $event_id) = explode('/', $this->getURI());
        $this->view->api = '/event/data/' . $event_id;
    }

    public function sliderunAction()
    {
        list(, /*event*/, /*sliderun*/, $event_id) = explode('/', $this->getURI());
        $this->view->api = '/event/data/' . $event_id;
    }

    public function userinfoAction()
    {
        list(, /*event*/, /*userinfo*/, $event_id) = explode('/', $this->getURI());
        $users = explode(',', trim($_GET['users']));
        $ret = new StdClass;
        $ret->error = false;
        $ret->data = new StdClass;
        if ($users) {
            foreach (User::search(1)->searchIn('slack_id', $users) as $user) {
                $ret->data->{$user->slack_id} = new StdClass;
                $ret->data->{$user->slack_id}->display_name = $user->getDisplayName();
                $ret->data->{$user->slack_id}->avatar = $user->getImage();
                $ret->data->{$user->slack_id}->account = $user->account;
            }
            foreach (Intro::search(array('event' => $event_id))->searchIn('created_by', $users) as $intro) {
                $data = json_decode($intro->data);
                foreach (array('keyword', 'voice_path', 'voice_length') as $k) {
                    if (property_exists($data, $k)) {
                        $ret->data->{$intro->created_by}->{$k} = $data->{$k};
                    }
                }
            }
        }
        return $this->json($ret);
    }
}
