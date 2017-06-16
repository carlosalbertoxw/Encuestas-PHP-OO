<?php

class page_rtr {

    private $load;

    public function __construct() {
        $this->load = Load::get_instance();
        if (filter_input(INPUT_GET, 'one') === '') {
            $ctl = $this->load->controller('public');
            $ctl->p_c_home();
        } else
        if (URI === WEB_PATH . 'index.php') {
            header('Location:' . WEB_PATH);
        } else {
            $this->load->error_404();
        }
    }

}
