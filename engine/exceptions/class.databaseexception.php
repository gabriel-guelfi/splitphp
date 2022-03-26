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

/**
 * Class DatabaseException
 * 
 * This class represents an extension of exceptions for Database operations and is able to store the SQL command, so the developer can
 * make a more detailed analysis of the problem. 
 *
 * @package engine/exceptions
 */
class DatabaseException extends Exception
{
  /**
   * @var string $sqlstate
   * Stores the SQl State code.
   */
  private $sqlstate;

  /**
   * @var string $sqlcommand
   * Stores the SQl command.
   */
  private $sqlcommand;

  /** 
   * Runs Exception class constructor, sets common Exception properties with the data retrieved from the Exception object passed on $exc, 
   * set sqlstate property with the data passed on $sqlstate, set property sqlcommand with the value passed on $sqlcmd, then returns an 
   * instance of this class (constructor).
   * 
   * @param Exception $exc
   * @param string $sqlstate
   * @param string $sqlcmd
   * @return DatabaseException 
   */
  public function __construct(Exception $exc, string $sqlstate, string $sqlcmd = null)
  {
    parent::__construct($exc->getMessage(), $exc->getCode(), $exc->getPrevious());

    $this->message = $exc->getMessage();
    $this->code = $exc->getCode();
    $this->file = $exc->getFile();
    $this->line = $exc->getLine();

    $this->sqlstate = $sqlstate;
    $this->sqlcommand = $sqlcmd;
  }

  /** 
   * Returns a string representation of the instance.
   * 
   * @return string 
   */
  public function __toString()
  {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }
  
  /** 
   * Returns the value stored on DatabaseException::sqlstate.
   * 
   * @return string 
   */
  public function getSqlState()
  {
    return $this->sqlstate;
  }

  /** 
   * Returns the value stored on DatabaseException::sqlcmd.
   * 
   * @return string 
   */
  public function getSqlCmd()
  {
    return $this->sqlcommand;
  }
}
