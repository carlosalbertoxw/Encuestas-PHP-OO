<?php

class Public_CTL extends Controller {

    private $public_mdl;

    public function __construct() {
        parent::__construct();

        $this->public_mdl = $this->load->model('public');

        $this->session = $this->session_validation();
    }

    public function p_c_profile() {
        if (val_input_get('one', VI_URI, 25, FALSE)) {
            $user = get('one');
            $r = $this->public_mdl->p_m_get_profile($user);
            if (count($r) == 6) {
                $a = $this->session;
                $a['title'] = $r['u_p_name'];
                $a['p'] = $r;
                $a['msg'] = read_msjs();
                $this->load->view('public/profile', $a);
            } else {
                $this->load->error_404();
            }
        } else {
            $this->load->error_404();
        }
    }

    public function p_c_sign_in_form() {
        if ($this->session === FALSE) {
            if (val_input_post('email', VI_EMAIL, 50, FALSE) and val_input_post('password', VI_STRING, 50, FALSE)) {
                $a['email'] = post('email');
                $a['password'] = post('password');
                $r = $this->public_mdl->p_m_get_user_by_email($a);
                if ($a['email'] != '' and $r['u_password'] != '' and $r['u_email'] == $a['email'] and $r['u_password'] == encrypt_password($a['password'])) {
                    $r['remember'] = post('remember') === 'on' ? post('remember') : FALSE;
                    create_session_cookie($r, $r['remember']);
                    header('Location:' . WEB_PATH . 'session/dashboard');
                } else {
                    create_msj('El correo electrónico o contraseña son incorrectos', MSG_DANGER);
                    header('Location:' . WEB_PATH);
                }
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                header('Location:' . WEB_PATH);
            }
        }
    }

    public function p_c_sign_up_form() {
        if ($this->session === FALSE) {
            if (val_input_post('email', VI_EMAIL, 50, FALSE) and val_input_post('password', VI_STRING, 50, FALSE) and filter_input(INPUT_POST, 'password') === filter_input(INPUT_POST, 're_password')) {
                $a['email'] = post('email');
                $a['password_key'] = random_string(50);
                $a['password'] = encrypt_password(post('password'));
                $r = $this->public_mdl->p_m_sign_up($a);
                if ($r == 0) {
                    create_msj('La cuenta se creó exitosamente', MSG_SUCCESS);
                } elseif (strpos($r, '1062') !== FALSE) {
                    create_msj('<strong>El correo ingresado ya pertenece a una cuenta.</strong> para restablecer la contraseña haga <a class="alert-link" href="' . WEB_PATH . 'reset-password">clic aquí</a>', 'danger');
                } else {
                    create_msj('Ocurrió un error al guardar los datos', MSG_DANGER);
                }
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
            }
        }
        header('Location:' . WEB_PATH);
    }

    public function p_c_home() {
        if ($this->session === FALSE) {
            $a['title'] = 'Inicio';
            $a['msg'] = read_msjs();
            $this->load->view('public/home', $a);
        } else {
            header('Location:' . WEB_PATH . 'session/dashboard');
        }
    }

}
