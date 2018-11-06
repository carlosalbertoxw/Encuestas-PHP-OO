<?php

class Answer_CTL extends Controller {

    private $answer_mdl;

    public function __construct() {
        parent::__construct();

        $this->answer_mdl = $this->load->model('answer');
    }

    public function a_c_add_answer_form() {
        if (val_input_post('stars', VI_NUMERIC, 1, FALSE) and val_input_post('comment', VI_STRING, 1000, TRUE) and val_input_post('poll_key', VI_NUMERIC, 10, FALSE) and val_input_post('user_key', VI_NUMERIC, 10, FALSE) and val_input_post('user', VI_URI, 25, FALSE)) {
            $data['stars'] = post('stars');
            $data['comment'] = post('comment');
            $data['poll_key'] = post('poll_key');
            $data['user_key'] = post('user_key');
            $r = $this->answer_mdl->a_m_add_answer($data);
            if ($r === 0) {
                create_msj('Los datos se guardaron exitosamente', MSG_SUCCESS);
            } else {
                create_msj('Ocurrio un error al guardar los datos', MSG_DANGER);
            }
        } else {
            create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
        }
        header('Location:' . WEB_PATH . post('user'));
    }

    public function a_c_add_answer() {
        if (val_input_get('three', VI_NUMERIC, 10, FALSE)) {
            $poll_mdl = $this->load->model('poll');
            $a['p'] = $poll_mdl->p_m_get_poll(get('three'));
            $a['title'] = 'Responder - ' . $a['p']['p_title'];
            $a['msg'] = read_msjs();
            $this->load->view('public/answer/poll_answer', $a);
        } else {
            create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
            header('Location:' . WEB_PATH);
        }
    }

    public function a_c_view_answers() {
        if ($this->session !== FALSE) {
            if (val_input_get('three', VI_NUMERIC, 10, FALSE)) {
                $poll_mdl = $this->load->model('poll');
                $a = $this->session;
                $a['poll'] = $poll_mdl->p_m_get_poll(get('three'));
                $a['answers'] = $this->answer_mdl->a_m_get_answers(get('three'));
                $a['title'] = 'Respuestas a - ' . $a['poll']['p_title'];
                $a['msg'] = read_msjs();
                $this->load->view('session/answer/answers', $a);
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                header('Location:' . WEB_PATH . 'session/dashboard');
            }
        } else {
            header('Location:' . WEB_PATH);
        }
    }

}
