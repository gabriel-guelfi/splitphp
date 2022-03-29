<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                                                                                                //
//                                                                ** SPLIT PHP FRAMEWORK **                                                                       //
// This file is part of *SPLIT PHP Framework*                                                                                                                     //
//                                                                                                                                                                //
// Why "SPLIT"? Firstly because the word "split" is a reference to micro-services and split systems architecture (of course you can make monoliths with it,       //
// if that's your thing). Furthermore, it is an acronym for these 5 bound concepts which are the bases that this framework leans on, which are: "Simplicity",     //
// "Purity", "Lightness", "Intuitiveness", "Target Minded"                                                                                                        //
//                                                                                                                                                                //
// See more info about it at: https://github.com/gabriel-guelfi/split-php                                                                                         //
//                                                                                                                                                                //
// MIT License                                                                                                                                                    //
//                                                                                                                                                                //
// Copyright (c) 2022 SPLIT PHP Framework Community                                                                                                               //
//                                                                                                                                                                //
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to          //
// deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or         //
// sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:                            //
//                                                                                                                                                                //
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.                                 //
//                                                                                                                                                                //
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,FITNESS     //
// FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY           //
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.     //
//                                                                                                                                                                //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Class System
 * 
 * This is the main class, the entry point of the application.
 *
 * @package engine
 */
class System
{
  /**
   * @var array $configs
   * Stores all the settings that come from config.ini file.
   */
  private static $configs;

  /**
   * @var array $globals
   * Used to store static data that must be available in the entire application.
   */
  public static $globals;

  /** 
   * This is the constructor of System class. It initiate the $globals property, create configuration constants, load and runs 
   * extensions, load custom exception classes, include the main classes, then executes the request.
   * 
   * @return System 
   */
  public function __construct()
  {
    self::$globals = [];

    define('INCLUDE_PATH', __DIR__ . "/..");
    define('HTTP_PROTOCOL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://"));
    define('URL_APPLICATION', HTTP_PROTOCOL . $_SERVER['SERVER_NAME']);

    $this->loadExtensions();
    $this->loadExceptions();

    // Setting up general configs:
    self::$configs = parse_ini_file(INCLUDE_PATH . "/config.ini", true);

    foreach (self::$configs as $key => $val) {
      if ($key != "UTILS") {
        foreach ($val as $k => $v) {
          define(strtoupper($k), $v);
        }
      }
    }

    // Including main classes:
    require_once __DIR__ . "/class.request.php";
    require_once __DIR__ . "/class.dao.php";
    require_once __DIR__ . "/class.service.php";
    require_once __DIR__ . "/class.restservice.php";
    require_once __DIR__ . "/class.utils.php";

    $this->execute(new Request($_SERVER["REQUEST_URI"]));
  }

  /** 
   * This is a wrapper to ObjLoader::load() method. Returns the instance of a class registered on the collection. 
   * If the class instance isn't registered yet, create a new instance of that class, register it on the collection, then returns it.
   * 
   * @param string $path
   * @param string $classname
   * @param array $args = []
   * @return mixed 
   */
  public static function loadClass(string $path, string $classname, array $args = array())
  {
    return ObjLoader::load($path, $classname, $args);
  }

  /** 
   * Creates a log file under /application/log with the specified $logname, writing down $logmsg with the current datetime 
   * 
   * @param string $logname
   * @param mixed $logmsg
   * @return void 
   */
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

  /** 
   * Creates a log file under /application/log with the specified $logname, with specific information about the exception received in $exc. 
   * Use $info to add extra information on the log.
   * 
   * @param string $logname
   * @param Exception $exc
   * @param array $info = []
   * @return void 
   */
  public static function errorLog(string $logname, Exception $exc, array $info = [])
  {
    self::log($logname, self::exceptionBuildLog($exc, $info));
  }

  /** 
   * Navigate the user agent to the specified $url. If $afterResponse flag is set to true, echoes a front-end script that does that.
   * 
   * @param string $url
   * @param boolean $afterResponse
   * @return void 
   */
  public static function navigateToUrl(string $url, bool $afterResponse = false)
  {
    if ($afterResponse) echo '<script type="text/javascript">window.location.href="' . $url . '";</script>';
    else header('Location: ' . $url);

    die;
  }

  /** 
   * Using the information stored in the received Request object, set and run a specific RestService, passing along the route 
   * and data specified in that Request object.
   * 
   * @param Request $request
   * @return Response 
   */
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
      self::errorLog('sys_error', $ex);
      throw $ex;
    }
  }

  /** 
   * Loads and runs all scripts located at /engine/extensions. It is used to add extra functionalities to PHP's interface, like $_PUT 
   * superglobal, for instance.
   * 
   * @return void 
   */
  private function loadExtensions()
  {
    if ($dir = opendir(__DIR__ . '/extensions/')) {
      while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') include_once __DIR__ . '/extensions/' . $file;
      }
    }
  }

  /** 
   * Includes all custom exception classes located at /engine/exceptions.
   * 
   * @return void 
   */
  private function loadExceptions()
  {
    if ($dir = opendir(__DIR__ . '/exceptions/')) {
      while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') include_once __DIR__ . '/exceptions/' . $file;
      }
    }
  }

  /** 
   * Using the information of the exception received in $exc, and the extra $info, builds a fittable 
   * error log object to be used as $logmsg.  
   * 
   * @param Exception $exc
   * @param array $info
   * @return void 
   */
  private static function exceptionBuildLog(Exception $exc, array $info)
  {
    return (object) [
      "datetime" => date('Y-m-d H:i:s'),
      "message" => $exc->getMessage(),
      "info" => $info,
      "stack_trace" => $exc->getTrace(),
      "previous_exception" => ($exc->getPrevious() != null ? self::exceptionBuildLog($exc->getPrevious(), []) : null),
      "file" => $exc->getFile(),
      "line" => $exc->getLine()
    ];
  }
}
