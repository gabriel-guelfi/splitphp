<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                                                                                                //
//                                                                ** SPLIT PHP FRAMEWORK **                                                                       //
// This file is part of *SPLIT PHP Framework*                                                                                                                     //
//                                                                                                                                                                //
// Why "SPLIT"? Firstly because the word "split" is a reference to micro-services and split systems architecture (of course you can make monoliths with it,       //
// if that's your thing). Furthermore, it is an acronym for these 5 bound concepts which are the bases that this framework leans on, which are: "Simplicity",     //
// "Purity", "Lightness", "Intuitiveness", "Target Minded"                                                                                                        //
//                                                                                                                                                                //
// See more info about it at: https://github.com/gabriel-guelfi/split-php                                                                                         //
//                                                                                                                                                                //
// MIT License                                                                                                                                                    //
//                                                                                                                                                                //
// Copyright (c) 2022 SPLIT PHP Framework Community                                                                                                               //
//                                                                                                                                                                //
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to          //
// deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or         //
// sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:                            //
//                                                                                                                                                                //
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.                                 //
//                                                                                                                                                                //
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,FITNESS     //
// FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY           //
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.     //
//                                                                                                                                                                //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

namespace engine;

use \Exception;

/**
 * Class Action
 * 
 * This class is for capturing the incoming cli commands and managing its informations.
 *
 * @package engine
 */
class Action
{
  /**
   * @var string $cmd
   * Stores the current accessed command.
   */
  private $cmd;

  /**
   * @var string $cliPath
   * Stores the defined Cli class path.
   */
  private $cliPath;

  /**
   * @var string $cliName
   * Stores the defined Cli class name.
   */
  private $cliName;

  /**
   * @var array $args
   * Stores the parameters and data passed along the request.
   */
  private $args;

  /** 
   * Parse the incoming $argv, separating, Cli's path and arguments. Returns an instance of the Action class (constructor).
   * 
   * @param array $args
   * @return Action 
   */
  public final function __construct(array $args)
  {
    $this->cmd = $args[1];
    array_shift($args);
    array_shift($args);
    $cmdElements = explode(":", $this->cmd);

    $this->cliFindAndSet('/application/commands/', $cmdElements);

    $this->args = [
      $this->cmd,
      $args
    ];
  }

  /** 
   * Returns the stored command.
   * 
   * @return string 
   */
  public function getCmd()
  {
    return $this->cmd;
  }

  /** 
   * Returns an object containing the name and the path of the Cli class.
   * 
   * @return object 
   */
  public function getCli()
  {
    return (object) [
      "name" => $this->cliName,
      "path" => $this->cliPath
    ];
  }

  /** 
   * Returns the parameters and data passed along the command.
   * 
   * @return array 
   */
  public function getArgs()
  {
    return $this->args;
  }

  /** 
   * Using $path as a base, loops through the $cmdElements searching for a valid Cli filepath. Once it is found, define the 
   * Cli's path and name, and the rest of the remaining elements up to that point are defined as the command itself.
   * 
   * @param string $path
   * @param array $cmdElements
   * @return void 
   */
  private function cliFindAndSet(string $path, array $cmdElements)
  {
    $basePath = "";
    if (strpos($path, INCLUDE_PATH)) {
      $basePath = $path;
    } else {
      $basePath = INCLUDE_PATH . $path;
    }

    foreach ($cmdElements as $i => $cmdPart) {
      if (is_dir($basePath . $cmdPart))
        $basePath .= $cmdPart . '/';
      elseif (is_file($basePath . $cmdPart . '.php')) {
        $this->cliPath = $basePath;
        $this->cliName = $cmdPart;
        $this->cmd = ":".implode(':', array_slice($cmdElements, $i + 1));
        break;
      } else {
        throw new Exception("Command not found.");
      }
    }
  }
}
