<?php
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
    if (!empty($payload))
      foreach ($payload as &$value) {
        if (gettype($value) == 'array' || (gettype($value) == 'object')) {
          $value = $this->escapeOutput($value);
          continue;
        }

        if (!is_numeric($value))
          $value = htmlspecialchars($value);
      }

    return $payload;
  }
}
