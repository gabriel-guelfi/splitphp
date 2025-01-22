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
// Copyright (c) 2025 Lightertools Open Source Community                                                                                                               //
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

use \engine\databasemodules\mysql\Dbcnn;
use Exception;

class DbConnections
{
  private static $connections = [];

  public static function retrieve(string $cnnName, array $credentials = null)
  {
    if (!isset(self::$connections[$cnnName])) {
      if (empty($credentials)) throw new Exception("You need to provide credentials to establish a new database connection.");

      $dbType = DBTYPE;

      require_once __DIR__."/databasemodules/{$dbType}/class.dbcnn.php";

      self::$connections[$cnnName] = new Dbcnn(...$credentials);
    }

    return self::$connections[$cnnName];
  }

  public static function remove(string $cnnName)
  {
    if (isset(self::$connections[$cnnName])) {
      $cnn = self::retrieve($cnnName);
      $cnn->disconnect();
      unset(self::$connections[$cnnName]);

      return true;
    }
    return false;
  }

  public static function change(string $cnnName, array $credentials = null)
  {
    self::remove($cnnName);

    return self::retrieve($cnnName, $credentials);
  }

  public static function check(string $cnnName)
  {
    return isset(self::$connections[$cnnName]);
  }
}
