<!DOCTYPE html>
<html lang="es">
    <head>
        <?php include_once 'application/template/public/head.php'; ?>
    </head>
    <body>
        <?php include_once 'application/template/public/nav.php'; ?>
        <div class="container">
            <?php print $a['msg'] ?>
            <div class="row">
                <div class="col-md-offset-3 col-md-6 col-sm-12">
                    <h4 class="text-center"><?php print $a['title'] ?></h4>
                    <h5 class="text-center">De: <a href="<?php print WEB_PATH . $a['p']['u_p_user'] ?>"><?php print $a['p']['u_p_name'] ?> <small>(<?php print $a['p']['u_p_user'] ?>)</small></a></h5>
                    <?php
                    print '<p class="text-center">' . $a['p']['p_description'] . '</p>';
                    ?>
                    <br>
                    <form class="form-horizontal" action="<?php print WEB_PATH ?>public/add-answer" method="post" onsubmit="return add_poll_answer()">
                        <div class="form-group">
                            <label class="col-sm-4 control-label">Estrellas</label>
                            <div class="col-sm-8">
                                <label class="radio-inline">
                                    <input type="radio" name="stars" id="inlineRadio1" value="1"> 1
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="stars" id="inlineRadio2" value="2"> 2
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="stars" id="inlineRadio3" value="3"> 3
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="stars" id="inlineRadio4" value="4"> 4
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="stars" id="inlineRadio5" value="5"> 5
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-4 control-label" for="comment">Comentario</label>
                            <div class="col-sm-8"><textarea rows="1" class="form-control" name="comment" id="comment" maxlength="1000"></textarea></div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-4 col-sm-8">
                                <button class="btn btn-primary" type="submit" >Enviar</button>
                            </div>
                        </div>
                        <?php
                        isset($a['p']) ? print '<input type="hidden" name="user_key" value="' . $a['p']['u_p_key'] . '"><input type="hidden" name="poll_key" value="' . $a['p']['p_key'] . '"><input type="hidden" name="user" value="' . $a['p']['u_p_user'] . '">' : null;
                        ?>
                    </form>
                </div>
            </div>
        </div>
        <?php include_once 'application/template/public/scripts.php'; ?>
    </body>
</html>