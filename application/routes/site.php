<?php
class Site extends Rest_service
{
  public function __construct()
  {
    parent::__construct();

    $this->set404template($this->theme . '/routenotfound', ['themePath' => $this->theme]);

    // Home Page Endpoints:
    $this->addEndpoint('GET', '/home', 'homepage');

    // 404 Route:
    $this->addEndpoint('GET', '/not-found', 'notFound');
  }

  public function homepage($params)
  {
    $response = new Response();

    return $response
    ->withStatus(200)
    ->withHTML($this->renderTemplate('site/home', []));
  }

  public function notFound($params)
  {
    $response = new Response();

    return $response->withStatus(200)->withHTML($this->renderTemplate($this->theme . '/routenotfound', ['themePath' => $this->theme]));
  }

}
