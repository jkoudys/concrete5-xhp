<?hh
/* Used as the base for all elements that render from a c5 collection, e.g. the area or attributes
 * Typically these will be singleton, though I'd appreciate anyone willing to change my mind on this.
 */
abstract class :c5:base extends :x:element {
  attribute
    string class,
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

/* The actual <form> element
 * We're using contexts, so rather than declare a "$fh = Loader::helper('form')",
 * just start your forms with <c5:form> and the helper will be available to
 * all contained <c5:form-* elements.
 */
class :c5:form extends :c5:base {
  attribute
    :form,
    string method,
    string action;
  public $selectIndex = 1;
  protected function init() {
    $this->setContext('fh', Loader::helper('form'));
    $this->setContext('form', &$this);
  }

  protected function compose(): :xhp {
    // We build this array so that any standard html:form attribute can pass through
    $attributes = Map::fromArray($this->getAttributes());
    $class = $attributes['class'];
    $method = $attributes['method'];
    $action = $attributes['action'];
    $attributes->remove('class')->remove('method')->remove('action');

    return
      (<form class={ $class } method={ $method } action={ $action }>
        { $this->getChildren() }
      </form>)->setAttributes($attributes);
  }
}

class :c5:form-hidden extends :c5:base {
  attribute
    :input,
    string name @required,
    string value;

  protected function compose() : :xhp {
    return <c5:raw>{ $this->getContext('fh')->hidden($this->getAttribute('name'),$this->getAttribute('value')) }</c5:raw>;
  }
}

class :c5:form-password extends :c5:base {
  attribute
    :input,
    string name @required,
    string value;

  protected function render() : :xhp {
    return <c5:raw>{ $this->getContext('fh')->password($this->getAttribute('name'), $this->getAttribute('value')) }</c5:raw>;
  }
}

class :c5:form-submit extends :c5:base {
  attribute
    :input,
    string name @required,
    string value = 'OK';

  protected function render() : :xhp {
    $value = $this->getAttribute('value');
    return <c5:raw>{ $this->getContext('fh')->submit($this->getAttribute('name'), $value) }</c5:raw>;
  }
}

class :c5:form-text extends :c5:base {
  attribute
    :input,
    string name @required,
    string value;

  protected function render() : :xhp {
    return <c5:raw>{ $this->getContext('fh')->text($this->getAttribute('name'), $this->getAttribute('value')) }</c5:raw>;
  }
}

/* Until here, the form helper classes are trivial enough that they're not terribly useful, even in the helper.
 * The 'select' is a good case study in converting helpers to xhp elements.
 */
class :c5:form-select extends :c5:base {
  attribute
    :select,
    string name,
    Map options,
    string selected;

  protected function init(): null {
  }

  protected function compose(): :xhp {
    $name = $this->getAttribute('name');
    $selected = $this->getAttribute('selected');
    $options = $this->getAttribute('options');
    $class = $this->getAttribute('class');
    
    if (substr($name, -2) == '[]') {
      $form = $this->getContext('form');
      $_name = substr($name, 0, -2);
      $id = $_name . $form->selectIndex;
      $form->selectIndex++;
    } else {
      $_name = $name;
      $id = $name;
    }
    
    // Filter out the attribs we don't need in root element
    $attributes = Map::fromArray($this->getAttributes())->remove('options')->remove('selected')->remove('class');
    return 
      (<select id={ $id } class={trim('ccm-input-select ' . $class)} >
      {
        $options->mapWithKey(
          function($k, $v) use ($selected) { 
            return <option value={ $k } selected={ $selected == $k } >{ $v }</option>;
          })
      }
      </select>)->setAttributes($attributes);
  }
}

/* The Loader
 * Some loader functions are for loading into memory, but others (e.g. header_required) are for rendering HTML content
 * These elements are for the latter.
 */
abstract class :c5:loader extends :x:element {
  
}

class :c5:loader-element extends :c5:loader {
  attribute
    string file @required,
    string args;

  protected function render() : :xhp {
    ob_start();
    Loader::element( $this->getAttribute('file'), $this->getAttribute('args') );
    return <c5:raw>{ ob_get_clean() }</c5:raw>;
  }
}

