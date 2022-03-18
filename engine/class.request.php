<?php
class Request
{
  private $route;
  private $restServicePath;
  private $restServiceName;
  private $args;

  public function __construct(string $uri, array $routeAlias = [])
  {
    $urlElements = explode("/", str_replace(strrchr($uri, "?"), "", urldecode($uri)));
    array_shift($urlElements);

    if (!empty($routeAlias)) {
      if (array_key_exists($urlElements[0], $routeAlias)) {
        $urlElements = explode('/', $routeAlias[$urlElements[0]]);
        if (empty($urlElements[0])) array_shift($urlElements);
      }
    }

    $this->setRestServicePath('/application/routes/');

    if (empty($urlElements[0])) {
      $this->restServiceName = DEFAULT_REST_SERVICE;
      $this->route = DEFAULT_ROUTE;
    } else {
      $this->restServiceName = $urlElements[0];
      array_shift($urlElements);
      $this->route = '/' . implode('/', $urlElements);
    }

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

  public static function getUserIP(){
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

  private function setRestServicePath(string $path)
  {
    if (strpos($path, INCLUDE_PATH)) {
      $this->restServicePath = $path;
    } else {
      $this->restServicePath = INCLUDE_PATH . $path;
    }
  }
  
}
