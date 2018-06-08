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
                    <form class="form-horizontal" action="<?php print WEB_PATH ?>session/change-email" method="post" onsubmit="return change_email(this)">
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="email">Correo electrónico</label>
                            <div class="col-sm-8">
                                <input class="form-control" value="<?php print $a['u_email'] ?>" type="text" name="email" id="email" maxlength="50">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="password">Contraseña</label>
                            <div class="col-sm-8"><input class="form-control" type="password" name="password" id="password" maxlength="50"></div>
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