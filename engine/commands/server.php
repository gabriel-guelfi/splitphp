<?php

namespace engine\commands;

use \engine\Cli;
use \engine\Utils;

class Server extends Cli
{
  public function init()
  {
    $this->addCommand('start', function ($input) {

      $port = empty($input['port']) ? '8000' : $input['port'];

      Utils::printLn("Starting server at localhost in port {$port}.");
      Utils::printLn();
      Utils::printLn("IMPORTANT: This server is intended for DEVELOPMENT PURPOSE ONLY. For production, we encourage you to use some solution like NGINX or APACHE web server.");
      Utils::printLn();

      exec("php -S 0.0.0.0:{$port} -t ".ROOT_PATH.'/public');
    });
  }
}
