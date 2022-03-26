<?php

class DatabaseException extends Exception
{
  private $sqlstate;
  private $sqlcommand;

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

  public function __toString()
  {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }

  public function getSqlState()
  {
    return $this->sqlstate;
  }

  public function getSqlCmd()
  {
    return $this->sqlcommand;
  }
}
