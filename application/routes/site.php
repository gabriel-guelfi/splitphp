<?php
class Site extends RestService
{
  public function __construct()
  {
    parent::__construct();

    $this->setAntiXsrfValidation(false);

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
      ->withtext("Hello fucking world!");
    // return $response
    //   ->withStatus(200)
    //   ->withHTML($this->renderTemplate('site/home', ['message' => $message]));
  }
}
