<?hh

/* Used as the base for all elements that render from a c5 collection, e.g. the area or attributes
 * Typically these will be singleton, though I'd appreciate anyone willing to change my mind on this.
 */
abstract class :c5:collection-element extends :x:element {
  attribute
    Page page @required;

  category %flow;
}

/* The 'raw' element, aka element of last resort
 * I'm relying on this a _lot_ right now, as converting c5 output into xhp often requires simply
 * outputting raw HTML, either as returned from a c5 function, or from the output buffer.
 */
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

/* Area element
 * One of the most frequently used tags, this is how you can add an Area to a page.
 * I'm still thinking if 'scope="global"' in the attributes makes sense for using
 * global attributes, or if c5:globalarea should be a child of this. 
 */
class :c5:area extends :c5:collection-element {
  attribute
    string name,
    string scope;
 
  protected function render() : :xhp {
    $page = $this->getAttribute('page');

    ob_start();
    if($this->getAttribute('scope') == 'global') {
      (new GlobalArea($this->getAttribute('name')))->display($page);
    } else {
      (new Area($this->getAttribute('name')))->display($page);
    }
    $buf = ob_get_clean();

    return <div><c5:raw>{$buf}</c5:raw></div>;
  }
}

/* Attribute element
 * Simple, but useful. Casts each attribute as string, triggering each attribute's __toString()
 */
class :c5:attribute extends :c5:collection-element {
  attribute
    string handle;

  protected function render() : :xhp {
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

/* FormHelper::hidden() */
class :c5:form-hidden extends :c5:form-element {
  attribute
    string name @required,
    string value;

  protected function render() : :xhp {
    return <c5:raw>{ $this->fh->hidden($this->getAttribute('name'),$this->getAttribute('value')) }</c5:raw>;
  }
}

/* FormHelper::password() */
class :c5:form-password extends :c5:form-element {
  attribute
    string name @required,
    string value;

  protected function render() : :xhp {
    return <c5:raw>{ $this->fh->password($this->getAttribute('name'), $this->getAttribute('value')) }</c5:raw>;
  }
}

/* FormHelper::submit() */
class :c5:form-submit extends :c5:form-element {
  attribute
    string name,
    string value;

  protected function render() : :xhp {
    $value = $this->getAttribute('value') ?: 'OK';
    return <c5:raw>{ $this->fh->submit($this->getAttribute('name'), $value) }</c5:raw>;
  }
}

/* FormHelper::text() */
class :c5:form-text extends :c5:form-element {
  attribute
    string name @required,
    string value;

  protected function render() : :xhp {
    return <c5:raw>{ $this->fh->text($this->getAttribute('name'), $this->getAttribute('value')) }</c5:raw>;
  }
}

/* The Loader
 */
abstract class :c5:loader extends :x:element {
  
}

/* Loader::element()
 * Used to replace rendering elements, e.g. Loader::('header_required') calls
 */
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

