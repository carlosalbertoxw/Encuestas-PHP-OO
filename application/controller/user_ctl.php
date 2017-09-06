<?php

class User_CTL extends Controller {

    private $user_mdl;

    public function __construct() {
        parent::__construct();

        $this->user_mdl = $this->load->model('user');

        $this->session = $this->session_validation();
    }

    private function u_c_is_user_valid($user) {
        $rw = array("application", "resource", "public", "session");
        for ($i = 0; $i < count($rw); $i++) {
            if ($rw[$i] == strtolower($user)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function u_c_edit_profile_form() {
        if ($this->session !== FALSE) {
            $data['key'] = $this->session['u_key'];
            if (val_input_post('name', VI_STRING, 50, FALSE)) {
                $data['name'] = post('name');
                $r = $this->user_mdl->u_m_update_profile($data);
                if ($r === 0) {
                    create_msj('Los datos se guardaron exitosamente', MSG_SUCCESS);
                    $this->session['u_p_name'] = $data['name'];
                    create_session_cookie($this->session, $this->session['remember']);
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

    public function u_c_change_password_form() {
        if ($this->session !== FALSE) {
            if (val_input_post('password', VI_STRING, 50, FALSE)) {
                $r = $this->user_mdl->u_m_get_user_by_key($this->session['u_key']);
                $a['password'] = post('password');
                if ($r['u_password'] != '' and $a['password'] != '' and $r['u_password'] == encrypt_password($a['password'])) {
                    $data['key'] = $this->session['u_key'];
                    if (val_input_post('new_password', VI_STRING, 50, FALSE) and post('new_password') == post('re_new_password')) {
                        $data['new_password'] = encrypt_password(post('new_password'));
                        $r = $this->user_mdl->u_m_update_password($data);
                        if ($r === 0) {
                            create_msj('Los datos se guardaron exitosamente', MSG_SUCCESS);
                            $this->session['u_password'] = $data['new_password'];
                            create_session_cookie($this->session, $this->session['remember']);
                        } else {
                            create_msj('Ocurrio un error al guardar los datos', MSG_DANGER);
                        }
                        header('Location:' . WEB_PATH . 'session/dashboard');
                    } else {
                        create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                        header('Location:' . WEB_PATH . 'session/dashboard');
                    }
                } else {
                    create_msj('La contraseña es incorrecta', MSG_DANGER);
                    header('Location:' . WEB_PATH . 'session/dashboard');
                }
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                header('Location:' . WEB_PATH . 'session/dashboard');
            }
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_change_email_form() {
        if ($this->session !== FALSE) {
            if (val_input_post('password', VI_STRING, 50, FALSE)) {
                $r = $this->user_mdl->u_m_get_user_by_key($this->session['u_key']);
                $a['password'] = post('password');
                if ($r['u_password'] != '' and $a['password'] != '' and $r['u_password'] == encrypt_password($a['password'])) {
                    $data['key'] = $this->session['u_key'];
                    if (val_input_post('email', VI_EMAIL, 50, FALSE)) {
                        $data['email'] = post('email');
                        $r = $this->user_mdl->u_m_update_email($data);
                        if ($r === 0) {
                            create_msj('Los datos se guardaron exitosamente', MSG_SUCCESS);
                            $this->session['u_email'] = $data['email'];
                            create_session_cookie($this->session, $this->session['remember']);
                        } elseif (strpos($r, '1062') !== FALSE) {
                            create_msj('<strong>El correo ingresado ya pertenece a una cuenta.</strong>', 'danger');
                        } else {
                            create_msj('Ocurrio un error al guardar los datos', MSG_DANGER);
                        }
                        header('Location:' . WEB_PATH . 'session/dashboard');
                    } else {
                        create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                        header('Location:' . WEB_PATH . 'session/dashboard');
                    }
                } else {
                    create_msj('La contraseña es incorrecta', MSG_DANGER);
                    header('Location:' . WEB_PATH . 'session/dashboard');
                }
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                header('Location:' . WEB_PATH . 'session/dashboard');
            }
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_change_user_form() {
        if ($this->session !== FALSE) {
            if (val_input_post('password', VI_STRING, 50, FALSE)) {
                $r = $this->user_mdl->u_m_get_user_by_key($this->session['u_key']);
                $a['password'] = post('password');
                if ($r['u_password'] != '' and $a['password'] != '' and $r['u_password'] == encrypt_password($a['password'])) {
                    $data['key'] = $this->session['u_key'];
                    if (val_input_post('user', VI_URI, 50, FALSE) and $this->u_c_is_user_valid(post('user'))) {
                        $data['user'] = post('user');
                        $r = $this->user_mdl->u_m_update_user($data);
                        if ($r === 0) {
                            create_msj('Los datos se guardaron exitosamente', MSG_SUCCESS);
                            $this->session['u_p_user'] = $data['user'];
                            create_session_cookie($this->session, $this->session['remember']);
                        } elseif (strpos($r, '1062') !== FALSE) {
                            create_msj('<strong>El usuario ingresado ya pertenece a una cuenta.</strong>', 'danger');
                        } else {
                            create_msj('Ocurrio un error al guardar los datos', MSG_DANGER);
                        }
                        header('Location:' . WEB_PATH . 'session/dashboard');
                    } else {
                        create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                        header('Location:' . WEB_PATH . 'session/dashboard');
                    }
                } else {
                    create_msj('La contraseña es incorrecta', MSG_DANGER);
                    header('Location:' . WEB_PATH . 'session/dashboard');
                }
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                header('Location:' . WEB_PATH . 'session/dashboard');
            }
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_edit_profile() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Editar perfil';
            $a['msg'] = read_msjs();
            $this->load->view('session/user/edit_profile', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_change_user() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Cambiar usuario';
            $a['msg'] = read_msjs();
            $this->load->view('session/user/change_user', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_change_email() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Cambiar correo electrónico';
            $a['msg'] = read_msjs();
            $this->load->view('session/user/change_email', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_change_password() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Cambiar contraseña';
            $a['msg'] = read_msjs();
            $this->load->view('session/user/change_password', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_delete_account_form() {
        if ($this->session !== FALSE) {
            if (val_input_post('password', VI_STRING, 50, FALSE)) {
                $r = $this->user_mdl->u_m_get_user_by_key($this->session['u_key']);
                $a['password'] = post('password');
                if ($r['u_password'] != '' and $a['password'] != '' and $r['u_password'] === encrypt_password($a['password'])) {
                    $data['key'] = $this->session['u_key'];
                    $r = $this->user_mdl->u_m_delete_account($data);
                    if ($r === 0) {
                        create_msj('Los datos se eliminaron exitosamente<br>¡Vuelve pronto!', MSG_SUCCESS);
                        remove_session_cookie();
                    } else {
                        create_msj('Ocurrio un error al aliminar los datos', MSG_DANGER);
                    }
                    header('Location:' . WEB_PATH);
                } else {
                    create_msj('La contraseña es incorrecta', MSG_DANGER);
                    header('Location:' . WEB_PATH);
                }
            } else {
                create_msj('Ocurrió un error en la validación de los datos', MSG_DANGER);
                header('Location:' . WEB_PATH);
            }
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_delete_account() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Borrar cuenta';
            $a['msg'] = read_msjs();
            $this->load->view('session/user/delete_account', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_close_session() {
        if ($this->session !== FALSE) {
            remove_session_cookie();
        }
        header('Location:' . WEB_PATH);
    }

    public function u_c_profile() {
        if (val_input_get('one', VI_URI, 25, FALSE)) {
            $user = get('one');
            $r = $this->user_mdl->u_m_get_profile($user);
            if (count($r) == 6) {
                $poll_mdl = $this->load->model('poll');
                $a = $this->session;
                $a['title'] = $r['u_p_name'];
                $a['p'] = $r;
                $a['polls'] = $poll_mdl->p_m_get_polls($r['u_p_key']);
                $a['msg'] = read_msjs();
                $this->load->view('public/user/profile', $a);
            } else {
                $this->load->error_404();
            }
        } else {
            $this->load->error_404();
        }
    }

    public function u_c_sign_in_form() {
        if ($this->session === FALSE) {
            if (val_input_post('email', VI_EMAIL, 50, FALSE) and val_input_post('password', VI_STRING, 50, FALSE)) {
                $a['email'] = post('email');
                $a['password'] = post('password');
                $r = $this->user_mdl->u_m_get_user_by_email($a);
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

    public function u_c_sign_up_form() {
        if ($this->session === FALSE) {
            if (val_input_post('email', VI_EMAIL, 50, FALSE) and val_input_post('password', VI_STRING, 50, FALSE) and filter_input(INPUT_POST, 'password') === filter_input(INPUT_POST, 're_password')) {
                $a['email'] = post('email');
                $a['password_key'] = random_string(50);
                $a['password'] = encrypt_password(post('password'));
                $r = $this->user_mdl->u_m_sign_up($a);
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

    public function u_c_home() {
        if ($this->session === FALSE) {
            $a['title'] = 'Inicio';
            $a['msg'] = read_msjs();
            $this->load->view('public/user/home', $a);
        } else {
            header('Location:' . WEB_PATH . 'session/dashboard');
        }
    }

}
