<?php

define('VI_URI', 'uri');
define('VI_URL', 'url');
define('VI_NUMERIC', 'numeric');
define('VI_NUMERIC_DECIMAL', 'numeric_decimal');
define('VI_EMAIL', 'email');
define('VI_STRING', 'string');

function val_input($val, $type, $length, $empty) {
    return t_l_e($val, $type, $length, $empty);
}

function val_input_get($val, $type, $length, $empty) {
    return t_l_e(filter_input(INPUT_GET, $val), $type, $length, $empty);
}

function val_input_post($val, $type, $length, $empty) {
    return t_l_e(filter_input(INPUT_POST, $val), $type, $length, $empty);
}

function t_l_e($val, $type, $length, $empty) {
    if (is_null($val)) {
        $val = '';
    }
    if ($empty == FALSE and strlen($val) == 0) {
        return FALSE;
    } else
    if ($empty == FALSE and strlen($val) > 0) {
        return t_l($val, $type, $length);
    } else
    if ($empty == TRUE and strlen($val) > 0) {
        return t_l($val, $type, $length);
    } else
    if ($empty == TRUE and strlen($val) == 0) {
        return TRUE;
    }
}

function t_l($val, $type, $length) {
    switch ($type) {
        case 'uri':
            $flag = val_uri($val);
            break;
        case 'url':
            $flag = val_url($val);
            break;
        case 'numeric':
            $flag = val_numeric($val);
            break;
        case 'numeric_decimal':
            $flag = val_numeric_decimal($val);
            break;
        case 'email':
            $flag = val_email($val);
            break;
        case 'string':
            $flag = TRUE;
            break;
        default:
            $flag = FALSE;
            break;
    }
    if ($flag) {
        if (strlen(utf8_decode($val)) <= $length) {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }
}

function val_uri($string) {
    return preg_match('/^([0-9a-zA-Z-])*$/', $string);
}

function val_url($string) {
    return filter_var($string, FILTER_VALIDATE_URL);
}

function val_numeric($string) {
    $flag = preg_match('/^([0-9])*$/', $string);
    if ($flag /* and $string <= 2147483647 */) {
        return TRUE;
    }
    return FALSE;
}

function val_numeric_decimal($string) {
    return preg_match('/^[0-9, ]*\.?[0-9]*$/', $string);
}

function val_email($string) {
    return preg_match('/^[^@\s]+@[^@\.\s]+(\.[^@\.\s]+)+$/', $string);
}

function post($name) {
    $string1 = trim(filter_input(INPUT_POST, $name));
    $string2 = nl2br(strip_tags($string1));
    return $string2;
}

function get($name) {
    $string1 = trim(filter_input(INPUT_GET, $name));
    $string2 = nl2br(strip_tags($string1));
    return $string2;
}
