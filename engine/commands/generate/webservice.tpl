<?php

namespace __NAMESPACE__;

use \engine\WebService;

class __CLASSNAME__ extends WebService
{
  public function init()
  {
    /*
     * You can use one of the following HTTP verbs: "GET", "POST", "PUT", "DELETE",
     * define the endpoint's route and a handler function, that will be executed when 
     * the endpoint is reached. The $input parameter is an associative array containing
     * the entire body of the request, plus any parameter passed in the route(in this example
     * we have a "username" parameter passed in the route that can be accessed in $input['username'])
     * For more info, refer to SPLIT PHP docs at www.splitphp.org/docs. 
     */
    $this->addEndpoint('GET', '/say-hello/?username?', function ($input) {
      $msg = "Hello, {$input['username']}!";

      return $this->response
        ->withStatus(200)
        ->withText($msg);
    });
  }
}
