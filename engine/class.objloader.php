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

use Exception;
use ReflectionClass;

/**
 * Class ObjLoader
 * 
 * This class is responsible loading the classes's objects, respecting the singleton OOP concept.
 *
 * @package engine
 */
class ObjLoader
{

  /**
   * @var array $collection
   * Stores a collection of already loaded objects.
   */
  private static $collection = [];

  /** 
   * Returns the instance of a class registered on the collection. If the class instance isn't registered yet, 
   * create a new instance of that class, register it on the collection, then returns it.
   * 
   * @param string $path
   * @param string $classname
   * @param array $args = []
   * @return mixed 
   */
  public static final function load(string $path, string $classname, array $args = [])
  {
    $arrClassPath = explode("/", str_replace(ROOT_PATH, "", $path));
    unset($arrClassPath[count($arrClassPath) - 1]);
    $classFullName = implode('\\', $arrClassPath).'\\'.ucfirst($classname);

    if (!isset(self::$collection[$classFullName])) {
      try {
        include_once $path;

        $r = new ReflectionClass($classFullName);
        self::$collection[$classFullName] = $r->newInstanceArgs($args);
      } catch (Exception $ex) {
        throw $ex;
      }
    }

    return self::$collection[$classFullName];
  }
}
