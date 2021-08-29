<?php
class Site extends Rest_service
{
  public function __construct()
  {
    parent::__construct();

    // Home Page Endpoints:
    $this->addEndpoint('GET', '/home/example', 'showHomepage');
    $this->addEndpoint('GET', '/home', 'showHomepage');
    
    $this->addEndpoint('GET', '/test', 'test');
  }

  public function showHomepage($params)
  {
    $response = new Response();
    $message = $this->getService('example')->welcomeMsg();

    return $response
      ->withStatus(200)
      ->withHTML($this->renderTemplate('site/home', ['message' => $message]));
  }

  public function test($params){
    $response = new Response();

    $data = $this->getTable('TST_TEST')->insert([
      'ds_key' => 'tst-'.uniqid(),
      'ds_first_name' => "Smith",
      'ds_last_name' => "Technology"
    ]);

    return $response->withData($data);
  }
}
