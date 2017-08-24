<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $a['title'] . ' - ' . WEB_SITE_NAME ?></title>
        <link href="<?php print WEB_PATH ?>application/library/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">
                    <a class="navbar-brand" href="<?php print WEB_PATH ?>"><?php print WEB_SITE_NAME ?></a>
                </div>
            </div>
        </nav>
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
        <script src="<?php print WEB_PATH ?>application/library/js/jquery.min.js"></script>
        <script src="<?php print WEB_PATH ?>application/library/bootstrap/js/bootstrap.min.js"></script>
        <script src="<?php print WEB_PATH ?>application/library/js/public/validations.js"></script>
    </body>
</html>
