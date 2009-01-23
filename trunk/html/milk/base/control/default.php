<?php

    class Text extends MilkControl {
        public $signals = array('click');
        public $value;

        public function __construct($parent, $value) {
            parent::__construct($parent);
            $this->value = $value;
        }
    }

    // The only difference between Label, HTML and
    // Text should be in the rendering
    class Label extends Text { }

    class HTML extends Text {
        public $signals = array();
    }

    class Image extends MilkControl {
        public $signals = array('click');
        public $src;
        public $width;
        public $height;
        public $alt;
        public $top;
        public $left;

        public function __construct($parent, $src, $width, $height, $alt='', $top=NULL, $left=NULL) {
            parent::__construct($parent);
            $this->src    = $src;
            $this->width  = $width;
            $this->height = $height;
            $this->alt    = $alt;
            $this->top    = $top;
            $this->left   = $left;
        }
    }

    class Terminator extends MilkControl {
        public $url    = NULL;
        public $reload = TRUE;
        public $close  = TRUE;
    }

    class VerticalBox extends MilkControl { }

    /* Synonyms for the VerticalBox control */
    class VertBox extends VerticalBox { }
    class VBox extends VerticalBox { }
    class VertContainer extends VerticalBox { }
    class VertCont extends VerticalBox { }
    class VCont extends VerticalBox { }
    /* End Synonyms for the VerticalBox control */

    class HorizontalBox extends MilkControl { }

    /* Synonyms for the HorizontalBox control */
    class HorizBox extends HorizontalBox { }
    class HBox extends HorizontalBox { }
    class HorizContainer extends HorizontalBox { }
    class HorizCont extends HorizontalBox { }
    class HCont extends HorizontalBox { }

    class Template extends MilkControl {
        public $title;
        public $file;

        public function __construct($parent, $title=NULL, $file=NULL) {
            parent::__construct($parent);
            $this->title = $title;
            $this->file  = MilkTools::ifNull($file, MilkLauncher::mkPath(MILK_APP_DIR, 'template', 'default.php'));
        }
    }

    class Table extends MilkControl {
        public $row = 0;

        public function __construct($parent) {
            parent::__construct($parent);
            $this->controls[$this->row] = array();
        }

        public function add($ctrl) {
            $args = func_get_args();
            $cb = array('MilkControl', 'create');
            if ($control = call_user_func_array($cb, $args)) {
                $this->controls[$this->row][] = $control;
                $this->prev = $control;
                return $control;
            }
        }

        public function newRow() {
            if (!isset($this->controls[$this->row])) $this->controls[$this->row] = array();
            $this->row++;
        }

        public function addPair($label, $ctrl) {
            $this->add('Label', $label);
            $args = func_get_args();
            array_unshift($args);
            $cb = array($this, 'add');
            return call_user_func_array($cb, $args);
        }
    }

    class Tabs extends MilkControl {
        public $signals = array('showtab');
        public $tabs = array();

        public function add($parent, $label, $ctrl) {
            $this->tabs[] = $label;
            $args = func_get_args();
            array_unshift($args);
            $cb = array(parent, 'add');
            return call_user_func_array($cb, $args);
        }
    }

    class ListView extends MilkControl { }

    /* Form controls */
    class Button extends MilkControl {
        public $signals = array('click');
        public $value;
        public $src;

        public function __construct($parent, $value, $src=NULL) {
            parent::__construct($parent);
            $this->value = $value;
            $this->src   = $src;
        }
    }

    abstract class Form extends MilkControl {
        public $slots = array('setvalue');
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

        protected function getAttribs($field, $attribs) {
            if (is_array($attribs)) {
                return $attribs;
            } else if ($attribs instanceof DataStructure) {
                $ds =& $attribs;
            } else if (is_scalar($attribs)) {
                $ds = $this->module->getDS($attribs);
            } else if ($this->module->hasDS()) {
                $ds = $this->module->getDS();
            }

            if (isset($ds) && $ds instanceof DataStructure && $ds->fieldExists($field)) {
                return $ds->fields[$field];
            }

            return array();
        }

        public function getAttrib($key, $default=NULL) {
            if (isset($this->attribs[$key])) {
                return $this->attribs[$key];
            }

            return $default;
        }

        public function setAttrib($key, $val) {
            $this->attribs[$key] = $val;
        }
    }

    class TextBox extends Form {
        public $signals = array('enter');
        public $minlen;
        public $maxlen;

        public function __construct($parent, $name, $value=NULL, $attrs=NULL) {
            parent::__construct($parent, $name, $value, $attrs);
            $this->minlen = $this->getAttrib('min');
            $this->maxlen = $this->getAttrib('max');
        }
    }

    class PasswordBox extends Form {
        public $signals = array('enter');
    }

    class ListBox extends Form {
        public $signals = array('change');
        public $options = array();
        public $minsel;
        public $maxsel;

        public function __construct($parent, $name, $value, $attribs=NULL) {
            parent::__construct($parent, $name, $value, $attribs);
            $this->options = $this->getAttrib('options');
            $this->minsel = $this->getAttrib('min');
            $this->maxsel = $this->getAttrib('max');

            if (!is_array($this->options)) {
                trigger_error('ListBox::__construct() - An options array must be specified in a data structure or attributes argument', E_USER_ERROR);
            }
        }
    }

    class Boolean extends Form {
        public $signals = array('change');
    }

    class ChoosBox extends Form {
        public $signals = array('change', 'choose');
        public $slots = array('setvalue', 'clear');
    }

    class DateBox extends Form {
        public $signals = array('enter');
    }

    class DateTimeBox extends DateBox {
        public $signals = array('enter');
    }

    class FileBox extends Form { }
    /* End form controls */

    class XML extends MilkControl {
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

    class CSV extends MilkControl {
        public $data = array();
        public $delimeter = ',';
        public $headers = array();

        function addHeaders($headers) {
            if (is_array($headers) || is_object($headers)) {
                foreach ($headers as $val) {
                    $this->headers[] = $val;
                }
            }
        }

        function addRow($row) {
            if (is_array($row) || is_object($row)) {
                $this->data[] = $row;
            }
        }

        function addAll($data) {
            if (is_array($data)) {
                $this->data = $data;
                return TRUE;
            }

            return FALSE;
        }

        /**
         * encode() takes an array and encodes it for use as a row in a CSV
         *
         * @return string the data (row) encoded for a CSV
         * @param array &$data the data to CSV encode
         * @param string $delim optional param, the column delimiter. Deaults to comma (,)
         */
        function encode(&$data, $delim=NULL) {
            if (!$delim) $delim = $this->delimeter;

            $rowbuffer = '';
            foreach ($data as $field) {
                if (is_object($field) || is_array($field)) $field = '';
                if ($rowbuffer != '') $rowbuffer .= $delim;
                if ($field == '' || strchr($field, $delim) || strchr($field, '"')) {
                    $field = '"' . str_replace('"', '""', $field) . '"';
                }
                $rowbuffer .= $field;
            }

            return $rowbuffer . "\r\n";
            // Note: UTF-16LE encoding is required for asian character set support in excel.
            // However, a BOM (Byte-Order-Mark must also be used at the beginning of the file for this to work
            // return mb_convert_encoding($rowbuffer . "\r\n", 'UTF-16LE');
        }

        function toFile($file=NULL) {
            header('Pragma: ');
            header('Content-type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . ($file ? str_replace('"', '\"', $filename) : 'data.csv') . '"');

            print $this->toString();
            exit;
        }

        function toString() {
            $csv = '';
            if (!empty($this->headers)) {
                $csv.= $this->encode($this->headers, $this->delimeter);
            }
            if (is_array($this->data)) {
                foreach ($this->data as $key => $val) {
                    if (is_array($val) || is_object($val)) $csv.= $this->encode($val, $this->delimeter);
                }
            }

            return $csv;
        }
    }
