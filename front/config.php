<?php

include(__DIR__ . '/../../../inc/includes.php');
Session::checkRight('config', UPDATE);
Html::redirect($CFG_GLPI["root_doc"] . "/front/config.form.php");
