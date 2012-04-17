<?php

    class Text_MilkControl extends MilkControl {
        public $signals = array('tap');
        public $value;
        public $nowrap = FALSE;
        public $color;
        public $tooltip;

        public function __construct($parent, $value) {
            parent::__construct($parent);
            $this->value = $value;
        }
    }

    class Label_MilkControl extends Text_MilkControl {
        public $wrap = FALSE;
    }

    class Heading_MilkControl extends Label_MilkControl {
        public $style = 1;
        public $wrap = FALSE;

        function __construct($parent, $value, $style=1) {
            parent::__construct($parent, $value);
            $this->style = (int)$style;
        }
    }

    class HTML_MilkControl extends Text_MilkControl {
        public $signals = array();
    }

    class Image_MilkControl extends MilkControl {
        public $signals = array('tap');
        public $slots = array('setsrc');
        public $src;
        public $width;
        public $height;
        public $alt;
        public $x;
        public $y;
        public $noborder = FALSE;

        public function __construct($parent, $src, $width, $height, $alt='', $x=NULL, $y=NULL) {
            parent::__construct($parent);
            $this->src    = $src;
            $this->width  = $width;
            $this->height = $height;
            $this->alt    = $alt;
            $this->x      = $x;
            $this->y      = $y;
        }
    }

    class Spacer_MilkControl extends MilkControl {
        public $flex = 1;
        protected $width;
        public $height;

        public function __construct($parent, $flex=1) {
            parent::__construct($parent);
            $this->flex = $flex;
        }

        public function __set($name, $value) {
            if ($name == 'width') {
                $this->width = $value;
                $this->flex = NULL;
            }
        }

        public function __get($name) {
            if ($name == 'width') return $this->width;
        }
    }

    class Terminator_MilkControl extends MilkControl {
        public $url    = NULL;
        public $reload = TRUE;
        public $close  = TRUE;
    }

    class Box_MilkControl extends MilkControl {
        public $flex = 1;
        public $cssclass;
        public $width;
        public $height;
        public $signals = array('tap');
    }

    class VBox_MilkControl extends MilkControl {
        public $flex = 1;
        public $fitHeight = FALSE;
    }

    class HBox_MilkControl extends MilkControl {
        public $flex = 1;
    }

    class HideBox_MilkControl extends MilkControl {
        public $slots = array('show', 'hide', 'toggle');
        public $signals = array('show', 'hide');
        public $show = FALSE;
    }

    class Template_MilkControl extends MilkControl {
        public $signals = array('load');
        public $title;
        public $file;
        public $css = array();

        public function __construct($parent, $title=NULL, $file=NULL) {
            parent::__construct($parent);
            $this->title = $title;
            $this->file  = MilkTools::ifNull($file, MilkTools::mkPath(MILK_APP_DIR, 'template', 'default.php'));
            $templatecss = '/css/style.css';
            if (file_exists(MilkTools::mkPath(MILK_PATH, $templatecss))) $this->addCSS($templatecss);
        }

        public function addCSS($file) {
            $this->css[] = $file;
        }

        public function setFile($name) {
            $this->file = MilkTools::mkPath(MILK_APP_DIR, 'template', $name);
        }
    }

    class Table_MilkControl extends MilkControl {
        public $row = 0;
        public $maxcols = 0;

        public function __construct($parent) {
            parent::__construct($parent);
            $this->controls[$this->row] = array();
        }

        public function add($ctrl) {
            $args = func_get_args();
            array_unshift($args, $this);
            $cb = array('MilkControl', 'create');
            if ($control = call_user_func_array($cb, $args)) {
                $this->controls[$this->row][] = $control;
                $this->prev = $control;
                return $control;
            }
        }

        public function newRow() {
            $this->maxcols = max($this->maxcols, count($this->controls[$this->row]));
            if (!isset($this->controls[$this->row])) $this->controls[$this->row] = array();
            $this->row++;
        }

        public function addPair($label, $ctrl) {
            $this->add('Label', $label);
            $args = func_get_args();
            array_shift($args);
            $cb = array($this, 'add');
            return call_user_func_array($cb, $args);
        }

        public function setSaveGroup() {
            if (!empty($this->controls)) {
                foreach ($this->controls as $key => $row) {
                    foreach ($row as $k => $ctrl) {
                        $ctrl->savegroup = $this->savegroup;
                        $ctrl->setSaveGroup();
                    }
                }
            }
        }
    }

    class Tabs_MilkControl extends MilkControl {
        public $signals  = array('showtab');
        public $slots    = array('resize');
        public $tabs     = array();
        public $tab      = 0;
        public $disabled = array();

        public function __construct($parent) {
            parent::__construct($parent);

            if (isset($this->request['tab'])) {
                $this->tab = $this->request['tab'];
            }
        }

        public function add($label, $ctrl) {
            $this->tabs[] = $label;
            $args = func_get_args();
            array_shift($args);
            $cb = array('parent', 'add');
            return call_user_func_array($cb, $args);
        }

        public function disable($tab) {
            if (isset($this->tabs[$tab])) {
                $this->disabled[$tab] = TRUE;
            }
        }
    }

    /* Form controls */
    class Button_MilkControl extends MilkControl {
        public $signals = array('tap');
        public $slots = array('disable', 'slotdone');
        public $value;
        public $disabled = FALSE;

        public function __construct($parent, $value) {
            parent::__construct($parent);
            $this->value = $value;
        }
    }

    abstract class Form_MilkControl extends MilkControl {
        public $slots = array('setvalue', 'focus');
        public $signals = array('slotdone');
        public $value;
        public $reqValue;
        public $disabled = FALSE;
        public $readonly = FALSE;
        public $attrs    = array();

        public function __construct($parent, $name, $value=NULL, $attrs=NULL) {
            parent::__construct($parent, $name);
            $this->attrs    = $this->getAttribs($name, $attrs);
            $this->disabled = $this->getAttrib('disabled', FALSE);
            $this->readonly = $this->getAttrib('readonly', FALSE);
            $this->value    = $value;
            $this->setValue();
        }

        protected function setValue() {
            if ($this->request !== NULL) {
                $this->reqValue =& $this->request;
            } else {
                $this->reqValue =& $this->value;
            }
        }

        protected function getAttribs($field, $attrs) {
            if (is_array($attrs)) {
                return $attrs;
            } else if ($attrs instanceof DataStructure) {
                $dd =& $attrs;
            } else if (is_scalar($attrs)) {
                $dd = $this->module->getDD($attrs);
            } else if ($this->module->hasDD()) {
                $dd = $this->module->getDD();
            }

            if (isset($dd) && $dd instanceof DataDef && $dd->fieldExists($field)) {
                return $dd->fields[$field];
            }

            return array();
        }

        public function getAttrib($key, $default=NULL) {
            if (array_key_exists($key, $this->attrs)) {
                return $this->attrs[$key];
            }

            return $default;
        }

        public function setAttrib($key, $val) {
            $this->attrs[$key] = $val;
        }
    }

    class TextBox_MilkControl extends Form_MilkControl {
        public $signals = array('enter', 'slotdone');
        public $minlen;
        public $maxlen;

        public function __construct($parent, $name, $value=NULL, $attrs=NULL) {
            parent::__construct($parent, $name, $value, $attrs);
            $this->minlen = $this->getAttrib('min');
            $this->maxlen = $this->getAttrib('max');
        }
    }

    class PasswordBox_MilkControl extends Form_MilkControl {
        public $signals = array('enter', 'slotdone');
    }

    class ListBox_MilkControl extends Form_MilkControl {
        public $signals = array('change', 'slotdone', 'filterset');
        public $slots   = array('setvalue', 'focus', 'filter');
        public $options = array();
        public $minsel;
        public $maxsel;
        public $filters = array();
        public $filterKey;

        public function __construct($parent, $name, $value=NULL, $attrs=NULL) {
            parent::__construct($parent, $name, $value, $attrs);
            $this->options   = $this->getAttrib('options');
            $this->minsel    = $this->getAttrib('min');
            $this->maxsel    = $this->getAttrib('max', 1);
            $this->filters   = $this->getAttrib('filters');
            $this->filterKey = $name;

            if (!is_array($this->options)) {
                trigger_error('ListBox::__construct() - An options array must be specified in a data structure or attributes argument', E_USER_ERROR);
            }
        }
    }

    class BoolBox_MilkControl extends Form_MilkControl {
        public $signals = array('change', 'slotdone', 'on', 'off');
        public $slots = array('setvalue', 'toggle', 'on', 'off');
    }

    class ChooseBox_MilkControl extends Form_MilkControl {
        public $signals = array('change', 'choose', 'slotdone');
        public $slots = array('setvalue', 'clear');
    }

    class DateBox_MilkControl extends Form_MilkControl {
        public $signals = array('enter', 'slotdone');
    }

    class DateTimeBox_MilkControl extends DateBox_MilkControl {
        public $signals = array('enter');
    }
    /* End form controls */

    class XML_MilkControl extends MilkControl {
        public $tag;
        public $props;
        public $value;
        public $stripCtrlChars = TRUE;

        public function __construct($parent, $tag, $props=NULL, $value='__XML_NOVALUE__') {
            parent::__construct($parent);
            $this->tag   = $tag;
            $this->props = $props;
            $this->value = $value;
        }

        public function add($tag, $props=NULL, $value='__XML_NOVALUE__') {
            return parent::add('XML', $tag, $props, $value);
        }

        public function entitise($str) {
            if ($str instanceof MilkDateTime) {
                $str = $str->toString(MilkTools::ifDef('CFG_DATETIME_FORMAT', NULL));
            } else if ($str instanceof MilkDate) {
                $str = $str->toString(MilkTools::ifDef('CFG_DATE_FORMAT', NULL));
            } else if (is_object($str) && method_exists($str, 'toString')) {
                $str = $str->toString();
            }

            $str = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), (string)$str);
            return $this->stripControlChars($str);
        }

        public function stripControlChars($str) {
            if ($this->stripCtrlChars) {
                return preg_replace('/([\x00-\x08\x0B-\x0C\x0E-\x0F])/e', '', $str);
            }

            return $str;
        }
    }

