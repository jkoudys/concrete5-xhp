<?hh
namespace Concrete\Database;

final class Redis {

  private static $instance = null;
  private static $redisDB;

  /**
   * Private singleton constructor
   */
  private function __construct(){
    self::$redisDB = new \Redis();
    self::$redisDB->connect('127.0.0.1');
  }

  /**
   */
  public static function db(){
    if( self::$instance === null ){
      self::$instance = new self;
    }
    return self::$redisDB;
  }

}
