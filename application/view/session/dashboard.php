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
            </div>
        </div>
        <?php include_once 'application/template/session/scripts.php'; ?>
    </body>
</html>