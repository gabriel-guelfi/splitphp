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

class Response
{
  private $status;
  private $contentType;
  private $data;
  private $headers;

  public function __construct()
  {
    $this->status = 200;
    $this->contentType = 'text/plain';
    $this->data = null;
    $this->headers = [];
  }

  public function setHeader(string $header)
  {
    $this->headers[] = $header;
    return $this;
  }

  public function withStatus(int $code)
  {
    $this->status = $code;
    return $this;
  }

  public function withText($text, $escape = true)
  {
    $this->data = $escape ? $this->escapeOutput($text) : $text;
    return $this;
  }

  public function withData($data, $escape = true)
  {
    $this->contentType = 'application/json';
    $this->data = $escape ? json_encode($this->escapeOutput($data)) : json_encode($data);
    return $this;
  }

  public function withHTML(string $content)
  {
    $this->contentType = 'text/html; charset=utf-8';
    $this->data = $content;
    return $this;
  }

  public function withXMLData($data)
  {
    $this->contentType = 'application/xml';
    $this->data = Utils::XML_encode($data);
    return $this;
  }

  public function getHeaders()
  {
    return $this->headers;
  }

  public function getStatus()
  {
    return $this->status;
  }

  public function getContentType()
  {
    return $this->contentType;
  }

  public function getData()
  {
    return $this->data;
  }

  private function escapeOutput($payload)
  {
    if (is_array($payload) || (gettype($payload) == 'object'))
      foreach ($payload as &$value) {
        if (gettype($value) == 'array' || (gettype($value) == 'object')) {
          $value = $this->escapeOutput($value);
          continue;
        }

        if (!is_numeric($value))
          $value = htmlspecialchars($value);
      }
    else{
        if (!is_numeric($payload))
          $payload = htmlspecialchars($payload);
    }

    return $payload;
  }
}
