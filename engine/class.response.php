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
 * Class Response
 * 
 * This class manages the response's information.
 *
 * @package engine
 */
class Response
{
  /**
   * @var integer $status
   * Stores the response's status code.
   */
  private $status;
  
  /**
   * @var string $contentType
   * A string containing the response's content type header.
   */
  private $contentType;

  /**
   * @var mixed $data
   * Stores the payload which will be sent on the response.
   */
  private $data;

  /**
   * @var array $headers
   * An array of string representations of the response's custom headers.
   */
  private $headers;

  /** 
   * Set the default response's status, content type, payload data and custom headers. Returns an instance of Response (constructor)
   * 
   * @return Response 
   */
  public function __construct()
  {
    $this->status = 200;
    $this->contentType = 'text/plain';
    $this->data = null;
    $this->headers = [];
  }

  /** 
   * Add a string representation of a header to the Response's headers collection. Each header stored on this collection, will be added to 
   * the response, later.
   * 
   * @param string $header
   * @return Response 
   */
  public function setHeader(string $header)
  {
    $this->headers[] = $header;
    return $this;
  }

  /** 
   * Set the status code of the response.
   * 
   * @param integer $code
   * @return Response 
   */
  public function withStatus(int $code)
  {
    $this->status = $code;
    return $this;
  }

  /** 
   * Set the response's content type to "text/plain" and the response's payload data, with the data passed on $text.
   * If $escape flag is set to false, the payload data will be set unescaped(insecure).
   * 
   * @param string $text
   * @param boolean $escape = true
   * @return Response 
   */
  public function withText(string $text, bool $escape = true)
  {
    $this->contentType = 'text/plain';
    $this->data = $escape ? $this->sanitizeOutput($text) : $text;
    return $this;
  }

  /** 
   * Set the response's content type to "application/json" and the response's payload data, with the data passed on $data.
   * If $escape flag is set to false, the payload data will be set unescaped(insecure).
   * 
   * @param mixed $data
   * @param boolean $escape = true
   * @return Response 
   */
  public function withData($data, bool $escape = true)
  {
    $this->contentType = 'application/json';
    $this->data = $escape ? json_encode($this->sanitizeOutput($data)) : json_encode($data);
    return $this;
  }

  /** 
   * Set the response's content type to "text/html", encoded with utf-8 charset, and the response's payload data, with the data passed on $content.
   * 
   * @param string $content
   * @return Response 
   */
  public function withHTML(string $content)
  {
    $this->contentType = 'text/html; charset=utf-8';
    $this->data = $content;
    return $this;
  }

  /** 
   * Set the response's content type to "application/xml" and the response's payload data, with the data passed on $data.
   * 
   * @param mixed $data
   * @return Response 
   */
  public function withXMLData($data)
  {
    $this->contentType = 'application/xml';
    $this->data = Utils::XML_encode($data);
    return $this;
  }

  /** 
   * Returns the collection of response's custom headers.
   * 
   * @return array 
   */
  public function getHeaders()
  {
    return $this->headers;
  }

  /** 
   * Returns the response's status code.
   * 
   * @return integer 
   */
  public function getStatus()
  {
    return $this->status;
  }

  /** 
   * Returns the response's content type string representation.
   * 
   * @return string 
   */
  public function getContentType()
  {
    return $this->contentType;
  }

  /** 
   * Returns the response's payload data.
   * 
   * @return mixed 
   */
  public function getData()
  {
    return $this->data;
  }

  /** 
   * Sanitizes the passed data, encoding it with htmlspecialchars(), in order to avoid XSS attacks. Returns the sanitized data.
   * 
   * @param mixed $payload
   * @return mixed 
   */
  private function sanitizeOutput($payload)
  {
    if (is_array($payload) || (gettype($payload) == 'object'))
      foreach ($payload as &$value) {
        if (gettype($value) == 'array' || (gettype($value) == 'object')) {
          $value = $this->sanitizeOutput($value);
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
