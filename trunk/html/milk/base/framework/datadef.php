<?php

    $i=0;
    define('DD_TYPE_ID',        ++$i);
    define('DD_TYPE_PKID',      ++$i);
    define('DD_TYPE_TEXT',      ++$i);
    define('DD_TYPE_PASSWORD',  ++$i);
    define('DD_TYPE_MULTILINE', ++$i);
    define('DD_TYPE_BOOL',      ++$i);
    define('DD_TYPE_NUMBER',    ++$i);
    define('DD_TYPE_LIST',      ++$i);
    define('DD_TYPE_DATE',      ++$i);
    define('DD_TYPE_DATETIME',  ++$i);
    define('DD_TYPE_TIME',      ++$i);
    define('DD_TYPE_DEF',       ++$i);
    define('DD_TYPE_EMAIL',     ++$i);
    define('DD_TYPE_PHONE',     ++$i);
    define('DD_TYPE_CHOOSER',   ++$i);
    define('DD_TYPE_FILE',      ++$i);
    define('DD_TYPE_CUSTOM',    ++$i);

    define('DD_ATTR_TYPE',           'type');
    define('DD_ATTR_REQUIRED',   'required');
    define('DD_ATTR_AUTO',           'auto');
    define('DD_ATTR_PK',               'pk');
    define('DD_ATTR_MULTILINE', 'multiline');
    define('DD_ATTR_LABEL',         'label');
    define('DD_ATTR_OPTIONS',     'options');
    define('DD_ATTR_MIN',             'min');
    define('DD_ATTR_MAX',             'max');
    define('DD_ATTR_REGEX',         'regex');
    define('DD_ATTR_DEFAULT',     'default');
    define('DD_ATTR_READONLY',   'readonly');
    define('DD_ATTR_DEF',              'dd');
    define('DD_ATTR_FILETYPE',   'filetype');
    define('DD_ATTR_EXTENSIONS',     'exts');
    define('DD_ATTR_PREFIX',       'prefix');
    define('DD_ATTR_CLASS',         'class');

    class dataDef {
        public $module;
        public $name;
        public $table;
        public $fields      = array();
        public $pkFields    = array();
        public $errors      = array();
        public $savedata    = array();
        public $subsavedata = array();

        function __construct($module, $name) {
            $this->module = $module;
            $this->name   = $name;
        }

        public function add($key, $type) {
            $args = func_get_args();
            array_shift($args);

            $attribs = array();
            foreach ($args as $arg) {
                if (!is_array($arg)) {
                    switch ($arg) {
                        case DD_TYPE_PKID:
                            $attribs[DD_ATTR_PK]   = TRUE;
                            $attribs[DD_ATTR_AUTO] = TRUE;
                            // fall through to ID case

                        case DD_TYPE_ID:
                            $attribs[DD_ATTR_TYPE] = 'id';
                            break;

                        case DD_TYPE_MULTILINE:
                            $attribs[DD_ATTR_MULTILINE] = TRUE;
                            $attribs[DD_ATTR_MAX]       = 4096;
                            // fall through to text case

                        case DD_TYPE_TEXT:
                            $attribs[DD_ATTR_TYPE] = 'text';
                            if (!isset($attribs[DD_ATTR_MAX])) $attribs[DD_ATTR_MAX] = 255;
                            break;

                        case DD_TYPE_PASSWORD:
                            $attribs[DD_ATTR_TYPE] = 'password';
                            break;

                        case DD_TYPE_EMAIL:
                            $attribs[DD_ATTR_REGEX] = '^([A-z0-9._%+-]+@[A-z0-9.-]+\.[A-z]{2,6})?$';
                            $attribs[DD_ATTR_TYPE]  = 'text';
                            $attribs[DD_ATTR_MAX]   = 255;
                            $attribs[DD_TYPE_EMAIL] = TRUE;
                            break;
                            
                        case DD_TYPE_PHONE:
                            $attribs[DD_ATTR_TYPE]  = 'text';
                            $attribs[DD_ATTR_MAX]   = 255;
                            $attribs[DD_TYPE_PHONE] = TRUE;
                            break;

                        case DD_TYPE_BOOL:
                            $attribs[DD_ATTR_TYPE] = 'bool';
                            break;

                        case DD_TYPE_NUMBER:
                            if (!isset($attribs[DD_ATTR_REGEX]) || strlen($attribs[DD_ATTR_REGEX]) == 0) {
                                if (($path = MilkTools::getAssociativeKeypath($args, DD_ATTR_PREFIX)) && ($prefix = MilkTools::findArrayValue($args, $path))) {
                                    $attribs[DD_ATTR_REGEX] = '^' . preg_quote($prefix) . '?[0-9,]*(\.[0-9]{2})?$';
                                } else {
                                    $attribs[DD_ATTR_REGEX] = '^[0-9,]*(\.[0-9]{2})?$';
                                }
                            }
                            $attribs[DD_ATTR_TYPE] = 'number';
                            break;

                        case DD_TYPE_LIST:
                            $attribs[DD_ATTR_TYPE] = 'list';
                            break;

                        case DD_TYPE_DATE:
                            $attribs[DD_ATTR_TYPE] = 'date';
                            break;

                        case DD_TYPE_DATETIME:
                            $attribs[DD_ATTR_TYPE] = 'datetime';
                            break;

                        case DD_TYPE_TIME:
                            $attribs[DD_ATTR_TYPE] = 'time';
                            break;

                        case DD_TYPE_DEF:
                            $attribs[DD_ATTR_TYPE] = 'datadef';
                            break;

                        case DD_TYPE_CHOOSER:
                            $attribs[DD_ATTR_TYPE] = 'chooser';
                            break;

                        case DD_TYPE_FILE:
                            $attribs[DD_ATTR_TYPE] = 'file';
                            break;
                            
                        case DD_TYPE_CUSTOM:
                            $attribs[DD_ATTR_TYPE] = 'custom';
                            break;

                        case DD_ATTR_MULTILINE:
                            if (!isset($attribs[DD_ATTR_MAX])) $attribs[DD_ATTR_MAX] = 4096;
                            $attribs[$arg] = TRUE;
                            break;

                        case DD_ATTR_REQUIRED:
                        case DD_ATTR_PK:
                        case DD_ATTR_READONLY:
                            $attribs[$arg] = TRUE;
                            break;
                    }
                } else {
                    foreach ($arg as $k => $v) {
                        switch ($k) {
                            case DD_ATTR_OPTIONS:
                                if ($v instanceof SQLFactory && isset($this->module->db)) {
                                    $v = $this->module->db->gethash($v->toString());
                                }
                                break;
                        }

                        $attribs[$k] = $v;
                    }
                }
            }

            if (!isset($this->fields[$key])) {
                $this->fields[$key] = array();
                $this->setAttribs($key, $attribs);
                if (isset($attribs[DD_ATTR_PK])) $this->pkFields[] = $key;
            }
        }

        public function setAttrib($field, $key, $val) {
            if (isset($this->fields[$field]) && is_array($this->fields[$field])) {
                $this->fields[$field][$key] = $val;
            } else {
                trigger_error('dataDef::setAttrib() - can not set attribute for non-existant field ' . $field, E_USER_ERROR);
            }
        }

        public function setAttribs($field, $vals) {
            if (isset($this->fields[$field])) {
                foreach ($vals as $k => $v) {
                    $this->setAttrib($field, $k, $v);
                }
            }
        }

        public function getAttrib($field, $key, $default=NULL) {
            if (MilkTools::arrayKeypathExists($this->fields, $field, $key)) {
                return $this->fields[$field][$key];
            }

            return $default;
        }

        public function fieldExists($field) {
            return (isset($this->fields[$field]) ? TRUE : FALSE);
        }

        public function validate($request=NULL) {
            $this->errors = array();
            $this->savedata = array();
            $this->subsavedata = array();
            $v = new MilkValidate();
            $v->validate($this, $request);
        }

        public function save() {
            $this->validate();
            $this->module->presave($this);
            $this->module->errors = array_merge($this->module->errors, $this->errors);

            if (strlen($this->table) == 0) trigger_error('dataDef::save() - Can not save data without a table name', E_USER_ERROR);

            if (empty($this->module->errors)) {
                if (is_array($this->savedata) && !empty($this->savedata)) {
                    if ($this->isNewPk()) {
                        if (($pk = $this->populatePk($this->module->db->insert($this->table, $this->module->db->quotearray($this->savedata), FALSE, FALSE))) !== FALSE) {
                            $this->module->postsave($this);
                            return $pk;
                        } else {
                            $this->module->errors[] = 'An error occured while saving. Please try again';
                        }
                    } else if ($this->isValidPk()) {
                        $pk = $this->getPk();
                        if ($this->module->db->update($this->table, $this->module->db->quotearray($this->savedata), $this->module->db->quotearray($pk), FALSE) !== FALSE) {
                            $this->module->postsave($this);
                            return $pk;
                        } else {
                            $this->module->errors[] = 'An error occured while saving. Please try again';
                        }
                    } else {
                        $this->module->errors[] = 'Invalid record specified';
                    }
                } else if ($this->isValidPk()) {
                    return $this->getPk();
                }
            }

            $this->module->postsave($this);

            return FALSE;
        }

        public function savesubset($subset, $pk=NULL, $pkmap=NULL) {
            if ($pk === NULL) $pk = $this->getPk();
            if ($this->isValidPk($pk) && $this->getAttrib($subset, DD_ATTR_TYPE) == 'datadef' && ($sds = $this->getAttrib($subset, DD_ATTR_DEF))) {
                if (strlen($sds->table) == 0) trigger_error('dataDef::savesubset() - Can not save data without a table name', E_USER_ERROR);
                if (isset($this->subsavedata[$subset]) && is_array($this->subsavedata[$subset])) {
                    $newpk = array();
                    if (is_array($pkmap)) {
                        foreach ($pk as $key => $val) {
                            if (isset($pkmap[$key])) {
                                $newpk[$pkmap[$key]] = $val;
                            } else {
                                $newpk[$key] = $val;
                            }
                        }
                    } else {
                        $newpk =& $pk;
                    }

                    if ($this->module->db->delete($sds->table, $this->module->db->quotearray($newpk), FALSE) !== FALSE) {
                        foreach ($this->subsavedata[$subset] as $data) {
                            $savedata = $data+$newpk;
                            if ($this->module->db->insert($sds->table, $this->module->db->quotearray($savedata), FALSE, FALSE) === FALSE) {
                                trigger_error('dataDef::savesubset() - A problem occured while saving the data', E_USER_ERROR);
                            }
                        }

                        return TRUE;
                    } else {
                        trigger_error('dataDef::savesubset() - A problem occured while deleting old records');
                    }
                } else {
                    return TRUE;
                }
            } else {
                trigger_error('dataDef::savesubset() - Unable to save subset due to an invalid primary key or missing data definition', E_USER_ERROR);
            }

            return FALSE;
        }

        public function delete() {
            if (strlen($this->table) == 0) trigger_error('dataDef::delete() - Can not delete data without a table name', E_USER_ERROR);

            if ($this->isValidPk()) {
                $pk = $this->getPk();
                if ($this->module->db->delete($this->table, $this->module->db->quotearray($pk), FALSE) !== FALSE) {
                    return TRUE;
                } else {
                    $this->module->errors[] = 'An error occured while deleting. Please try again';
                }
            } else {
                $this->module->errors[] = 'Invalid record specified';
            }

            return FALSE;
        }

        public function populatePk($val) {
            if ($val) {
                if (count($this->pkFields) == 1) {
                    foreach ($this->pkFields as $field) {
                        if ($this->getAttrib($field, DD_ATTR_AUTO, FALSE)) {
                            return array($field => $val);
                        }
                    }
                } else {
                    trigger_error('dataDef::populatePk() - Unable to populate a primary key with multiple fields', E_USER_ERROR);
                }
            }

            return FALSE;
        }

        public function getPk() {
            if (isset($this->module->request['pk']) && $this->isPk($this->module->request['pk'])) {
                return $this->module->request['pk'];
            }

            return NULL;
        }

        public function getNewPk() {
            $pk = array();
            foreach ($this->pkFields as $key) {
                $pk[$key] = '\N';
            }

            return $pk;
        }

        public function getPkVal($key, $pk=NULL) {
            if (is_null($pk)) $pk = $this->getPk();
            if (isset($pk[$key]) && $this->isPk($pk)) return $pk[$key];
            return NULL;
        }

        public function isPk($pk=NULL) {
            if (is_null($pk)) $pk = $this->getPk();
            if (!is_array($pk)) return FALSE;
            if (count($pk) != count($this->pkFields)) return FALSE;
            foreach ($this->pkFields as $key) {
                if (!isset($pk[$key])) return FALSE;
            }

            return TRUE;
        }

        public function isNewPk($pk=NULL) {
            if (is_null($pk)) $pk = $this->getPk();
            if (!$this->isPk($pk)) return FALSE;
            foreach ($pk as $val) {
                if (!MilkValidate::isNull($val)) {
                    return FALSE;
                }
            }

            return TRUE;
        }

        public function isValidPk($pk=NULL) {
            if (is_null($pk)) $pk = $this->getPk();
            if (!$this->isPk($pk)) {
                return FALSE;
            }
            foreach ($pk as $key => $val) {
                if (!MilkValidate::any($val, $this->fields[$key])) {
                    return FALSE;
                }
            }

            return TRUE;
        }
    }
    
    /**
     * Interface for custom DD types
     */
    interface dataDef_CustomType {
        public function set($value);
        
        public function toString();
        
        public function toDBString();
        
        public function fromDBString($value);
        
        public function validate();
        
        public function isValid();
    }
