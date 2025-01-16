<?php

namespace engine;

class Helpers
{
  public static function Log()
  {
    return ObjLoader::load(ROOT_PATH . "/engine/helpers/log.php", "Log");
  }

  public static function cURL()
  {
    return ObjLoader::load(ROOT_PATH . "/engine/helpers/curl.php", "Curl");
  }
}
