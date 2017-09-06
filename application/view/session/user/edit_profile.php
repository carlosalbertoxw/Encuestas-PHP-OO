<!DOCTYPE html>
<html lang="es">
    <head>
        <?php include_once 'application/template/session/head.php'; ?>
    </head>
    <body>
        <?php include_once 'application/template/session/nav.php'; ?>
        <div class="container">
            <?php print $a['msg'] ?>
            <div class="row">
                <div class="col-md-offset-3 col-md-6 col-sm-12">
                    <h4 class="text-center"><?php print $a['title'] ?></h4>
                    <form class="form-horizontal" action="<?php print WEB_PATH ?>session/edit-profile" method="post" onsubmit="return edit_profile(this)">
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="name">Nombre</label>
                            <div class="col-sm-8"><input class="form-control" value="<?php print $a['u_p_name'] ?>" type="text" name="name" id="name" maxlength="50"></div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-4 col-sm-8">
                                <button class="btn btn-primary" type="submit" >Enviar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php include_once 'application/template/session/scripts.php'; ?>
    </body>
</html>