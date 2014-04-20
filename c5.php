<?hh
/* Used as the base for all elements that render from a c5 collection, e.g. the area or attributes
 * Typically these will be singleton, though I'd appreciate anyone willing to change my mind on this.
 */
abstract class :c5:base extends :x:element {
  attribute
    bool cached = false,
    int cache-timeout = 60 // Let's default to caching for a minute
    ;

  category %flow;

  protected $cacheType = 'cacheable';
  protected $cacheKey = '';

  protected function compose(): :c5:base {
  }

  protected function render(): :x:composable-element {
    if($this->getAttribute('cached')) {
      $composed = Cache::get($this->cacheType, $this->cacheKey);
      if(!$composed) {
        $composed = $this->compose();
        Cache::set($this->cacheType, $this->cacheKey, $composed, $this->getAttribute('cache-timeout'));
      }
    }
    else {
      $composed = $this->compose();
    }
    return $composed;
  }
}

class :c5:raw extends :xhp:raw-pcdata-element {
  category %phrase, %flow, %metadata;

  protected function stringify() : string {
    $buf = '';
    foreach ($this->getChildren() as $child) {
      if (!is_string($child)) {
        throw new XHPClassException($this, 'Child must be a string');
      }
      $buf .= $child;
    }
    return $buf;
  }
}


class :c5:area extends :c5:base {
  attribute
    string name,
    Page page,
    enum {'global', 'local'} scope = 'local';

  protected $cacheType = 'area';

  protected function init(): null {
    $this->cacheKey = $this->getAttribute('name');
  }

  protected function compose(): :xhp {
    $page = $this->getAttribute('page');
    $name = $this->getAttribute('name');
    ob_start();
    
    if($this->getAttribute('scope') == 'global') {
      (new GlobalArea($name))->display($page);
    } else {
      (new Area($name))->display($page);
    }

    return <c5:raw>{ ob_get_clean() }</c5:raw>;
  }
}

class :c5:attribute extends :c5:base {
  attribute
    Page page,
    string handle,
    string fa-icon // FontAwesome support
    ;

  protected $cacheType = 'attribute';

  protected function init(): null {
    $this->cacheKey = $this->getAttribute('handle');
  }

  protected function compose() : :xhp {
    $page = $this->getAttribute('page');
    return <c5:raw>{(string) $page->getAttribute($this->getAttribute('handle'))}</c5:raw>;
  }
}

/* Wrappers for the form-helper.
 * I'm just grabbing the raw strings from the helpers for now, but eventually
 * the helper logic should all go inside these render() functions
 */
abstract class :c5:form-element extends :x:element {
  category %flow;

  public $fh;
  protected function init() {
    $this->fh = Loader::helper('form');
  }
}

class :c5:form-hidden extends :c5:form-element {
  attribute
    string name @required,
    string value;

  protected function render() : :xhp {
    return <c5:raw>{ $this->fh->hidden($this->getAttribute('name'),$this->getAttribute('value')) }</c5:raw>;
  }
}

class :c5:form-password extends :c5:form-element {
  attribute
    string name @required,
    string value;

  protected function render() : :xhp {
    return <c5:raw>{ $this->fh->password($this->getAttribute('name'), $this->getAttribute('value')) }</c5:raw>;
  }
}

class :c5:form-submit extends :c5:form-element {
  attribute
    string name,
    string value;

  protected function render() : :xhp {
    $value = $this->getAttribute('value') ?: 'OK';
    return <c5:raw>{ $this->fh->submit($this->getAttribute('name'), $value) }</c5:raw>;
  }
}

class :c5:form-text extends :c5:form-element {
  attribute
    string name @required,
    string value;

  protected function render() : :xhp {
    return <c5:raw>{ $this->fh->text($this->getAttribute('name'), $this->getAttribute('value')) }</c5:raw>;
  }
}

/* The Loader
 * Some loader functions are for loading into memory, but others (e.g. header_required) are for rendering HTML content
 * This element is for the latter.
 */
abstract class :c5:loader extends :x:element {
  
}

class :c5:loader-element extends :c5:loader {
  attribute
    string file,
    string args;

  protected function render() : :xhp {
    ob_start();
    Loader::element( $this->getAttribute('file'), $this->getAttribute('args') );
    return <c5:raw>{ ob_get_clean() }</c5:raw>;
  }
}

