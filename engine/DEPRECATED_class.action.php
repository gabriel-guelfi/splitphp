<?php

class Action
{
  private $route;
  private $classPath;
  public $className;
  public $methodName;
  private $args;
  private $utils;

  public function __construct(string $uri, array $routeConfigs = array(), $args = array())
  {
    $this->utils = System::LoadClass(__DIR__ . "/class.utils.php", "utils");

    $urlElements = explode("/", str_replace(strrchr($uri, "?"), "", urldecode($uri)));
    array_shift($urlElements);

    if (System::getExecEnv() == '/api') { // <<-- Remove
      $this->setClassPath('/api/routes/');
      $this->className = $urlElements[0];
      $this->methodName = 'execute'; // <<-- Remove

      $this->route = implode('/', $urlElements);

      array_shift($urlElements);
      $this->args = [
        '/'.implode('/', $urlElements),
        $_SERVER['REQUEST_METHOD']
      ];

      return;
    } else {
      $this->setClassPath('/application/controllers/');
      $urlElements = $this->setRoute($urlElements, $routeConfigs);
    }

    try {
      $_args = array_merge(array_slice($urlElements, 2), $args);
      $this->setArgs($_args);
    } catch (Exception $ex) {
      System::debug($ex, get_defined_vars());
    }
  }

  public function setClassPath(string $path)
  {
    if (strpos($path, INCLUDE_PATH)) {
      $this->classPath = $path;
    } else {
      $this->classPath = INCLUDE_PATH . $path;
    }
  }

  public function getRoute()
  {
    return $this->route;
  }

  public function getClassPath()
  {
    return $this->classPath;
  }

  public function getArgs()
  {
    return $this->args;
  }

  public function getAction()
  {
    $r = (object) get_object_vars($this);
    unset($r->utils);
    return $r;
  }

  private function setRoute(array $urlElements, array $routeConfigs)
  {
    if (!empty($routeConfigs)) {
      if (array_key_exists($urlElements[0], $routeConfigs)) {
        $arr_route = explode("/", $routeConfigs[$urlElements[0]]);

        unset($urlElements[0]);

        for ($i = count($arr_route) - 1; $i >= 0; $i--) {
          array_unshift($urlElements, $arr_route[$i]);
        }
      }
    }

    $this->route = implode('/', $urlElements);

    if (!empty($_REQUEST["controller"])) {
      $this->className = $_REQUEST["controller"];
    } elseif (!empty($urlElements[0])) {
      $this->className = $urlElements[0];
    } else {
      $this->className = DEFAULT_CONTROLLER;
    }

    if (!empty($_REQUEST["method"])) {
      $this->methodName = $_REQUEST["method"];
    } elseif (!empty($urlElements[1])) {
      $this->methodName = $urlElements[1];
    } else {
      $this->methodName = DEFAULT_METHOD;
    }

    return $urlElements;
  }

  private function sanitizeRequest()
  {
    if (!empty($_GET)) {
      foreach ($_GET as $key =>  $val) {
        if ($val == "null" || $val == "undefined") {
          $_GET[$key] = null;
        }
      }
    }

    if (!empty($_POST)) {
      foreach ($_POST as $key =>  $val) {
        if ($val == "null" || $val == "undefined") {
          $_POST[$key] = null;
        }
      }
    }

    if (!empty($_REQUEST)) {
      foreach ($_REQUEST as $key =>  $val) {
        if ($val == "null" || $val == "undefined") {
          $_REQUEST[$key] = null;
        }
      }
    }
  }

  private function setArgs(array $args)
  {
    foreach ($args as $k => $v) {
      if ($v === "")
        unset($args[$k]);
    }
    $this->args = array_values($args);

    $this->sanitizeRequest();

    if (isset($_REQUEST["args"])) {
      if (!is_array($_REQUEST["args"])) {
        throw new Exception('If you have a request data named "args", it must be of array type. (Ex.: $_POST["args"]["argumentName"]) ' . ucfirst(gettype($_REQUEST["args"])) . ' given.');
      }
      $this->args = $_REQUEST["args"];
      $_REQUEST = [];
    } elseif (!empty($_POST)) {
      if (isset($_POST['controller']))
        unset($_POST['controller']);
      if (isset($_POST['method']))
        unset($_POST['method']);

      array_push($this->args, $_POST);
      $_POST = [];
    } elseif (!empty($_GET)) {
      if (isset($_GET['controller']))
        unset($_GET['controller']);
      if (isset($_GET['method']))
        unset($_GET['method']);

      array_push($this->args, $_GET);
      $_GET = [];
    }
  }
}
