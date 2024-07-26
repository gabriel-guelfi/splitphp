<?php

namespace engine\commands\generate;

use \engine\Cli;
use \engine\Utils;

class Webservice extends Cli
{
  public function init()
  {

    $this->addCommand('', function ($input) {
      Utils::printLn("Hello and welcome to the SPLIT PHP scafolding tool!");
      Utils::printLn();
      // Opens up Service command help menu:
      if (isset($input[0]) && ($input[0] == '--help' || $input[0] == '-h')) {
        $this->showHelp('webservice');
        return;
      }

      if (!$this->askProceed('Web Service')) return;
      Utils::printLn();

      $pathName = !empty($input['name']) ? $input['name'] : $this->setPathname();
      if (strpos($pathName, '/')) {
        $pathName = explode('/', $pathName);
        $webserviceName = array_pop($pathName);

        $namespace = 'application\routes\\' . implode('\\', $pathName);
        $path = ROOT_PATH . '/application/routes/' . implode('/', $pathName);
        if (!is_dir($path)) mkdir($path, 0775, true);
      } else {
        $webserviceName = $pathName;
        $namespace = 'application\routes';
        $path = ROOT_PATH . '/application/routes';
      }

      $content = file_get_contents(__DIR__ . '/webservice.tpl');
      $content = str_replace('__NAMESPACE__', $namespace, $content);
      $content = str_replace('__CLASSNAME__', ucfirst($webserviceName), $content);
      file_put_contents("{$path}/{$webserviceName}.php", $content);

      Utils::printLn();
      Utils::printLn("Your new Web Service was created at \"{$path}/{$webserviceName}.php\"");
      Utils::printLn();
    });
  }

  private function showHelp($type)
  {
    $helpItems = [
      'service' => [
        'modes' => [
          '--help' => 'Show this menu.',
          '-h' => 'The shortcut form of "--help".'
        ],
        'arguments' => [
          'name' => 'the pathname of the Service. This must be a valid file/dir URI and you can make use of "/" to put your Service inside a specific directory. If the directory tree does not exist, it will be created automatically.'
        ],
      ],
      'webservice' => [
        'modes' => [
          '--help' => 'Show this menu.',
          '-h' => 'The shortcut form of "--help".'
        ],
        'arguments' => [
          'name' => 'the pathname of the Web Service. This must be a valid file/dir URI and you can make use of "/" to put your Web Service inside a specific directory. If the directory tree does not exist, it will be created automatically.'
        ],
      ],
      'cli' => [
        'modes' => [
          '--help' => 'Show this menu.',
          '-h' => 'The shortcut form of "--help".'
        ],
        'arguments' => [
          'name' => 'the pathname of the CLI. This must be a valid file/dir URI and you can make use of "/" to put your CLI inside a specific directory. If the directory tree does not exist, it will be created automatically.'
        ],
      ],
    ];

    Utils::printLn("//// HELP MENU ////");
    Utils::printLn();
    Utils::printLn("Scafolding command syntax: generate:[option] [?mode] [?argument]");
    Utils::printLn("  -> Available Options:");
    Utils::printLn("    * service: Generates a Service for your application.");
    Utils::printLn("    * webservice: Generates a Web Service for your application.");
    Utils::printLn("    * cli: Generates a CLI for your application.");
    Utils::printLn();
    Utils::printLn("Scafolding Available Modes:");
    foreach ($helpItems[$type]['modes'] as $mode => $msg) {
      Utils::printLn("  * {$mode}: {$msg}");
    }
    Utils::printLn();
    Utils::printLn("Scafolding Available arguments:");
    foreach ($helpItems[$type]['arguments'] as $arg => $msg) {
      Utils::printLn("  * {$arg}: {$msg}");
    }
  }

  private function askProceed($fileType)
  {
    $proceed = strtoupper(readline("- You're about to generate a {$fileType} into your application. Proceed? (\"Y\"=Yes; \"N\"=No;)"));
    if ($proceed == 'Y') return true;
    elseif ($proceed == 'N') return false;
    else {
      Utils::printLn("Sorry! I couldn't understand your answer.");
      Utils::printLn('Use "Y" for "yes" and "N" for "no".');
      return $this->askProceed($fileType);
    }
  }

  private function setPathname()
  {
    Utils::printLn("Now you will define the pathname of your Web Service.");
    Utils::printLn("(To find more about the pathname of a Wweb Service, type 'generate:webservice --help'.)");
    $pathname = readline("What is the full pathname of your Web Service?");
    if (empty($pathname)) {
      Utils::printLn("A Web Service's pathname cannot be blank or empty.");
      return $this->setPathname();
    }

    return $pathname;
  }
}
