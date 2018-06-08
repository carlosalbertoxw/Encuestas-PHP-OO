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
                    <form class="form-horizontal" action="<?php print WEB_PATH ?><?php isset($a['p']) ? print 'session/edit-poll' : print 'session/new-poll' ?>" method="post" onsubmit="return poll(this)">
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="title">Título</label>
                            <div class="col-sm-8"><input <?php isset($a['p']) ? print 'value="' . $a['p']['p_title'] . '"' : null ?> class="form-control" type="text" name="title" id="title" maxlength="250"></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="description">Descripcón</label>
                            <div class="col-sm-8"><textarea rows="1" class="form-control" name="description" id="description" maxlength="500"><?php isset($a['p']) ? print $a['p']['p_description'] : null ?></textarea></div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="position">Posición</label>
                            <div class="col-sm-8"><input <?php isset($a['p']) ? print 'value="' . $a['p']['p_position'] . '"' : null ?> class="form-control" type="text" name="position" id="position" maxlength="6"></div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-4 col-sm-8">
                                <button class="btn btn-primary" type="submit" >Enviar</button>
                            </div>
                        </div>
                        <?php
                        isset($a['p']) ? print '<input type="hidden" name="key" value="' . $a['p']['p_key'] . '">' : null;
                        ?>
                    </form>
                </div>
            </div>
        </div>
        <?php include_once 'application/template/session/scripts.php'; ?>
    </body>
</html>