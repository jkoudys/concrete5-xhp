<?hh
defined('C5_EXECUTE') || die('Access Denied.');

class Page extends Concrete5_Model_Page {
  private :xhp $xhpHead;
  private :xhp $xhpBody;
  private :xhp $xhpFoot;

  public function appendXhpHead(mixed $child): void {
    $this->xhpHead->appendChild($child);
  }
  public function appendXhpBody(mixed $child): void {
    $this->xhpBody->appendChild($child);
  }

  public function initXhp(): void {
    $cp = new Permissions($this);
    $this->xhpHead = <head />;
    $this->xhpBody = <body class={'preload ' . ($cp->canWrite() ? 'c5-edit-bar ' : '') . ' ' . ($this->isEditMode() ? 'c5-edit-mode' : '')} />;
  }
  public function getXhp(): :xhp {
    return
      <x:doctype>
        <html>
          {$this->xhpHead}
          {$this->xhpBody}
        </html>
      </x:doctype>;
  }
}
