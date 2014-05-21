<?hh
namespace Concrete\Database;

final class Redis {

  private static Redis $instance = null;
  private static \Redis $redisDB;

  /**
   * Private singleton constructor
   */
  private function __construct(): void{
    self::$redisDB = new \Redis();
    self::$redisDB->connect(REDIS_CONNECTION_HANDLE);
  }

  /**
   */
  public static function db(): \Redis{
    if( self::$instance === null ){
      self::$instance = new self;
    }
    return self::$redisDB;
  }

  /* 
   * delete
   */
  public function delete(Page $page): void {
//    self::redisDB->del
  }
}
