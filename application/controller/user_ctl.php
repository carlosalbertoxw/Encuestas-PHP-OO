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
            if (val_input_post('name', VI_STRING, 50, FALSE) and val_input_post('user', VI_URI, 25, FALSE) and $this->u_c_is_user_valid(post('user'))) {
                $data['name'] = post('name');
                $data['user'] = post('user');
                $r = $this->user_mdl->u_m_update_profile($data);
                if ($r === 0) {
                    create_msj('Los datos se guardaron exitosamente', MSG_SUCCESS);
                    $this->session['u_p_name'] = $data['name'];
                    $this->session['u_p_user'] = $data['user'];
                    create_session_cookie($this->session, $this->session['remember']);
                } elseif (strpos($r, '1062') !== FALSE) {
                    create_msj('<strong>El usuario ingresado ya pertenece a una cuenta. Por favor intentalo nuevamente', 'danger');
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
                            create_msj('<strong>El correo ingresado ya pertenece a una cuenta.</strong> para restablecer la contraseña haga <a class="alert-link" href="' . WEB_PATH . 'reset-password">clic aquí</a>', 'danger');
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
            $this->load->view('user/edit_profile', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_change_email() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Cambiar correo electrónico';
            $a['msg'] = read_msjs();
            $this->load->view('user/change_email', $a);
        } else {
            header('Location:' . WEB_PATH);
        }
    }

    public function u_c_change_password() {
        if ($this->session !== FALSE) {
            $a = $this->session;
            $a['title'] = 'Cambiar contraseña';
            $a['msg'] = read_msjs();
            $this->load->view('user/change_password', $a);
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
                        create_msj('Los datos se eliminaron exitosamente<br>¡Hasta luego!', MSG_SUCCESS);
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
            $this->load->view('user/delete_account', $a);
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

}
