<?php

namespace application\routes;

use \engine\WebService;

class Site extends WebService
{
  public function init()
  {
    $this->setAntiXsrfValidation(false);

    // Home Page Endpoints:
    $this->addEndpoint('GET', '/home', function ($params) {
      $message = $this->getService('example')->welcomeMsg();

      return $this->response
        ->withStatus(200)
        ->withHTML($this->renderTemplate('site/home', ['message' => $message]));
    });
  }
}
