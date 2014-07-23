# concrete5-xhp

Includes the XHP element definitions for concrete5. For use with facebook's excellent xhp library, you can use this to easily write clean view-logic by puttng c5 elements right in your HTML.

e.g. this:
```
<div id="header-area">
  <?php
    $a = new Area('Header Nav');
    $a->display($c);
  ?>
</div>
```

becomes this:
```
<div id="header-area">
  <c5:area name="Header Nav" page={$c} />
</div>
```

## Installation

Copy the xhp/ directory into your `SITE_ROOT/packages`, then install from the c5 dashboard. You can add additional XHP extensions, by loading them inside of `SITE_ROOT/libraries/xhp/init.php`.
```
<?hh
Loader::library('xhp/my_custom_classes')
```

## Requirements

You'll need to have the xhp libs installed, which is tricky on a vanilla PHP environment. If you're reading this, then you most likely are interested in running this on an HHVM environment (as mixed hack/html is impossible, so xhp is not only a good idea in general, but necessary in hacklang.) HHVM won't run without this fix mkly and I worked on a while back (https://github.com/concrete5/concrete5/pull/1564), as a requirement for HHVM not supporting this quirky behaviour in PHP: https://github.com/facebook/hhvm/issues/1462 .

In short, this requires either:
- A PHP env with XHP support, and concrete5.5+
OR
- An HHVM env, with concrete5.6.3.1+ (or 5.6.2 and with the changes in the above pull request applied.)

In short, unless you're a developer, you probably shouldn't think about using this until it gets a bit more mature.

## TODOs

Lots, this project's just starting. All work done so far has been against 5.6.3, but with the impending release of 5.7, it's likely everything will be transferred to that. 
