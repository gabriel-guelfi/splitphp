<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  die;
}
// Includes main loader class "ObjLoader".
require_once $_SERVER['DOCUMENT_ROOT'] . "/../engine/class.objloader.php";

$system = ObjLoader::load($_SERVER['DOCUMENT_ROOT'] . "/../engine/class.system.php", "system");

die;