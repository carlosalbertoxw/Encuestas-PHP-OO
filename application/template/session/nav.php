<nav class="navbar navbar-default navbar-static-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php print WEB_PATH . 'session/dashboard' ?>"><?php print WEB_SITE_NAME ?></a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-left">
                <li><a href="<?php print WEB_PATH ?>session/new-poll">Nueva encuesta</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Usuario <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php print WEB_PATH . $a['u_p_user'] ?>">Ver perfil</a></li>
                        <li><a href="<?php print WEB_PATH ?>session/edit-profile">Editar perfil</a></li>
                        <li><a href="<?php print WEB_PATH ?>session/change-user">Cambiar usuario</a></li>
                        <li><a href="<?php print WEB_PATH ?>session/change-email">Cambiar correo electrónico</a></li>
                        <li><a href="<?php print WEB_PATH ?>session/change-password">Cambiar contraseña</a></li>
                        <li><a href="<?php print WEB_PATH ?>session/delete-account">Borrar cuenta</a></li>
                        <li><a href="<?php print WEB_PATH ?>session/close-session">Cerrar sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>