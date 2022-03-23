<?php
class Request
{
  private $route;
  private $restServicePath;
  private $restServiceName;
  private $args;

  public function __construct(string $uri)
  {
    $urlElements = explode("/", str_replace(strrchr($uri, "?"), "", urldecode($uri)));
    array_shift($urlElements);

    $this->setRestService('/application/routes/', $urlElements);

    $this->args = [
      $this->route,
      $_SERVER['REQUEST_METHOD']
    ];
  }

  public function getRoute()
  {
    return $this->route;
  }

  public function getRestService()
  {
    return (object) [
      "name" => $this->restServiceName,
      "path" => $this->restServicePath
    ];
  }

  public function getArgs()
  {
    return $this->args;
  }

  public static function getUserIP()
  {
    //whether ip is from the share internet  
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    //whether ip is from the proxy  
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    //whether ip is from the remote address  
    else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  private function setRestService(string $path, $urlElements)
  {
    $basePath = "";
    if (strpos($path, INCLUDE_PATH)) {
      $basePath = $path;
    } else {
      $basePath = INCLUDE_PATH . $path;
    }

    if (empty($urlElements[0])) {
      $this->restServicePath = $basePath;
      $this->restServiceName = DEFAULT_REST_SERVICE;
      $this->route = DEFAULT_ROUTE;

      return;
    }

    foreach ($urlElements as $i => $urlPart) {
      if (is_dir($basePath . $urlPart))
        $basePath .= $urlPart.'/';
      elseif (is_file($basePath . $urlPart . '.php')) {
        $this->restServicePath = $basePath;
        $this->restServiceName = $urlPart;
        $this->route = '/'.implode('/', array_slice($urlElements, $i +1));
        break;
      }
    }
  }
}
