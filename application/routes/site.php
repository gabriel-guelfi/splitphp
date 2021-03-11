<?php
class Site extends Rest_service
{
  public function __construct()
  {
    parent::__construct();

    // Home Page Endpoints:
    $this->addEndpoint('GET', '/home/example', 'showHomepage');
    $this->addEndpoint('GET', '/home', 'showHomepage');
  }

  public function showHomepage($params)
  {
    $response = new Response();
    
    $message = $this->getService('example')->welcomeMsg();

    return $response
    ->withStatus(200)
    ->withHTML($this->renderTemplate('site/home', ['message' => $message]));
  }

}
