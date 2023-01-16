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

namespace engine;

use stdClass;
use Exception;

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
   * @var string $webServiceName
   * Stores the name of the WebService which is being executed in the current request/response.
   */

  public static $webServiceName;
  /**
   * @var array $globals
   * Used to store static data that must be available in the entire application.
   */
  public static $globals;

  /**
   * @var string $cliName
   * Stores the name of the CLI which is being executed in the current command execution.
   */
  private static $cliName;

  /** 
   * This is the constructor of System class. It initiate the $globals property, create configuration constants, load and runs 
   * extensions, load custom exception classes, include the main classes, then executes the request.
   * 
   * @return System 
   */
  public final function __construct($cliArgs = [])
  {
    // Setup error handling:
    $this->setupErrorHandling();

    // Initiate System's properties:
    self::$globals = [];
    self::$webServiceName = "";
    self::$cliName = "";

    // Define runtime constants:
    define('ROOT_PATH', __DIR__ . "/..");
    define('HTTP_PROTOCOL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://"));
    define('URL_APPLICATION', HTTP_PROTOCOL . $_SERVER['HTTP_HOST']);

    // Setting up general configs:
    $this->loadConfigsFromFile();
    $this->setConfigsFromEnv();

    // Setup CORS
    if (ALLOW_CORS == "on")
      $this->setupCORS();

    // Set system's default timezone: 
    if (!empty(DEFAULT_TIMEZONE))
      date_default_timezone_set(DEFAULT_TIMEZONE);

    // Load extensions:
    $this->loadExtensions();
    $this->loadExceptions();

    // Including main classes:
    require_once __DIR__ . "/class.objloader.php";
    require_once __DIR__ . "/class.dao.php";
    require_once __DIR__ . "/class.service.php";
    require_once __DIR__ . "/class.utils.php";

    if (empty($cliArgs)) {
      require_once __DIR__ . "/class.request.php";
      require_once __DIR__ . "/class.webservice.php";
      $this->executeRequest(new Request($_SERVER["REQUEST_URI"]));
    } else {
      require_once __DIR__ . "/class.action.php";
      require_once __DIR__ . "/class.cli.php";
      $this->executeCommand(new Action($cliArgs));
    }

    $this->serverLogCleanUp();
  }

  /** 
   * Returns a string representation of this class for printing purposes.
   * 
   * @return string 
   */
  public function __toString()
  {
    $webService = self::$webServiceName;
    $cli = self::$cliName;
    
    return "class:" . __CLASS__ . "(CLI:{$cli}, WebService:{$webService})";
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
    if ($logname == 'server') throw new Exception("You cannot manually write data in server's log.");

    $path = ROOT_PATH . "/application/log/";

    if (!file_exists($path))
      mkdir($path, 0755, true);
    touch($path);
    chmod($path, 0755);

    if (is_array($logmsg) || (gettype($logmsg) == 'object' && $logmsg instanceof stdClass)) {
      $logmsg = json_encode($logmsg);
    }

    $currentLogData = array_filter(explode(str_repeat(PHP_EOL, 2), file_get_contents($path . $logname . '.log')));
    if (count($currentLogData) >= MAX_LOG_ENTRIES) {
      $currentLogData = array_slice($currentLogData, ((MAX_LOG_ENTRIES - 1) * -1));
      $currentLogData[] = "[" . date('Y-m-d H:i:s') . "] - " . $logmsg;
      file_put_contents($path . $logname . '.log', implode(str_repeat(PHP_EOL, 2), $currentLogData) . str_repeat(PHP_EOL, 2));
    } else {
      $log = fopen($path . $logname . '.log', 'a');
      fwrite($log, "[" . date('Y-m-d H:i:s') . "] - " . $logmsg . str_repeat(PHP_EOL, 2));
      fclose($log);
    }
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
   * Navigate the user agent to the specified $url.
   * 
   * @param string $url
   * @return void 
   */
  public static function navigateToUrl(string $url)
  {
    header('Location: ' . $url);

    die;
  }

  /** 
   * Setup CORS policy and responds pre-flight requests:
   * 
   * @return void 
   */
  private function setupCORS()
  {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // cache for 1 day

    // Respond pre-flight requests:
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

      if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

      die;
    }
  }

  /** 
   * Setup /application/log directory and pre-create server.log file
   * 
   * @return void 
   */
  private function setupErrorHandling()
  {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE);

    $path = __DIR__ . "/../application/log";
    if (!file_exists($path)) {
      mkdir($path);
      chmod($path, 0755);
    }

    $path .= "/server.log";
    if (!file_exists($path)) {
      touch($path);
      chmod($path, 0644);
    }

    ini_set('error_log', $path);
  }

  /** 
   * Using the information stored in the received Request object, set and run a specific WebService, passing along the route 
   * and data specified in that Request object.
   * 
   * @param Request $request
   * @return void
   */
  private function executeRequest(Request $request)
  {
    if (file_exists($request->getWebService()->path . $request->getWebService()->name . ".php") === false) {
      http_response_code(404);
      die;
    }

    self::$webServiceName = $request->getWebService()->name;

    $webServiceObj = self::loadClass($request->getWebService()->path . $request->getWebService()->name . ".php", $request->getWebService()->name);
    call_user_func_array(array($webServiceObj, 'execute'), $request->getArgs());
  }

  /** 
   * Using the information stored in the received Action object, set and run a specific Cli, passing along the command 
   * and arguments specified in that Action object.
   * 
   * @param Action $action
   * @return void
   */
  private function executeCommand(Action $action)
  {
    if (file_exists($action->getCli()->path . $action->getCli()->name . ".php") === false) {
      throw new Exception("Command not found.");
    }

    self::$cliName = $action->getCli()->name;

    $CliObj = self::loadClass($action->getCli()->path . $action->getCli()->name . ".php", $action->getCli()->name);
    call_user_func_array(array($CliObj, 'execute'), $action->getArgs());
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
      "webService" => ucfirst(self::$webServiceName),
      "cli" => ucfirst(self::$cliName),
      "info" => $info,
      "stack_trace" => $exc->getTrace(),
      "previous_exception" => ($exc->getPrevious() != null ? self::exceptionBuildLog($exc->getPrevious(), []) : null),
      "file" => $exc->getFile(),
      "line" => $exc->getLine()
    ];
  }

  /** 
   * Parse the /config.ini file and for each variable found, sets it on the environment variables if it does not exists already 
   * 
   * @return void 
   */
  private function loadConfigsFromFile()
  {
    if (file_exists(ROOT_PATH . "/config.ini")) {
      $configs = parse_ini_file(ROOT_PATH . "/config.ini", true);

      foreach ($configs as $section => $innerSettings) {
        foreach ($innerSettings as $var => $value) {
          if ($section == 'CUSTOM') {
            define(strtoupper($var), $value);
          } elseif ($section != "VENDORS") {
            if (empty(getenv(strtoupper($var)))) putenv(strtoupper($var) . '=' . $value);
          }
        }
      }
    }
  }

  /** 
   * Sets global constants from specific environment variables:
   * 
   * @return void 
   */
  private function setConfigsFromEnv()
  {
    // Define Database configuration constants:
    define('DB_CONNECT', getenv('DB_CONNECT'));
    define('DBNAME', getenv('DBNAME'));
    define('DBHOST', getenv('DBHOST'));
    define('DBPORT', !empty(getenv('DBPORT')) ? getenv('DBPORT') : 3306);
    define('DBUSER_MAIN', getenv('DBUSER_MAIN'));
    define('DBPASS_MAIN', getenv('DBPASS_MAIN'));
    define('DBUSER_READONLY', getenv('DBUSER_READONLY'));
    define('DBPASS_READONLY', getenv('DBPASS_READONLY'));
    define('DBTYPE', getenv('DBTYPE'));
    define('DB_TRANSACTIONAL', getenv('DB_TRANSACTIONAL'));
    define('DB_WORK_AROUND_FACTOR', getenv('DB_WORK_AROUND_FACTOR'));
    define('CACHE_DB_METADATA', getenv('CACHE_DB_METADATA'));
    define('DB_CHARSET', !empty(getenv('DB_CHARSET')) ? getenv('DB_CHARSET') : "utf8");

    // Define System configuration constants:
    define('APPLICATION_NAME', getenv('APPLICATION_NAME'));
    define('DEFAULT_ROUTE', getenv('DEFAULT_ROUTE'));
    define('DEFAULT_TIMEZONE', getenv('DEFAULT_TIMEZONE'));
    define('HANDLE_ERROR_TYPES', getenv('HANDLE_ERROR_TYPES'));
    define('APPLICATION_LOG', getenv('APPLICATION_LOG'));
    define('PRIVATE_KEY', getenv('PRIVATE_KEY'));
    define('PUBLIC_KEY', getenv('PUBLIC_KEY'));
    define('ALLOW_CORS', getenv('ALLOW_CORS'));
    define('MAX_LOG_ENTRIES', !empty(getenv('MAX_LOG_ENTRIES')) ? getenv('MAX_LOG_ENTRIES') : 5);
  }

  /** 
   * Remove entries from server's log until it reach the MAX_LOG_ENTRIES limit. The cleaning-up remove the oldest entries and leave the newer:
   * 
   * @return void 
   */
  private function serverLogCleanUp()
  {
    $path = __DIR__ . '/../application/log/server.log';

    if (file_exists($path)) {
      $rawData = array_filter(explode(PHP_EOL, file_get_contents($path)));

      if (count($rawData) > MAX_LOG_ENTRIES) {
        $rawData = array_slice($rawData, (MAX_LOG_ENTRIES * -1));
        file_put_contents($path, implode(PHP_EOL, $rawData) . PHP_EOL);
      }
    }
  }
}
