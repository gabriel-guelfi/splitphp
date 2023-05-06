<?php

namespace engine\commands;

use \engine\Cli;
use \engine\Utils;

class Help extends Cli
{
  public function init()
  {
    $this->addCommand('', function () {
      Utils::printLn("Test");

    });
  }
}
