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

        if ($intro = Intro::search(array('event' => $id, 'created_by' => $this->view->user->id))->first()) {
            $intro->update(array(
                'data' => json_encode(array(
                    'display_name' => $_POST['display_name'],
                    'account' => $_POST['account'],
                    'keyword' => $_POST['keyword'],
                    'avatar' => $_POST['avatar'],
                    'has_voice' => $_POST['record_data'] ? 1 : 0,
                )),
            ));
        } else {
            $intro = Intro::insert(array(
                'event' => $id,
                'created_at' => time(),
                'created_by' => $this->view->user->id,
                'data' => json_encode(array(
                    'display_name' => $_POST['display_name'],
                    'account' => $_POST['account'],
                    'keyword' => $_POST['keyword'],
                    'avatar' => $_POST['avatar'],
                    'has_voice' => $_POST['record_data'] ? 1 : 0,
                )),
            ));
        }

        if ($_POST['record_data']) {
            if ($intro_voice = IntroVoice::find($intro->id)) {
                $intro_voice->update(array(
                    'voice' => strval($_POST['record_data']),
                    'length' => intval($_POST['record_length']),
                ));
            } else {
                IntroVoice::insert(array(
                    'id' => $intro->id,
                    'voice' => strval($_POST['record_data']),
                    'length' => intval($_POST['record_length']),
                ));
            }
        }

        return $this->alert("自介儲存成功", "/event/show/{$id}");
    }
}
