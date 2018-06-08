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
            <?php
            print '<p class="text-center">' . $a['poll']['p_description'] . '</p>';
            ?>
            <br>
            <div class="row">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>Estrellas</th>
                                <th>Comentarios</th>
                            </tr>
                        </thead>
                        <?php
                        if (count($a['answers']) > 0) :
                            foreach ($a['answers'] as $row) :
                                print '<tr>';
                                print '<td>' . $row['a_stars'] . '</td>';
                                print '<td>' . $row['a_comment'] . '</td>';
                                print '</tr>';
                            endforeach;
                        else:
                            print '<tr><td colspan="2">No hay resultados</td></tr>';
                        endif;
                        ?>
                    </table>
                </div>
            </div>
        </div>
        <?php include_once 'application/template/session/scripts.php'; ?>
    </body>
</html>