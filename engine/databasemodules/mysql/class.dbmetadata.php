<?php

class Dbmetadata
{

  private static $collection;
  private static $system;

  private function __construct()
  {
  }

  public static function initCache()
  {
    self::$system = &$system;
    $p = INCLUDE_PATH . '/application/cache/';

    try {
      if (!file_exists($p)) {
        mkdir($p, 0755, true);
        touch($p);
        chmod($p, 0755);
      }
      if (!file_exists($p . 'database-metadata.cache')) {
        file_put_contents($p . 'database-metadata.cache', '');
      }
    } catch (Exception $ex) {
      System::log('sys_error', $ex->getMessage());
    }
  }

  public static function tbInfo($tablename, $updCache = false)
  {
    if (empty(self::$collection)) {
      self::$collection = self::readCache();
    }

    if (!isset(self::$collection[$tablename]) || $updCache) {
      $cnn = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.dblink.php", 'dblink');
      $sql = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.sql.php", 'sql');
      $res_f = $cnn->getConnection('reader')->runsql($sql->write("DESCRIBE `" . $tablename . "`", array(), $tablename)->output());

      $fields = array();
      $key = false;
      foreach ($res_f as $row) {
        $fields[] = $row;

        if ($row->Key === "PRI") {
          $key = (object) array(
            'keyname' => $row->Field,
            'keyalias' => $tablename . "_" . $row->Field
          );
        }
      }

      $res_r = $cnn->getConnection('reader')->runsql($sql->write("SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '" . DBNAME . "' AND REFERENCED_TABLE_NAME = '" . $tablename . "';", array(), $tablename)->output());

      foreach ($res_r as $k => $v) {
        $res_r[$v->TABLE_NAME] = $v;
        unset($res_r[$k]);
      }

      self::$collection[$tablename] = array(
        'table' => $tablename,
        'fields' => $fields,
        'references' => $res_r,
        'key' => $key
      );

      $res_r = $cnn->getConnection('reader')->runsql($sql->write("SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '" . DBNAME . "' AND TABLE_NAME = '" . $tablename . "';", array(), $tablename)->output());

      foreach ($res_r as $k => $v) {
        $res_r[$v->REFERENCED_TABLE_NAME] = $v;
        unset($res_r[$k]);
      }

      self::$collection[$tablename]['relatedTo'] = $res_r;

      self::updCache();
    }

    return (object) self::$collection[$tablename];
  }

  public static function alterTable($tablename, $cmd)
  {
    $cnn = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.dblink.php", 'dblink');
    $sql = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.sql.php", 'sql');

    return $cnn->getConnection('writer')->runsql($sql->write("ALTER TABLE `" . $tablename . "` " . $cmd, array(), $tablename)->output());
  }

  public static function listTables()
  {
    $cnn = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.dblink.php", 'dblink');
    $sql = System::loadClass(INCLUDE_PATH . "/engine/databasemodules/" . DBTYPE . "/class.sql.php", 'sql');
    $res = $cnn->getConnection('reader')->runsql($sql->write("SHOW TABLES")->output());

    $ret = array();
    $keyname = "Tables_in_" . DBNAME;
    foreach ($res as $t) {
      $ret[] = $t->$keyname;
    }

    return $ret;
  }

  private static function readCache()
  {
    try {
      return (array) unserialize(file_get_contents(INCLUDE_PATH . '/application/cache/database-metadata.cache'));
    } catch (Exception $ex) {
      System::log('sys_error', $ex->getMessage());
    }
  }

  private static function updCache()
  {
    $p = INCLUDE_PATH . '/application/cache/database-metadata.cache';

    try {
      return file_put_contents($p, serialize(array_merge(self::readCache(), self::$collection)));
    } catch (Exception $ex) {
      System::log('sys_error', $ex->getMessage());
    }
  }

  public static function clearCache()
  {
    try {
      unlink(INCLUDE_PATH . '/application/cache/database-metadata.cache');
    } catch (Exception $ex) {
      System::log('sys_error', $ex->getMessage());
    }

    self::initCache();
  }
}

Dbmetadata::initCache();
