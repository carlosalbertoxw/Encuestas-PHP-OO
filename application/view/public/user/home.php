<!DOCTYPE html>
<html lang="es">
    <head>
        <?php include_once 'application/template/public/head.php'; ?>
    </head>
    <body>
        <?php include_once 'application/template/public/nav.php'; ?>
        <div class="container">
            <div>
                <?php print $a['msg'] ?>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <h4 class="text-center">Acceso</h4>
                    <form class="form-horizontal" action="<?php print WEB_PATH ?>public/sign-in" method="post" onsubmit="return sign_in(this)">
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="email1">Correo electrónico</label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" name="email" id="email1" maxlength="50">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="password1">Contraseña</label>
                            <div class="col-sm-8"><input class="form-control" type="password" name="password" id="password1" maxlength="50"></div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-4 col-sm-8">
                                <div class="checkbox">
                                    <label for="remember">
                                        <input type="checkbox" name="remember" id="remember"> Recordar
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-4 col-sm-8">
                                <button class="btn btn-primary" type="submit" >Enviar</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 col-sm-12">
                    <h4 class="text-center">Registro</h4>
                    <form class="form-horizontal" action="<?php print WEB_PATH ?>public/sign-up" method="post" onsubmit="return sign_up(this)">
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="email">Correo electrónico</label>
                            <div class="col-sm-8">
                                <input class="form-control" type="text" name="email" id="email" maxlength="50">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="password">Contraseña</label>
                            <div class="col-sm-8"><input class="form-control" type="password" name="password" id="password" maxlength="50"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="re_password">Repita contraseña</label>
                            <div class="col-sm-8"><input class="form-control" type="password" name="re_password" id="re_password" maxlength="50"></div>
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
        <?php include_once 'application/template/public/scripts.php'; ?>
    </body>
</html>
