<?php

class System
{

  // Utils class object
  private $utils;
  // Holds general configuration
  private static $configs;
  // Global Vars:
  public static $globals;

  // Include some global core classes and uses data passed on POST, GET or URI to set running controller, action and args.
  public function __construct()
  {
    self::$globals = [];
    define('INCLUDE_PATH', __DIR__ . "/..");
    define('HTTP_PROTOCOL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://"));
    define('URL_APPLICATION', HTTP_PROTOCOL . $_SERVER['SERVER_NAME']);

    $this->loadExtensions();

    $this->utils = self::loadClass(__DIR__ . "/class.utils.php", "utils");
    $this->registerGlobalMethods();

    // Setting up general configs:
    self::$configs = parse_ini_file(INCLUDE_PATH . "/config.ini", true);

    foreach (self::$configs as $key => $val) {
      if ($key != "UTILS" && $key != "ROUTE_ALIAS") {
        foreach ($val as $k => $v) {
          define(strtoupper($k), $v);
        }
      }
    }

    //
    // Including main classes:
    require_once __DIR__ . "/class.request.php";
    require_once __DIR__ . "/class.dao.php";
    require_once __DIR__ . "/class.service.php";
    require_once __DIR__ . "/class.restservice.php";

    $this->execute(new Request($_SERVER["REQUEST_URI"], self::$configs["ROUTE_ALIAS"]));
  }

  /*/ Create an instance of a custom controller and calls it's method, passing specified arguments. 
   * If no controller, action or args is supplied, it uses the ones setted in __construct method, above.
  /*/
  private function execute(Request $request)
  {
    if (file_exists($request->getRestService()->path . $request->getRestService()->name . ".php") === false) {
      http_response_code(404);
      die;
    }

    try {
      $c_obj = self::loadClass($request->getRestService()->path . $request->getRestService()->name . ".php", $request->getRestService()->name);
      $res = call_user_func_array(array($c_obj, 'execute'), $request->getArgs());
      return $res;
    } catch (Exception $ex) {
      self::log('sys_error', $ex);
    }
  }

  private function registerGlobalMethods()
  {
  }

  private function loadExtensions()
  {
    if ($dir = opendir(__DIR__ . '/extensions/')) {
      while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') include_once __DIR__ . '/extensions/' . $file;
      }
    }
  }

  public static function loadClass(string $path, string $classname, array $args = array())
  {
    return ObjLoader::load($path, $classname, $args);
  }

  public static function log(string $logname, $logmsg)
  {
    $path = INCLUDE_PATH . "/application/log/";

    if (!file_exists($path))
      mkdir($path, 0755, true);
    touch($path);
    chmod($path, 0755);

    if (is_array($logmsg) || (gettype($logmsg) == 'object' && $logmsg instanceof stdClass)) {
      $logmsg = json_encode($logmsg);
    }

    $log = fopen($path . $logname . '.log', 'a');
    fwrite($log, "[" . date('Y-m-d H:i:s') . "] - " . $logmsg . str_repeat(Utils::lineBreak(), 2));
    fclose($log);
  }

  public static function errorLog(string $logname, Exception $exc, $info = [])
  {
    self::log($logname, self::exceptionBuildLog($exc, $info));
  }

  public static function navigateToUrl($url, $afterResponse = false)
  {
    if ($afterResponse) echo '<script type="text/javascript">window.location.href="' . $url . '";</script>';
    else header('Location: ' . $url);

    die;
  }

  private static function exceptionBuildLog(Exception $exc, $info)
  {
    return (object) [
      "datetime" => date('Y-m-d H:i:s'),
      "message" => $exc->getMessage(),
      "info" => $info,
      "stack_trace" => $exc->getTrace(),
      "previous_exception" => ($exc->getPrevious() != null ? self::exceptionBuildLog($exc->getPrevious()) : null),
      "file" => $exc->getFile(),
      "line" => $exc->getLine()
    ];
  }
}
