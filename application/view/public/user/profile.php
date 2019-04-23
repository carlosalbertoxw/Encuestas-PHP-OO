<!DOCTYPE html>
<html lang="es">
    <head>
        <?php
        if (isset($a['u_p_user_name'])):
            include_once 'application/template/session/head.php';
        else:
            include_once 'application/template/public/head.php';
        endif;
        ?>
    </head>
    <body>
        <?php
        if (isset($a['u_p_user_name'])):
            include_once 'application/template/session/nav.php';
        else:
            include_once 'application/template/public/nav.php';
        endif;
        ?>
        <div class="container">
            <div>
                <?php print $a['msg'] ?>
            </div>
            <h3 style="text-align: center"><?php print $a['p']['u_p_name'] ?> <small><?php print '(' . $a['p']['u_p_user_name'] . ')' ?></small></h3>
            <div class="row">
                <hr>
                <?php
                if (count($a['polls']) > 0) :
                    foreach ($a['polls'] as $row) :
                        print '<div class="panel panel-default">';
                        print '<div class="panel-body">';
                        print $row['p_title'];
                        print '<br>';
                        print $row['p_description'];
                        print '</div>';
                        print '<div class="panel-footer">';
                        print '<a href="' . WEB_PATH . 'public/add-answer/' . $row['p_key'] . '">Responder</a>';
                        print '</div>';
                        print '</div>';
                    endforeach;
                else:
                    print '<h6 class="text-center">No hay encuestas<h6>';
                endif;
                ?>
            </div>
        </div>
        <?php
        if (isset($a['u_p_user_name'])):
            include_once 'application/template/session/scripts.php';
        else:
            include_once 'application/template/public/scripts.php';
        endif;
        ?>
    </body>
</html>
