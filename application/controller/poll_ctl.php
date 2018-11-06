<?php

class Poll_CTL extends Controller {

    private $poll_mdl;

    public function __construct() {
        parent::__construct();

        $this->poll_mdl = $this->load->model('poll');
    }

    public function p_c_delete_poll() {
        if ($this->session !== FALSE) {
            if (val_input_get('three', VI_NUMERIC, 10, FALSE)) {
                $data['user_key'] = $this->session['u_key'];
                $data['key'] = get('three');
                $r = $this->poll_mdl->p_m_delete_poll($data);
                if ($r === 0) {
                    create_msj('Los datos se actualiarón exitosamente', MSG_SUCCESS);
                } else {
                    create_msj('Ocurrio un error al actualizar los datos', MSG_DANGER);
                }
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
            }
            header('Location:' . WEB_PATH . 'session/dashboard');
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function p_c_edit_poll_form() {
        if ($this->session !== FALSE) {
            if (val_input_post('title', VI_STRING, 250, FALSE) and val_input_post('description', VI_STRING, 500, TRUE) and val_input_post('position', VI_NUMERIC, 6, FALSE)) {
                $data['user_key'] = $this->session['u_key'];
                $data['title'] = post('title');
                $data['description'] = post('description');
                $data['position'] = post('position');
                $data['key'] = post('key');
                $r = $this->poll_mdl->p_m_update_poll($data);
                if ($r === 0) {
                    create_msj('Los datos se actualiarón exitosamente', MSG_SUCCESS);
                } else {
                    create_msj('Ocurrio un error al actualizar los datos', MSG_DANGER);
                }
                header('Location:' . WEB_PATH . 'session/dashboard');
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                header('Location:' . WEB_PATH . 'session/dashboard');
            }
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function p_c_edit_poll() {
        if ($this->session !== FALSE) {
            if (val_input_get('three', VI_NUMERIC, 10, FALSE)) {
                $a = $this->session;
                $a['title'] = 'Editar encuesta';
                $a['msg'] = read_msjs();
                $a['p'] = $this->poll_mdl->p_m_get_poll(get('three'));
                $this->load->view('session/poll/poll', $a);
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                header('Location:' . WEB_PATH . 'session/dashboard');
            }
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function p_c_new_poll_form() {
        if ($this->session !== FALSE) {
            $data['key'] = $this->session['u_key'];
            if (val_input_post('title', VI_STRING, 250, FALSE) and val_input_post('description', VI_STRING, 500, TRUE) and val_input_post('position', VI_NUMERIC, 6, FALSE)) {
                $data['title'] = post('title');
                $data['description'] = post('description');
                $data['position'] = post('position');
                $r = $this->poll_mdl->p_m_new_poll($data);
                if ($r === 0) {
                    create_msj('Los datos se guardaron exitosamente', MSG_SUCCESS);
                } else {
                    create_msj('Ocurrio un error al guardar los datos', MSG_DANGER);
                }
                header('Location:' . WEB_PATH . 'session/dashboard');
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                header('Location:' . WEB_PATH . 'session/dashboard');
            }
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function p_c_new_poll() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Nueva encuesta';
            $a['msg'] = read_msjs();
            $this->load->view('session/poll/poll', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function p_c_dashboard() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Tablero - Encuestas';
            $a['polls'] = $this->poll_mdl->p_m_get_polls($this->session['u_key']);
            $a['msg'] = read_msjs();
            $this->load->view('session/poll/dashboard', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

}
