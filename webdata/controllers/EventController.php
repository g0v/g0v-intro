<?php

class EventController extends Pix_Controller
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

    public function showAction()
    {
        list(, /*event*/, /*show*/, $id) = explode('/', $this->getURI());
        if (!$event = Event::find(strval($id))) {
            return $this->redirect('/');
        }
        $this->view->event = $event;
        if ($this->view->user) {
            $this->view->intro = Intro::search(array('event' => $event->id, 'created_by' => $this->view->user->id))->first();
            if ($this->view->intro) {
                $this->view->intro_voice = IntroVoice::find($this->view->intro->id);
            }
        }
    }

    public function getintrovoiceAction()
    {
        if (!$intro_voice = IntroVoice::find(intval($_GET['id']))) {
            return $this->json(false);
        }
        return $this->json(array(
            'data' => $intro_voice->voice,
        ));
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
            'display_name' => $_POST['display_name'],
            'account' => $_POST['account'],
            'keyword' => $_POST['keyword'],
            'avatar' => $_POST['avatar'],
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
                $path = date('Ymd') . '/' . $this->view->user->id . '-' . crc32(uniqid()) . '.mp3';
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
                ]);
                unlink($tmpfile . '.mp3');
                $data['voice_path'] = $path;
                $data['voice_length'] = intval($_POST['record_length']);
            }
        }


        if ($intro = Intro::search(array('event' => $id, 'created_by' => $this->view->user->id))->first()) {
            $intro->update(array(
                'data' => json_encode($data),
            ));
        } else {
            $intro = Intro::insert(array(
                'event' => $id,
                'created_at' => time(),
                'created_by' => $this->view->user->id,
                'data' => json_encode($data),
            ));
        }

        return $this->alert("自介儲存成功", "/event/show/{$id}");
    }
}
