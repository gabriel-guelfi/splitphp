<?php

namespace engine;

use Exception;

class EventListener extends Service
{
  private static $events = [];
  private static $listeners = [];

  public final function __construct()
  {
    // Find all events that exists and load them into self::$events array.
    self::discoverEvents();

    // Invoke Service's contructor:
    parent::__construct();
  }

  protected final function addEventListener(string $evtName, callable $callback)
  {
    $evtId = "evt-" . uniqid() . "-" . $evtName;
    self::$listeners[$evtId] = (object) [
      'evtName' => $evtName,
      'callback' => $callback
    ];

    return $evtId;
  }

  public static final function removeEventListener($evtId)
  {
    unset(self::$listeners[$evtId]);
  }

  public static final function eventRemoveListeners($evtName)
  {
    foreach (self::$listeners as $key => $listener) {
      if (strpos($key, $evtName) !== false)
        unset(self::$listeners[$key]);
    }
  }

  public static final function triggerEvent(string $evtName, array $data = [])
  {
    try {
      if (!array_key_exists($evtName, self::$events)) self::discoverEvents();

      $evt = self::$events[$evtName];

      $evtObj = ObjLoader::load($evt->classPath, $evt->className, $data);

      if (DB_CONNECT == "on" && DB_TRANSACTIONAL == "on")
        DbConnections::retrieve('main')->startTransaction();

      foreach (self::$listeners as $key => $listener) {
        if (strpos($key, $evtName) !== false) {
          $callback = $listener->callback;
          call_user_func_array($callback, [$evtObj]);
        }
      }

      if (DB_CONNECT == "on" && DB_TRANSACTIONAL == "on")
        DbConnections::retrieve('main')->commitTransaction();
    } catch (Exception $exc) {
      if (DB_CONNECT == "on" && DB_TRANSACTIONAL == "on" && DbConnections::check('main'))
        DbConnections::retrieve('main')->rollbackTransaction();

      if (APPLICATION_LOG == "on") {
        Helpers::Log()->error('event_error', $exc);
      }

      $status = self::userFriendlyErrorStatus($exc);
      http_response_code($status);
      echo json_encode([
        "error" => true,
        "user_friendly" => $status !== 500,
        "message" => $exc->getMessage(),
        "webService" => System::$webservicePath,
        "requestinfo" => $_REQUEST,
      ]);
      die;
    }
  }

  private static function discoverEvents()
  {
    $eventFiles = [];

    // List all built-in events's class paths:
    $eventFiles = [...$eventFiles, ...self::listFilesWithinPath(ROOT_PATH . "/engine/events/")];

    // List all built-in events's class paths: 
    $eventFiles = [...$eventFiles, ...self::listFilesWithinPath(ROOT_PATH . "/application/events/")];

    foreach ($eventFiles as $classPath) {
      $content = file_get_contents($classPath);
      if (empty($content)) continue;

      // Use regex to extract the class name
      if (preg_match('/class\s+([a-zA-Z0-9_]+)/', $content, $matches)) {
        $className = $matches[1];
      }

      // Match the EVENT_NAME constant
      $eventName = null;
      if (preg_match('/const\s+EVENT_NAME\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $content, $eventNameMatches)) {
        $eventName = $eventNameMatches[1];
      }
      if (empty($eventName)) {
        throw new Exception("Event class {$className} must implement a public constant 'EVENT_NAME' with a valid name for it");
      }

      self::$events[$eventName] = (object) [
        'evtName' => $eventName,
        'classPath' => $classPath,
        'className' => $className
      ];
    }
  }

  private static function listFilesWithinPath($dirPath)
  {
    $paths = [];

    if (is_dir($dirPath)) {
      $dirHandle = opendir($dirPath);
      while (($f = readdir($dirHandle)) !== false)
        // Combine $dirPath and $file to retrieve fully qualified class path:
        if ($dirPath . $f != '.' && $dirPath . $f != '..' && is_file($dirPath . $f))
          $paths[] = $dirPath . $f;

      closedir($dirHandle);
    }
    return $paths;
  }

  /** 
   * Returns an integer representing a specific http status code for predefined types of exceptions. Defaults to 500.
   * 
   * @param Exception $exc
   * @return integer
   */
  private static function userFriendlyErrorStatus(Exception $exc)
  {
    switch ($exc->getCode()) {
      case (int) VALIDATION_FAILED:
        return 422;
        break;
      case (int) BAD_REQUEST:
        return 400;
        break;
      case (int) NOT_AUTHORIZED:
        return 401;
        break;
      case (int) NOT_FOUND:
        return 404;
        break;
      case (int) PERMISSION_DENIED:
        return 403;
        break;
      case (int) CONFLICT:
        return 409;
        break;
    }

    return 500;
  }
}
