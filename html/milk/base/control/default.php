<?php

    class Text_MilkControl extends MilkControl {
        public $signals = array('click');
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
        public $signals = array('click', 'over', 'out');
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
        public $signals = array('click');
    }

    class VerticalBox_MilkControl extends MilkControl {
        public $flex = 1;
        public $fitHeight = FALSE;
    }

    /* Synonyms for the VerticalBox control */
    class VertBox_MilkControl extends VerticalBox_MilkControl { }
    class VBox_MilkControl extends VerticalBox_MilkControl { }
    class VertContainer_MilkControl extends VerticalBox_MilkControl { }
    class VertCont_MilkControl extends VerticalBox_MilkControl { }
    class VCont_MilkControl extends VerticalBox_MilkControl { }
    /* End Synonyms for the VerticalBox control */

    class HorizontalBox_MilkControl extends MilkControl {
        public $flex = 1;
    }

    /* Synonyms for the HorizontalBox control */
    class HorizBox_MilkControl extends HorizontalBox_MilkControl { }
    class HBox_MilkControl extends HorizontalBox_MilkControl { }
    class HorizContainer_MilkControl extends HorizontalBox_MilkControl { }
    class HorizCont_MilkControl extends HorizontalBox_MilkControl { }
    class HCont_MilkControl extends HorizontalBox_MilkControl { }

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

    class DataGrid_MilkControl extends MilkControl {
        public $signals     = array('hover', 'focus', 'select');
        public $slots       = array('first', 'prev', 'next', 'last', 'csv');
        public $strictConns = FALSE;
        public $data        = array();
        public $numcols     = 0;
        public $perpage     = NULL;
        public $totalrows   = NULL;
        public $offset      = 0;
        public $sortCol     = 0;
        public $sortDesc    = FALSE;
        protected $props    = array();
        protected $cols     = array();

        public function __construct($parent, $name=NULL) {
            parent::__construct($parent, $name);
            if (isset($this->request['offset'])) $this->offset = $this->request['offset'];
            if (isset($this->request['sortCol'])) $this->sortCol = $this->request['sortCol'];
            if (isset($this->request['sortDesc'])) $this->sortDesc = $this->request['sortDesc'];
        }

        public function setProp($col, $key, $val) {
            if (!isset($this->props[$col])) $this->props[$col] = array();
            $this->props[$col][$key] = $val;
        }

        public function getProp($col, $key) {
            if (isset($this->props[$col][$key])) {
                return $this->props[$col][$key];
            }

            return NULL;
        }

        public function setHeaders($header) {
            if (is_array($header)) {
                $args = $header;
            } else {
                $args = func_get_args();
            }
            $c = count($args);
            for ($i=0; $i < $c; $i++) {
                if (is_string($args[$i])) {
                    $this->setProp($i, 'header', $args[$i]);
                } else {
                    trigger_error('DataGrid::setHeaders() - ' . $args[$i] . ' is not a valid header', E_USER_ERROR);
                }
            }
            $this->numcols = max($this->numcols, count($args));
        }

        public function setCols($col) {
            if (is_array($col)) {
                $args = $col;
            } else {
                $args = func_get_args();
            }
            foreach ($args as $col) {
                if (!is_string($col)) {
                    trigger_error('DataGrid::setCols() - ' . $col . ' is not a valid column name', E_USER_ERROR);
                }
            }
            $this->cols = $args;
            $this->numcols = count($this->cols);
        }

        public function sort($col, $desc=FALSE) {
            if (is_integer($col) && isset($this->cols[$col])) {
                $this->sortCol = $col;
            } else if (is_scalar($col) && ($i = array_search($col, $this->cols)) !== FALSE) {
                $this->sortCol = $i;
            }
            $this->sortDesc = ($desc ? TRUE : FALSE);
        }

        public function add($row, $actargs=NULL) {
            $tmp = array();
            $isarr = is_array($row);
            if (!empty($this->cols)) {
                foreach ($this->cols as $col) {
                    $tmp[] = ($isarr ? $row[$col] : $row->{$col});
                }
            } else {
                // TODO: Figure out how best to add data when cols not set
            }

            if (!empty($tmp)) {
                $this->data[] = array($tmp, $actargs);
                $this->numcols = max($this->numcols, count($tmp));
            }
        }

        public function &normaliseRow($db, $sql, &$row) {
            $keys = array_keys(get_object_vars($row));
            foreach ($keys as $idx => $key) {
                if (in_array($key, $this->cols) && ($meta = $db->getColumnMeta($sql->toString(), $idx)) && isset($meta['native_type'])) {
                    switch ($meta['native_type']) {
                        case 'DATE':
                            if ($row->{$meta['name']} !== NULL && !is_object($row->{$meta['name']})) {
                                $tmp = $row->{$meta['name']};
                                $row->{$meta['name']} = new MilkDate();
                                $row->{$meta['name']}->fromDBString($tmp);
                            }
                            break;

                        case 'TIMESTAMP':
                        case 'DATETIME':
                            if ($row->{$meta['name']} !== NULL && !is_object($row->{$meta['name']})) {
                                $tmp = $row->{$meta['name']};
                                $row->{$meta['name']} = new MilkDateTime();
                                $row->{$meta['name']}->fromDBString($tmp);
                            }
                            break;

                        case 'TIME':
                            if ($row->{$meta['name']} !== NULL && !is_object($row->{$meta['name']})) {
                                $tmp = $row->{$meta['name']};
                                $row->{$meta['name']} = new MilkTime();
                                $row->{$meta['name']}->fromDBString($tmp);
                            }
                            break;
                            
                        case 'NEWDECIMAL': // TODO: Should there be any other types here??
                            $dd = $this->module->getDD();
                            if ($dd->fieldExists($meta['name']) && ($attr = $dd->getAttrib($meta['name'], DD_ATTR_PREFIX))) {
                                $row->{$meta['name']} = $attr . $row->{$meta['name']};
                            }
                            break;
                    }
                }
            }

            return $row;
        }

        public function getNext($db, $sql) {
            if ($this->perpage > 0) {
                if ($this->totalrows == NULL && !isset($this->request['csv'])) {
                    $sql->option('SQL_CALC_FOUND_ROWS');
                    $sql->limit($this->perpage, $this->offset);
                }
            }
            if ($this->totalrows == NULL && isset($this->cols[$this->sortCol])) {
                $sql->orderby($this->cols[$this->sortCol], ($this->sortDesc ? 'DESC' : 'ASC'));
            }
            if ($row = $db->getnext($sql->toString())) {
                if ($this->totalrows == NULL) {
                    $this->totalrows = $db->foundrows();
                }

                return $this->normaliseRow($db, $sql, $row);
            }

            return NULL;
        }

        public function addNav($h) {
            if ($this->perpage > 0) {
                $pages = ($this->totalrows > 0 ? ceil($this->totalrows/$this->perpage) : 1);
                $page  = floor($this->offset/$this->perpage)+1;

                $f = $h->add('Button', '<<');
                $p = $h->add('Button', '<');
                if ($this->offset > 0) {
                    $f->connect('click', $this, 'first');
                    $p->connect('click', $this, 'prev');
                } else {
                    $f->disabled = TRUE;
                    $p->disabled = TRUE;
                }

                $h->add('Text', sprintf('%d of %d', $page, $pages))->nowrap = TRUE;

                $n = $h->add('Button', '>');
                $l = $h->add('Button', '>>');
                if ($this->offset < $this->totalrows-$this->perpage) {
                    $n->connect('click', $this, 'next');
                    $l->connect('click', $this, 'last');
                } else {
                    $n->disabled = TRUE;
                    $l->disabled = TRUE;
                }
            } else {
                // TODO: throw an error here
            }
        }

        public function csv() {
            $csv = $this->module->newControl('CSV');
            $headers = array();
            for ($i=0; $i < $this->numcols; $i++) {
                if ($header = $this->getProp($i, 'header')) {
                    $headers[] = $header;
                }
            }
            if (!empty($headers)) $csv->addHeaders($headers);
            foreach ($this->data as $row) {
                list($data,) = $row;
                $csv->add($data);
            }
            $csv->toFile();
        }

        public function deliver($theme) {
            if (isset($this->request['csv'])) {
                $this->csv();
            } else {
                parent::deliver($theme);
            }
        }
        // TODO: Add deliver to check if nav is added when perpage is set
    }

    /* Form controls */
    class Button_MilkControl extends MilkControl {
        public $signals = array('click');
        public $slots = array('disable', 'slotdone');
        public $value;
        public $src;
        public $disabled = FALSE;

        public function __construct($parent, $value, $src=NULL) {
            parent::__construct($parent);
            $this->value = $value;
            $this->src   = $src;
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

    class FileBox_MilkControl extends Form_MilkControl { }
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

    class CSV_MilkControl extends MilkControl {
        public $data = array();
        public $delimeter = ',';
        public $headers = array();

        public function addHeaders($headers) {
            if (is_array($headers) || is_object($headers)) {
                foreach ($headers as $val) {
                    $this->headers[] = $val;
                }
            }
        }

        public function add($row) {
            if (is_array($row) || is_object($row)) {
                $this->data[] = $row;
            }
        }

        public function addAll($data) {
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
        public function encode(&$data, $delim=NULL) {
            if (!$delim) $delim = $this->delimeter;

            $rowbuffer = '';
            foreach ($data as $field) {
                if (is_object($field) && method_exists($field, 'toString')) $field = $field->toString();
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

        public function toFile($file=NULL) {
            header('Pragma: ');
            header('Content-type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . ($file ? str_replace('"', '\"', $filename) : 'data.csv') . '"');

            print $this->toString();
            exit;
        }

        public function toString() {
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
