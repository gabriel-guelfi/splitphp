<?php

namespace engine\helpers;

use \Exception;
use \stdClass;
use \engine\System;

class Log
{
  /** 
   * Creates a log file under /application/log with the specified $logname, writing down $logmsg with the current datetime 
   * 
   * @param string $logname
   * @param mixed $logmsg
   * @param boolean $limit
   * @return void 
   */
  public function add(string $logname, $logmsg, $limit = true)
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

    if (file_exists($path . $logname . '.log'))
      $currentLogData = array_filter(explode(str_repeat(PHP_EOL, 2), file_get_contents($path . $logname . '.log')));
    else $currentLogData = [];

    if (count($currentLogData) >= MAX_LOG_ENTRIES && $limit) {
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
  public function error(string $logname, Exception $exc, array $info = [])
  {
    $this->add($logname, $this->exceptionBuildLog($exc, $info));
  }

  /** 
   * Using the information of the exception received in $exc, and the extra $info, builds a fittable 
   * error log object to be used as $logmsg.  
   * 
   * @param Exception $exc
   * @param array $info
   * @return void 
   */
  private function exceptionBuildLog(Exception $exc, array $info)
  {
    return (object) [
      "datetime" => date('Y-m-d H:i:s'),
      "message" => $exc->getMessage(),
      "file" => $exc->getFile(),
      "line" => $exc->getLine(),
      "webService" => System::$webservicePath,
      "cli" => System::$cliPath,
      "route" => System::$route,
      "httpVerb" => System::$httpVerb,
      "request" => $_REQUEST,
      "info" => $info,
      "stack_trace" => $exc->getTrace(),
      "previous_exception" => ($exc->getPrevious() != null ? $this->exceptionBuildLog($exc->getPrevious(), []) : null),
    ];
  }
}
