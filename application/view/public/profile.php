<!DOCTYPE html>
<html lang="es">
    <head>
        <?php
        if (isset($a['u_p_user'])):
            include_once 'application/template/session/head.php';
        else:
            include_once 'application/template/public/head.php';
        endif;
        ?>
    </head>
    <body>
        <?php
        if (isset($a['u_p_user'])):
            include_once 'application/template/session/nav.php';
        else:
            include_once 'application/template/public/nav.php';
        endif;
        ?>
        <div class="container">
            <div>
                <?php print $a['msg'] ?>
            </div>
            <h3 style="text-align: center"><?php print $a['p']['u_p_name'] ?> <small><?php print '(' . $a['p']['u_p_user'] . ')' ?></small></h3>
            <div class="row">
                <hr>
            </div>
        </div>
        <?php
        if (isset($a['u_p_user'])):
            include_once 'application/template/session/scripts.php';
        else:
            include_once 'application/template/public/scripts.php';
        endif;
        ?>
    </body>
</html>
