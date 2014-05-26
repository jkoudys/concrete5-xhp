<?hh

/* Used as the base for all elements that render from a c5 collection, e.g. the area or attributes
 */
Loader::library('Redis/Redis','xhp');
abstract class :c5:base extends :x:element {
  attribute
    bool cached = false,
    int cache-options = null; // Let's not set any cache options

  category %flow;

  protected $cacheType = 'cacheable';
  protected $cacheKey = '';

  protected function compose(): :xhp {
  }

  final protected function render(): :x:composable-element {
    if($this->getAttribute('cached')) {
      $composed = unserialize(\Concrete\Database\Redis::db()->get($this->cacheType . $this->cacheKey));
      if(!$composed) {
        $composed = $this->compose();
        \Concrete\Database\Redis::db()->set($this->cacheType . $this->cacheKey, serialize($composed), $this->getAttribute('cache-options'));
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

/* Use a base 'HTML' element, to which we'll setup our whole page */
class :c5:html extends :c5:base {
  attribute
    :html,
    Page page;

  protected function init() {
    if($page = $this->getAttribute('page')) {
      $this->setContext('page', $page);
    }
  }

  protected function compose(): :xhp {
    return (<html>{$this->getChildren()}</html>)->setAttributes($attributes);
  }
}

class :c5:area extends :c5:base {
  attribute
    string name,
    Page page,
    Map attributes,
    :xhp block-wrapper,
    enum {'global', 'local'} scope = 'local';

  protected $cacheType = 'area';

  protected function init(): void {
    $this->cacheKey = $this->getAttribute('name');
  }

  protected function compose(): :xhp {
    $page = $this->getAttribute('page');
    $name = $this->getAttribute('name');
    
    if($this->getAttribute('scope') == 'global') {
      $area = new GlobalArea($name);
    } else {
      $area = new Area($name);
    }

    foreach($this->getAttribute('attributes') as $attribute) {
      $area->setAttribute($attribute);
    }

    $blockWrapper = $this->getAttribute('block-wrapper');
    
    ob_start();
    $area->display($page);
    $renderedArea = <c5:raw>{ ob_get_clean() }</c5:raw>;

    if($blockWrapper) {
      return $blockWrapper->appendChild($renderedArea);
    }
    else {
      return $renderedArea;
    }
  }
}

class :c5:attribute extends :c5:base {
  attribute
    Page page,
    string handle,
    string fa-icon // FontAwesome support
    ;

  protected $cacheType = 'attribute';

  protected function init(): void {
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
    :form;
  public $selectIndex = 1;
  public $radioIndex = 1;

  protected function init() {
    $this->setContext('fh', Loader::helper('form'));
    $this->setContext('form', &$this);
  }

  protected function compose(): :xhp {
    return
      (<form>
        { $this->getChildren() }
      </form>)->setAttributes($this->getAttributes());
  }
}

/* These classes are the 'input-type' helpers. They're mostly useless, and I recommend against using them */
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

  protected function compose() : :xhp {
    return <c5:raw>{ $this->getContext('fh')->password($this->getAttribute('name'), $this->getAttribute('value')) }</c5:raw>;
  }
}

class :c5:form-submit extends :c5:base {
  attribute
    :input,
    string name @required,
    string value = 'OK';

  protected function compose() : :xhp {
    $value = $this->getAttribute('value');
    return <c5:raw>{ $this->getContext('fh')->submit($this->getAttribute('name'), $value) }</c5:raw>;
  }
}

class :c5:form-text extends :c5:base {
  attribute
    :input,
    string name @required,
    string value;

  protected function compose(): :xhp {
    return <c5:raw>{ $this->getContext('fh')->text($this->getAttribute('name'), $this->getAttribute('value')) }</c5:raw>;
  }
}

/* Until here, the form helper classes are trivial enough that they're not terribly useful, even in the helper.
 * The 'select' is a good case study in converting helpers to xhp elements.
 */
class :c5:form-select extends :c5:base {
  attribute
    :select,
    string name @required,
    Map options,
    string selected;

  protected function init(): void {
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
    $attributes = Map::fromArray($this->getAttributes())->remove('options')->remove('selected')->remove('class')->remove('id');
    return 
      (<select id={ $id } class={trim('ccm-input-select ' . $class)} >
      {
        $options->mapWithKey(
          function($k, $v) use ($selected, $name) { 
            $isSelected =
              $selected == $k &&
              !isset($_REQUEST[$_key]) ||
                ($name !== false && $name == $k) ||
                (is_array($_REQUEST[$_name]) && (in_array($k, $_REQUEST[$_name])));
            return <option value={ $k } selected={ $isSelected } >{ $v }</option>;
          })
      }
      </select>)->setAttributes($attributes);
  }
}

class :c5:form-select-multiple extends :c5:base {
  attribute
    :c5:form-select;
  protected function compose(): :xhp {
    $name = $this->getAttribute('name');
    $id = $name . '[]';

    $selected = $this->getAttribute('selected');

    $inputIgnore = <input type="hidden" class="ignore" name={$name} value="" />;
    $select = <select id={$id} multiple="multiple" />;
    foreach ($this->getAttribute('options') as $val => $text) {
      $isSelected = in_array($val, Vector { $this->getAttribute('selected')} );
      $select->appendChild( <option value={$val} selected={$isSelected}>{$text}</option> );
    }
    $attributes = Map::fromArray($this->getAttributes())->remove('options')->remove('selected')->remove('class')->remove('id');
    $select->setAttributes($attributes);

    return <x:frag>{Vector { $inputIgnore, $select }}</x:frag>;
  }
}

/*
 * Generates a radio button
 */
class :c5:form-radio extends :c5:base {
  attribute
    :input,
    string name @required,
    string option,
    string selected;

  protected function compose(): :xhp {
    $form = $this->getContext('form');
    $value = $this->getOption('option');

    $checked = ($name == $value && !isset($_REQUEST[$name]) || (isset($_REQUEST[$name]) && $_REQUEST[$name] == $value));

    $input = <input type="radio" id={ $this->getAttribute('name') . $form->radioIndex } class={ trim($this->getAttribute('class') . ' ccm-input-radio') }  />;
    $attributes = Map::fromArray($this->getAttributes())->remove('class')->remove('id')->remove('selected')->remove('option');
    $input->setAttributes($attributes);

    $form->radioIndex++;

    return $input;
  }
}

/*
 * Avatar helper
 */
class :c5:avatar extends :c5:base {
  attribute :img,
    Object user @required,
    float aspect-ratio = 1.0;

  protected function compose(): :xhp {
    $uo = $this->getAttribute('user');
    $attributes = Map::fromArray($this->getAttributes())->remove('user')->remove('aspectRatio');
    if ($uo->hasAvatar()) {
      if (file_exists(DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.jpg')) {
        $size = DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.jpg';
        $src = REL_DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.jpg';
      } 
      if (file_exists($size)) {
        $isize = getimagesize($size);
        $isize[0] = round($isize[0]*$aspectRatio);
        $isize[1] = round($isize[1]*$aspectRatio);

        return (<img class="u-avatar" src={$src} width={$isize[0]} height={$isize[1]} alt={$uo->getUserName()} />)->setAttributes($attributes);
      }
    }
		return (<img class="u-avatar" src={AVATAR_NONE} width={AVATAR_WIDTH*$this->getAttribute('aspect-ratio')} height={AVATAR_HEIGHT*$this->getAttribute('aspect-ratio')} alt="" />)->setAttributes($attributes);
  }
}

/* The Loader
 * Some loader functions are for loading into memory, but others (e.g. header_required) are for rendering HTML content
 * These elements are for the latter.
 */
class :c5:loader-element extends :c5:base {
  attribute
    string file @required,
    mixed args;

  protected function compose() : :xhp {
    ob_start();
    Loader::element( $this->getAttribute('file'), $this->getAttribute('args') );
    return <c5:raw>{ ob_get_clean() }</c5:raw>;
  }
}

/* <a> -- generally used to replace $this->url() calls in the controller/view, or the NavigationHelper */
class :c5:a extends :c5:base {
  attribute
    :a,
    Page page,
    Map parameters,
    string task;
  category %flow, %phrase, %interactive;
  // Should not contain %interactive
  children (pcdata | %flow)*;

  protected function compose(): :xhp {
    $attributes = Map::fromArray($this->getAttributes())->remove('href')->remove('page')->remove('task')->remove('parameters');

    if($href = $this->getAttribute('href')) {
      $link = View::url($href, $this->getAttribute('task'));
    }
    else if($page = $this->getAttribute('page')) {
      $link = Loader::helper('navigation')->getLinkToCollection($page);
      $parameters = $this->getAttribute('parameters');
      if($parameters) {
        $link .= '?' . join('&', $parameters->mapWithKey(function($k, $v) { return "$k=$v"; }));
      }
    }
    return (<a href={$link}>{$this->getChildren()}</a>)->setAttributes($attributes);
  }
}

