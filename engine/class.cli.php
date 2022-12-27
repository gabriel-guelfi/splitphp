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

use Exception;
use stdClass;
use \engine\exceptions\DatabaseException;

/**
 * Class Cli
 * 
 * This class aims to provide an interface where the developer creates the application's CLI and defines its commands.
 *
 * @package engine
 */
abstract class Cli extends Service
{
  /**
   * @var array $commands
   * Stores a list of command strings.
   */
  protected $commands;

  /**
   * @var array $commandIndex
   * This is a summary for the $commands list.
   */
  protected $commandIndex;

  /**
   * @var Dblink $dblink
   * Stores an instance of the class Dblink, used to perform database connections and operations.
   */
  private $dblink;

  /**
   * @var int $timeStart
   * Stores the timestamp of the start of the command's execution.
   */
  private $timeStart;

  /**
   * @var int $timeEnd
   * Stores the timestamp of the end of the command's execution.
   */
  private $timeEnd;

  /**
   * @var string $cmdString
   * Stores the current execution's command string.
   */
  private $cmdString;

  /** 
   * Defines constants for user errors, set properties with their initial values, instantiate other classes, then returns an
   * instance of the CLI(constructor).
   * 
   * @return Cli 
   */
  public final function __construct()
  {
    $this->commands = [];
    $this->cmdString = "";
    $this->timeStart = 0;
    $this->timeEnd = 0;

    if (DB_CONNECT == 'on')
      $this->dblink = System::loadClass(ROOT_PATH . "/engine/databasemodules/" . DBTYPE . "/class.dblink.php", 'dblink');

    parent::__construct();
  }

  /** 
   * Returns a string representation of this class for printing purposes.
   * 
   * @return string 
   */
  public final function __toString()
  {
    return "class:Cli:" . __CLASS__ . "(started:{$this->timeStart}, Ended:{$this->timeEnd}, Command:{$this->cmdString})";
  }

  /** 
   * Searches for the command's string in added commands list then executes the 
   * handler method provided for the command.
   * 
   * @param string $cmdString
   * @param array $args = []
   * @return void 
   */
  public final function execute(string $cmdString, array $args = [], $innerExecution = false)
  {
    $this->cmdString = $cmdString;
    $this->timeStart = time();

    $commandData = $this->findCommand($cmdString);
    if (empty($commandData)) {
      throw new Exception("Command not found");
    }

    try {
      if (!$innerExecution) {
        echo PHP_EOL;
        Utils::printLn("*------*------*------*------*------*------*------*");
        Utils::printLn("[SPLITPHP CONSOLE] Command execution started.");
        Utils::printLn("*------*------*------*------*------*------*------*");
        echo PHP_EOL;
      }

      $commandHandler = is_callable($commandData->method) ? $commandData->method : [$this, $commandData->method];

      if (DB_CONNECT == "on" && DB_TRANSACTIONAL == "on" && !$innerExecution) {
        $this->dblink->getConnection('writer')->startTransaction();
        call_user_func_array($commandHandler, [$this->prepareArgs($args)]);
        $this->dblink->getConnection('writer')->commitTransaction();
      } else {
        call_user_func_array($commandHandler, [$this->prepareArgs($args)]);
      }

      $this->timeEnd = time();
      $durationTime = $this->timeEnd - $this->timeStart;
      if (!$innerExecution) {
        echo PHP_EOL;
        Utils::printLn("*------*------*------*------*------*------*------*");
        Utils::printLn("[SPLITPHP CONSOLE] Command execution finished. Run time duration: {$durationTime} second(s).");
        Utils::printLn("*------*------*------*------*------*------*------*");
        echo PHP_EOL;
      }
    } catch (Exception $exc) {
      if (DB_CONNECT == "on" && DB_TRANSACTIONAL == "on" && $this->dblink->checkConnection('writer'))
        $this->dblink->getConnection('writer', false)->rollbackTransaction();

      if (APPLICATION_LOG == "on") {
        if ($exc instanceof DatabaseException) {
          System::errorLog('db_error', $exc, [
            'sqlState' => $exc->getSqlState(),
            'sqlCommand' => $exc->getSqlCmd()
          ]);
          echo "ERROR[Database]: " . $exc->getMessage() . ". In file '" . $exc->getFile() . "', line " . $exc->getLine() . ".";
          echo PHP_EOL;
        } else {
          System::errorLog('application_error', $exc);
          echo "ERROR[Application]: " . $exc->getMessage() . ". In file '" . $exc->getFile() . "', line " . $exc->getLine() . ".";
          echo PHP_EOL;
        }
      }
    } finally {
      if (DB_CONNECT == "on")
        $this->dblink->disconnect('writer');
    }
  }

  /** 
   * Registers a command on the list $commands, in other words: makes a command available within the CLI, with the 
   * handler method provided.
   * 
   * @param string $cmdString
   * @param mixed $method
   * @return void 
   */
  protected final function addCommand(string $cmdString, $method)
  {
    $cmdString = substr($cmdString, 0, 1) === ':' ? $cmdString : ":" . $cmdString;

    if (!empty($this->findCommand($cmdString)))
      throw new Exception("Attempt to add duplicate command (same command within a single CLI). (" . self::class . " -> " . $cmdString . ")");

    $this->commands[$cmdString] = (object) [
      "command" => $cmdString,
      "method" => $method
    ];
  }

  /** 
   * Runs another command from within a command, based on the received command string. 
   * Returns the executed command's returned value.
   * 
   * @param string $cmdString
   * @return mixed 
   */
  protected final function run(string $cmdString)
  {
    $action = new Action(['console', ...explode(" ", $cmdString)]);
    if ($action->getCmd() == $this->cmdString) throw new Exception("You cannot run a command from within itself");

    $CliObj = System::loadClass($action->getCli()->path . $action->getCli()->name . ".php", $action->getCli()->name);
    return call_user_func_array(array($CliObj, 'execute'), [...$action->getArgs(), true]);
  }

  /** 
   * Using the command's string, searches for a command in the commands list. Returns the command data or null, in case of not founding it.
   * 
   * @param string $cmdString
   * @return object
   */
  private function findCommand(string $cmdString)
  {
    if (array_key_exists($cmdString, $this->commands)) return $this->commands[$cmdString];

    return null;
  }

  /** 
   * Normalizes args from CLI input, setting the final array in the pattern "key=value".
   * 
   * @param array $args
   * @return array
   */
  private function prepareArgs(array $args)
  {
    $result = [];
    foreach ($args as $arg) {
      if (is_string($arg) && strpos($arg, '=') !== false) {
        $argData = explode('=', $arg);
        $result[$argData[0]] = $argData[1];
      } else $result[] = $arg;
    }

    return $result;
  }

  /** 
   * Nullify string representations od empty values, like 'null' or 'undefined', then returns the modified dataset.
   * 
   * @param mixed $data
   * @return mixed
   */
  private function actualizeEmptyValues($data)
  {
    foreach ($data as $key => $value) {
      if (gettype($value) == 'array' || (gettype($value) == 'object' && $value instanceof StdClass)) {
        $data[$key] = $this->actualizeEmptyValues($data[$key]);
        continue;
      }

      if ($value === 'null' || $value === 'undefined') $data[$key] = null;
    }

    return $data;
  }
}
