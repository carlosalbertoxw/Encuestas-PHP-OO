<?php

define('VI_USERNAME', 'username');
define('VI_URL', 'url');
define('VI_NUMERIC', 'numeric');
define('VI_NUMERIC_DECIMAL', 'numeric_decimal');
define('VI_EMAIL', 'email');
define('VI_STRING', 'string');

function val_input($val, $type, $length, $empty) {
    return t_l_e($val, $type, $length, $empty);
}

function t_l_e($val, $type, $length, $empty) {
    if (is_null($val)) {
        return FALSE;
    }
    if ($empty == FALSE and strlen($val) == 0) {
        return FALSE;
    } else
    if ($empty == FALSE and strlen($val) > 0) {
        return t($val, $type, $length);
    } else
    if ($empty == TRUE and strlen($val) > 0) {
        return t($val, $type, $length);
    } else {
        return $empty == TRUE and strlen($val) == 0;
    }
}

function t($val, $type, $length) {
    switch ($type) {
        case 'username':
            $pattern = '/^([0-9a-zA-Z-])*$/';
            $flag = val($val, $pattern);
            break;
        case 'url':
            $flag = filter_var($val, FILTER_VALIDATE_URL) != FALSE ? TRUE : FALSE;
            break;
        case 'numeric':
            $pattern = '/^([0-9])*$/';
            $flag = val($val, $pattern);
            break;
        case 'numeric_decimal':
            $pattern = '/^[0-9,]*\.?[0-9]*$/';
            $flag = val($val, $pattern);
            break;
        case 'email':
            $pattern = '/^[^@\s]+@[^@\.\s]+(\.[^@\.\s]+)+$/';
            $flag = val($val, $pattern);
            break;
        case 'string':
            $flag = TRUE;
            break;
        default:
            $flag = FALSE;
            break;
    }
    return l($val, $flag, $length);
}

function l($val, $flag, $length) {
    return $flag and strlen(utf8_decode($val)) <= $length;
}

function val($string, $pattern) {
    return preg_match($pattern, $string);
}
