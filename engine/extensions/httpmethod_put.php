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

function _parsePut()
{
  global $_PUT;

  if ($_SERVER['REQUEST_METHOD'] != 'PUT') {
    $GLOBALS['_PUT'] = [];
    return;
  }

  /* PUT data comes in on the stdin stream */
  $putdata = fopen("php://input", "r");

  /* Open a file for writing */
  // $fp = fopen("myputfile.ext", "w");

  $raw_data = '';

  /* Read the data 1 KB at a time
       and write to the file */
  while ($chunk = fread($putdata, 1024))
    $raw_data .= $chunk;

  /* Close the streams */
  fclose($putdata);

  if (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $GLOBALS['_PUT'] = json_decode($raw_data, true);
    $_REQUEST = array_merge($GLOBALS['_PUT'], $_REQUEST);
    return;
  }

  // Fetch content and determine boundary
  $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

  if (empty($boundary)) {
    parse_str($raw_data, $data);
    $GLOBALS['_PUT'] = $data;
    $_REQUEST = array_merge($GLOBALS['_PUT'], $_REQUEST);
    return;
  }

  // Fetch each part
  $parts = array_slice(explode($boundary, $raw_data), 1);
  $data = array();

  foreach ($parts as $part) {
    // If this is the last part, break
    if ($part == "--\r\n") break;

    // Separate content from headers
    $part = ltrim($part, "\r\n");
    list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

    // Parse the headers list
    $raw_headers = explode("\r\n", $raw_headers);
    $headers = array();
    foreach ($raw_headers as $header) {
      list($name, $value) = explode(':', $header);
      $headers[strtolower($name)] = ltrim($value, ' ');
    }

    // Parse the Content-Disposition to get the field name, etc.
    if (isset($headers['content-disposition'])) {
      $filename = null;
      $tmp_name = null;
      preg_match(
        '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
        $headers['content-disposition'],
        $matches
      );
      list(, $type, $name) = $matches;

      //Parse File
      if (isset($matches[4])) {
        //if labeled the same as previous, skip
        if (isset($_FILES[$matches[2]])) {
          continue;
        }

        //get filename
        $filename = $matches[4];

        //get tmp name
        $filename_parts = pathinfo($filename);
        $tmp_name = tempnam(ini_get('upload_tmp_dir'), $filename_parts['filename']);

        //populate $_FILES with information, size may be off in multibyte situation
        $_FILES[$matches[2]] = array(
          'error' => 0,
          'name' => $filename,
          'tmp_name' => $tmp_name,
          'size' => strlen($body),
          'type' => $value
        );

        //place in temporary directory
        file_put_contents($tmp_name, $body);
      }
      //Parse Field
      else {
        $data[$name] = substr($body, 0, strlen($body) - 2);
      }
    }
  }
  $GLOBALS['_PUT'] = $data;
  $_REQUEST = array_merge($GLOBALS['_PUT'], $_REQUEST);
  return;
}
_parsePut();
