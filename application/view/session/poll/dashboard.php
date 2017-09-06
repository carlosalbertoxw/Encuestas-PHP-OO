<!DOCTYPE html>
<html lang="es">
    <head>
        <?php include_once 'application/template/session/head.php'; ?>
    </head>
    <body>
        <?php include_once 'application/template/session/nav.php'; ?>
        <div class="container">
            <?php print $a['msg'] ?>
            <h4 class="text-center"><?php print $a['title'] ?></h4>
            <div class="row">
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
                        print '<a href="' . WEB_PATH . 'session/edit-poll/' . $row['p_key'] . '">Editar</a> | <a onclick="return confirm(\'Estas seguro de borrar este registro\')" href="' . WEB_PATH . 'session/delete-poll/' . $row['p_key'] . '">Borrar</a> | <a href="' . WEB_PATH . 'session/view-answers/' . $row['p_key'] . '">Ver respuestas</a> | <a href="' . WEB_PATH . 'public/add-answer/' . $row['p_key'] . '">Responder</a>';
                        print '</div>';
                        print '</div>';
                    endforeach;
                else:
                    print '<h6 class="text-center">No hay resultados<h6>';
                endif;
                ?>
            </div>
        </div>
        <?php include_once 'application/template/session/scripts.php'; ?>
    </body>
</html>