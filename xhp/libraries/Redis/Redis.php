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
    self::$redisDB->connect(REDIS_CONNECTION_HANDLE);
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
