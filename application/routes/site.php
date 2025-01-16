<?php

namespace application\routes;

use engine\DbConnections;
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
      // DbConnections::change('readonly', [
      //   'api.erp-mqvending.com.br',
      //   3306,
      //   'mqvending',
      //   'mqvending_prod_ro',
      //   'beVr6f8u1I!m@lpq'
      // ]);

      $data = $this->getDao('APM_MODULE')
        ->find();

      return $this->response->withData($data);
    });
  }
}
