<?hh 
defined('C5_EXECUTE') || die(_('Access Denied.'));

/**
 * Concrete5 Package "XHP Support"
 * @author jkoudys "Joshua Koudys" http://qaribou.com
 */
class XhpPackage extends Package {

	protected static string $pkgHandle = 'xhp';
	protected string $appVersionRequired = '5.6.3';
	protected string $pkgVersion = '1.0';

	public function getPackageDescription(): string {
		return t('XHP classes for loading concrete5 data, attributes, areas, and more.');
	}

	public function getPackageName(): string {
		return t('XHP Support');
	}
	
	public function getPackageHandle(): string {
		return self::$pkgHandle;
	}

	public function install(): void {
		$pkg = parent::install();
  }

  public function on_start(): void {
    Loader::library('/xhp/init', 'xhp');
    Loader::library('/xhp/init');
    Loader::model('/page', 'xhp');
  }
}
