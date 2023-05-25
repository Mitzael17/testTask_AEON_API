<?php

header('Content-type: json/application');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

const ACCESS  = true;

require_once 'config.php';
require_once 'internal_settings.php';

use core\controllers\route_controller;

route_controller::instance()->route();