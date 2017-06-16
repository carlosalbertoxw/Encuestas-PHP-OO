<?php

define('MSG_DANGER', 'danger');
define('MSG_INFO', 'info');
define('MSG_PRIMARY', 'primary');
define('MSG_SUCCESS', 'success');

function read_msjs() {
    $string = '';
    if (isset($_SESSION['0A0'])) {
        $msjs = $_SESSION['0A0'];
        unset($_SESSION['0A0']);
        for ($i = 0; $i < count($msjs); $i++) {
            $string = $string . '<div class="alert alert-' . $msjs[$i]['type'] . ' alert-dismissable text-center"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' . $msjs[$i]['msj'] . '</div>';
        }
    }
    return $string;
}

function create_msj($msj, $type) {
    $msj_a['msj'] = $msj;
    $msj_a['type'] = $type;
    if (isset($_SESSION['0A0'])) {
        $msjs_a = $_SESSION['0A0'];
        $msjs_a[] = $msj_a;
        $_SESSION['0A0'] = $msjs_a;
    } else {
        $msjs_a = array($msj_a);
        $_SESSION['0A0'] = $msjs_a;
    }
}

function create_msj_in_page($msj, $type, $str_msjs) {
    $string = $str_msjs . '<div class="alert alert-' . $type . ' alert-dismissable text-center"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' . $msj . '</div>';
    return $string;
}
