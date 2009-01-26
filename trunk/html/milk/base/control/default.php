<?php

    class Text_MilkControl extends MilkControl {
        public $signals = array('click');
        public $value;

        public function __construct($parent, $value) {
            parent::__construct($parent);
            $this->value = $value;
        }
    }

    // The only difference between Label, HTML and
    // Text should be in the rendering
    class Label_MilkControl extends Text_MilkControl { }

    class HTML_MilkControl extends Text_MilkControl {
        public $signals = array();
    }

    class Image_MilkControl extends MilkControl {
        public $signals = array('click');
        public $src;
        public $width;
        public $height;
        public $alt;
        public $x;
        public $y;

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

    class Terminator_MilkControl extends MilkControl {
        public $url    = NULL;
        public $reload = TRUE;
        public $close  = TRUE;
    }

    class VerticalBox_MilkControl extends MilkControl { }

    /* Synonyms for the VerticalBox control */
    class VertBox_MilkControl extends VerticalBox_MilkControl { }
    class VBox_MilkControl extends VerticalBox_MilkControl { }
    class VertContainer_MilkControl extends VerticalBox_MilkControl { }
    class VertCont_MilkControl extends VerticalBox_MilkControl { }
    class VCont_MilkControl extends VerticalBox_MilkControl { }
    /* End Synonyms for the VerticalBox control */

    class HorizontalBox_MilkControl extends MilkControl { }

    /* Synonyms for the HorizontalBox control */
    class HorizBox_MilkControl extends HorizontalBox_MilkControl { }
    class HBox_MilkControl extends HorizontalBox_MilkControl { }
    class HorizContainer_MilkControl extends HorizontalBox_MilkControl { }
    class HorizCont_MilkControl extends HorizontalBox_MilkControl { }
    class HCont_MilkControl extends HorizontalBox_MilkControl { }

    class Template_MilkControl extends MilkControl {
        public $title;
        public $file;

        public function __construct($parent, $title=NULL, $file=NULL) {
            parent::__construct($parent);
            $this->title = $title;
            $this->file  = MilkTools::ifNull($file, MilkLauncher::mkPath(MILK_APP_DIR, 'template', 'default.php'));
        }
    }

    class Table_MilkControl extends MilkControl {
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

    class Tabs_MilkControl extends MilkControl {
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

    class ListView_MilkControl extends MilkControl {
        public $signals = array('click', 'dblclick');
        public $slots = array('first', 'previous', 'next', 'last', 'exportcsv', 'selectall', 'deselectall');
        public $dynamicsignals = TRUE;
        public $dynamicslots = TRUE;
        public $rowsperpage;
        public $rows;
        public $numcols;
        public $sortCol;
        public $sortRev;
        public $offset;
        public $totalrows;
        public $pkmap;

        protected $sql;
        protected $rowProps  = array();
        protected $colProps  = array();
        protected $cellProps = array();
        protected $colSrcs   = array();
        protected $navAdded  = FALSE;

        public function __construct($parent) {
            parent::__construct($parent);

            if (isset($this->request['offset']) && is_numeric($this->request['offset'])) {
                $this->offset = $this->request['offset'];
            } else {
                $this->offset = 0;
            }
            if (isset($this->request['sort'])) {
                $this->sortCol = $this->request['sort'];
            } else {
                $this->sortCol = 0;
            }
            if (isset($this->request['rev'])) {
                $this->sortRev = $this->request['rev'];
            } else {
                $this->sortRev = FALSE;
            }
        }

        public function sort($col, $reverse=FALSE) {
            if (!isset($this->request['sort'])) {
                if (is_numeric($col)) {
                    $this->sortCol = $col;
                } else if (is_string($col) && ($c = array_search($col, $this->colSrcs))) {
                    $this->sortCol = $c;
                }
            }
            if (!isset($this->request['rev'])) $this->sortRev = ($reverse ? TRUE : FALSE);
        }

        public function setHeaders($header) {
            $args = (is_array($header) ? $header : func_get_args());
            foreach ($args as $col => $arg) {
                $this->setColProp('header', $arg, $col);
            }
            $this->numcols = max($this->numcols, count($args));
        }

        public function setColSrcs($col) {
            $this->colSrcs = (is_array($col) ? $col : func_get_args());
            $this->numcols = max($this->numcols, count($this->colSrcs));
        }

        public function setRowProp($key, $val, $r) {
            if (!isset($this->rowProps[$r]) || !is_array($this->rowProps[$r])) $this->rowProps[$r] = array();
            $this->rowProps[$r][$key] = $val;
        }

        public function setColProp($key, $val, $c) {
            if (!isset($this->colProps[$c]) || !is_array($this->colProps[$c])) $this->colProps[$c] = array();
            $this->colProps[$c][$key] = $val;
        }

        public function setCellProp($key, $val, $r, $c) {
            if (!isset($this->cellProps[$r]) || !is_array($this->cellProps[$r])) $this->cellProps[$r] = array();
            if (!isset($this->cellProps[$r][$c]) || !is_array($this->cellProps[$r][$c])) $this->cellProps[$r][$c] = array();
            $this->cellProps[$r][$c][$key] = $val;
        }

        public function getRowProp($key, $r) {
            if (isset($this->rowProps[$r][$key])) {
                return $this->rowProps[$r][$key];
            }

            return NULL;
        }

        public function getColProp($key, $c) {
            if (isset($this->colProps[$c][$key])) {
                return $this->colProps[$c][$key];
            }

            return NULL;
        }

        public function getCellProp($key, $r, $c) {
            if (isset($this->cellProps[$r][$c][$key])) {
                return $this->cellProps[$r][$c][$key];
            }

            return NULL;
        }

        public function addRow($row, $props=NULL) {
            $r = count($this->rows);
            if (!isset($this->rows[$r])) $this->rows[$r] = array();
            $this->controls =& $this->rows[$r];
            if (is_array($row)) {
                foreach ($row as $cell) {
                    $this->add($cell);
                }
            } else if (is_object($row)) {
                if (empty($this->colSrcs)) $this->colSrcs = (array)array_keys($row);
                foreach ($this->colSrcs as $col => $field) {
                    $this->add($row->{$field});
                    if (!$this->getColProp('header', $col)) $this->setColProp('header', $field, $col);
                }
            }
            if (is_array($props)) {
                foreach ($props as $key => $val) {
                    $this->setRowProp($key, $val, $r);
                }
            }
        }

        public function add($cell, $cellprops=NULL) {
            if (empty($this->rows)) $this->addRow();
            $this->controls[] = $cell;
            $this->numcols = max($this->numcols, count($this->controls));
        }

        public function &normaliseRow($db, $sql, &$row) {
//             $keys = array_keys(get_object_vars($row));
//             foreach ($keys as $idx => $key) {
//                 if (in_array($key, $this->colSrcs) && ($meta = $db->getColumnMeta($sql->toString(), $idx)) && isset($meta['native_type'])) {
//                     switch ($meta['native_type']) {
//                         case 'DATE':
//                             if ($row->{$meta['name']} !== NULL) {
//                                 $tmp = $row->{$meta['name']};
//                                 $row->{$meta['name']} = new HOLDate();
//                                 $row->{$meta['name']}->fromDBString($tmp);
//                             }
//                             break;

//                         case 'DATETIME':
//                             if ($row->{$meta['name']} !== NULL) {
//                                 $tmp = $row->{$meta['name']};
//                                 $row->{$meta['name']} = new HOLDateTime();
//                                 $row->{$meta['name']}->fromDBString($tmp);
//                             }
//                             break;
//                     }
//                 }
//             }

//             return $row;
        }

        public function dbGetNext($sql, $db) {
            if ($sql instanceof SQLFactory) {
                if (!$this->sql instanceof SQLFactory) {
                    $this->sql = $sql;
                    $this->sql->option('SQL_CALC_FOUND_ROWS');
                    if ($this->rowsperpage > 0 && (!isset($this->request['exportcsv']) || !$this->request['exportcsv'] || @$this->mod->request['export'] == 'current')) {
                        $this->sql->limit($this->rowsperpage, ifnull($this->offset, 0));
                    } else if (@$this->request['exportcsv'] && @$this->mod->request['export'] == 'selected') {
                        if (!is_array($this->pkmap)) {
                            trigger_error('ListView_CoreWidget::dbGetNext() - A primary key map must be set to export selected records', E_USER_ERROR);
                            exit;
                        }
                        if (is_array(@$this->mod->request['exportitems'])) {
                            $items =& $this->mod->request['exportitems'];
                            if (is_associative($items)) {
                                if (isset($items['pk'])) {
                                    foreach ($items['pk'] as $key => $val) {
                                        if (isset($this->pkmap[$key])) {
                                            $this->sql->where($this->pkmap[$key] . ' = ' . $db->quote($val));
                                        }
                                    }
                                }
                            } else {
                                $vals = array();
                                foreach ($items as $item) {
                                    if (isset($item['pk'])) {
                                        foreach ($item['pk'] as $key => $val) {
                                            if (strlen($val) > 0) {
                                                if (!isset($vals[$key])) $vals[$key] = array();
                                                $vals[$key][] = $val;
                                            }
                                        }
                                    }
                                }
                                foreach ($vals as $key => $val) {
                                    if (isset($this->pkmap[$key]) && is_array($val)) {
                                        $this->sql->where($this->pkmap[$key] . ' IN (' . implode(',', $db->quotearray($val)) . ')');
                                    }
                                }
                            }
                        }
                    }
                    $sortorder = ($this->sortRev ? 'DESC' : 'ASC');
                    if ($this->sortCol < count($this->colSrcs)) {
                        $this->sql->orderby($this->colSrcs[$this->sortCol], $sortorder);
                    } else {
                        $this->sql->orderby($this->colSrcs[0], $sortorder);
                    }
                }

                if ($row = $db->getnext($sql->toString())) {
                    if (is_null($this->totalrows)) {
                        $this->totalrows = $db->foundrows();
                    }

                    return $this->normaliseRow($db, $sql, $row);
                }
            }

            return NULL;
        }

        public function addNav($parent) {
            if ($this->rowsperpage === NULL) {
                trigger_error('ListView_CoreWidget::addNav() - Listview navigation can not be added when rows per page is not set', E_USER_ERROR);
                exit;
            }

            $totalpages = ceil($this->totalrows/$this->rowsperpage);
            $currpage   = floor($this->offset/$this->rowsperpage)+1;

            $parent->add('button', 'First', 'listview-first.png')->connect('click', $this, 'first');
            if ($this->offset == 0) $parent->last->disabled = TRUE;
            $parent->add('button', 'Previous', 'listview-prev.png')->connect('click', $this, 'previous');
            if ($this->offset == 0) $parent->last->disabled = TRUE;
            $parent->add('text', sprintf(langstr('%d of %d'), $currpage, $totalpages), FALSE);
            $parent->last->nowrap = TRUE;
            $parent->add('button', 'Next', 'listview-next.png')->connect('click', $this, 'next');
            if ($this->offset >= $this->totalrows-$this->rowsperpage) $parent->last->disabled = TRUE;
            $parent->add('button', 'Last', 'listview-last.png')->connect('click', $this, 'last');
            if ($this->offset >= $this->totalrows-$this->rowsperpage) $parent->last->disabled = TRUE;

            $this->navAdded = TRUE;
        }

        /**
         * exportcsv() exports data displayed on the listview to CSV format.
         *
         * It can export all records, the current page or selected records
         * by specifying all, current or selected in the connection 'export' argument.
         * The default behaviour is to export all records
         */
        public function exportcsv() {
            header('Pragma: ');
            header('Content-type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="data.csv"');

            $headers = array();
            for ($i=0; $i < $this->numcols; $i++) {
                $headers[] = ifnull($this->getColProp('header', $i), "");
            }
            print csvencode($headers);

            foreach ($this->rows as $row) {
                print csvencode($row);
            }
            exit;
        }

        public function deliver() {
            if ($this->rowsperpage !== NULL && $this->navAdded !== TRUE) {
                trigger_error('ListView::deliver() - A rows per page value has been set but no navigation was added', E_USER_ERROR);
                exit;
            }

            if (isset($this->request['exportcsv']) && $this->request['exportcsv']) {
                $this->exportcsv();
            }

            parent::deliver();
        }
    }

    /* Form controls */
    class Button_MilkControl extends MilkControl {
        public $signals = array('click');
        public $value;
        public $src;

        public function __construct($parent, $value, $src=NULL) {
            parent::__construct($parent);
            $this->value = $value;
            $this->src   = $src;
        }
    }

    abstract class Form_MilkControl extends MilkControl {
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

    class TextBox_MilkControl extends Form_MilkControl {
        public $signals = array('enter');
        public $minlen;
        public $maxlen;

        public function __construct($parent, $name, $value=NULL, $attrs=NULL) {
            parent::__construct($parent, $name, $value, $attrs);
            $this->minlen = $this->getAttrib('min');
            $this->maxlen = $this->getAttrib('max');
        }
    }

    class PasswordBox_MilkControl extends Form_MilkControl {
        public $signals = array('enter');
    }

    class ListBox_MilkControl extends Form_MilkControl {
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

    class Boolean_MilkControl extends Form_MilkControl {
        public $signals = array('change');
    }

    class ChoosBox_MilkControl extends Form_MilkControl {
        public $signals = array('change', 'choose');
        public $slots = array('setvalue', 'clear');
    }

    class DateBox_MilkControl extends Form_MilkControl {
        public $signals = array('enter');
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
