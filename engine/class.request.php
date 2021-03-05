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

  private function setRestServicePath(string $path)
  {
    if (strpos($path, INCLUDE_PATH)) {
      $this->restServicePath = $path;
    } else {
      $this->restServicePath = INCLUDE_PATH . $path;
    }
  }
}
