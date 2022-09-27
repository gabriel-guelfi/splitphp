<?php

namespace application\commands;

use \engine\Cli;
use \engine\Utils;

class Sample extends Cli
{
  public function init()
  {
    $this->addCommand('hello', function ($args) {
      Utils::printLn($this->getService('example')->welcomeMsg($args['name']));
    });
  }
}
