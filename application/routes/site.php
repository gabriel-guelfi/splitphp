<?php

namespace application\routes;

use \engine\RestService;

class Site extends RestService
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
