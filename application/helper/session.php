<?php

function create_session_cookie($value, $remember = FALSE) {
    $cookie_name = 'AOAAOA';
    if ($remember == 'on') {
        setcookie($cookie_name, encrypt(serialize($value), ''), time() + 60 * 60 * 24 * 365, WEB_PATH, WEB_SITE_DOMAIN, RELEASED, RELEASED);
    } else {
        setcookie($cookie_name, encrypt(serialize($value), ''), 0, WEB_PATH, WEB_SITE_DOMAIN, RELEASED, RELEASED);
    }
}

function remove_session_cookie() {
    $cookie_name = 'AOAAOA';
    if (isset($_COOKIE[$cookie_name])) {
        setcookie($cookie_name, '', time() - 60, WEB_PATH, WEB_SITE_DOMAIN, RELEASED, RELEASED);
    }
}

function get_session_cookie() {
    $cookie_name = 'AOAAOA';
    if (isset($_COOKIE[$cookie_name])) {
        return unserialize(decrypt($_COOKIE[$cookie_name], ''));
    }
    return FALSE;
}
