<?php
class Response
{
  private $status;
  private $contentType;
  private $data;

  public function __construct(int $statusCode = 200, string $contentType = 'text/plain', $data = null)
  {
    $this->status = $statusCode;
    $this->contentType = $contentType;
    $this->data = $data;
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
    foreach ($payload as &$value) {
      if (gettype($value) == 'array' || (gettype($value) == 'object' && $value instanceof StdClass)) {
        $value = $this->escapeOutput($value);
        continue;
      }

      if (!is_numeric($value))
        $value = htmlspecialchars($value);
    }

    return $payload;
  }
}
