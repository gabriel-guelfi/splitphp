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
    if (!array_key_exists($evtName, self::$events)) self::discoverEvents();

    $evt = self::$events[$evtName];

    $evtObj = ObjLoader::load($evt->classPath, $evt->className, $data);

    foreach (self::$listeners as $key => $listener) {
      if (strpos($key, $evtName) !== false) {
        $callback = $listener->callback;
        call_user_func_array($callback, [$evtObj]);
      }
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
}
