<?php

namespace application\routes;

use engine\Helpers;
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

    $this->addEndpoint('GET', '/', function () {
      $response = Helpers::cURL()
        ->get('https://api.sampleapis.com/beers/ale');

      return $this->response->withData($response);
    });
  }
}
