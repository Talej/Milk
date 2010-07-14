<?php

    /**
     * $Id$
     */

    class MilkValidate {
        public $savedata = array();

        protected $errors = array();

        public function setError($str) {
            $this->errors[] = $str;
        }

        public function getErrors() {
            return $this->errors;
        }

        public function clearErrors() {
            $this->errors = array();
        }

        public function createLabel($key) {
            if (substr($key, -2, 2) == 'ID') $key = substr($key, 0, -2);
            $words = preg_split('/([A-Z][^A-Z]+)/', $key, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $label = '';
            foreach ($words as $word) {
                $label.= ($label != '' ? ' ' . strtolower($word) : $word);
            }
            return $label;
        }

        public function getProp($props, $prop, $default=NULL) {
            if (is_array($props) && isset($props[$prop])) {
                return $props[$prop];
            }

            return $default;
        }

        protected function save($key, $val) {
            $this->savedata[$key] = $val;
        }

        public function getValidateMethod($type) {
            $func = NULL;
            switch ($type) {
                case 'id':       $func = 'id';         break;
                case 'text':     $func = 'text';       break;
                case 'bool':     $func = 'bool';       break;
                case 'number':   $func = 'number';     break;
                case 'list':     $func = 'optionlist'; break;
                case 'date':     $func = 'date';       break;
                case 'datetime': $func = 'datetime';   break;
                case 'time':     $func = 'time';       break;
                case 'chooser':  $func = 'chooser';    break;
                case 'password': $func = 'password';   break;
                case 'file':     $func = 'file';       break;

                default:
                    trigger_error('MilkValidate::validate() - Unable to find validation method for ' . $field, E_USER_ERROR);
                    break;
            }

            return $func;
        }

        public function validate($dd, $request=NULL) {
            if ($request === NULL) $request =& $dd->module->request;
            $v = new MilkValidate();
            if ($dd instanceof DataDef && is_array($dd->fields)) {
                foreach ($dd->fields as $field => $props) {
                    if (!self::getProp($props, DD_ATTR_READONLY, FALSE) && !self::getProp($props, DD_ATTR_AUTO, FALSE)) {
                        if (self::getProp($props, 'type') == 'datadef') {
                            if (isset($request[$field]) && is_array($request[$field])) {
                                if ($sdd = self::getProp($props, DD_ATTR_DEF)) {
                                    $dd->subsavedata[$field] = array();
                                    foreach ($request[$field] as $subreq) {
                                        $sdd->validate($subreq);
                                        $sdd->module->presave($sdd);
                                        $dd->subsavedata[$field][] = $sdd->savedata;
                                        $dd->errors = array_merge($dd->errors, $sdd->errors);
                                    }
                                } else {
                                    trigger_error('MilkValidate::validate() - Unable to find sub-data definition for ' . $field, E_USER_ERROR);
                                }
                            }
                        } else {
                            $func = self::getValidateMethod(self::getProp($props, 'type'));
                            $validate = FALSE;
                            if ($dd->isNewPk() && !isset($request[$field])) {
                                $validate = TRUE;
                                $value = $dd->getAttrib($field, DD_ATTR_DEFAULT, NULL);
                            } else if ($dd->isNewPk() || isset($request[$field])) {
                                $validate = TRUE;
                                $value = (isset($request[$field]) ? $request[$field] : NULL);
                            }

                            if ($validate && method_exists($v, $func)) {
                                $v->{$func}($value, $field, $props);
                            }

                            if ($func == 'file') {
                                $request[$field] = (isset($v->savedata[$field]) ? $v->savedata[$field] : NULL);
                            }
                        }
                    }
                }
                $dd->savedata = array_merge($dd->savedata, $v->savedata);
                $dd->errors = array_merge($dd->errors, $v->getErrors());
            }
        }

        function isNull($val) {
            return (is_null($val) || (is_array($val) && empty($val)) || (is_string($val) && ($val === '\N' || strlen($val) == 0)));
        }

        function any($val, $props, $key=NULL) {
            if (isset($this) && is_a($this, 'MilkValidate')) {
                return call_user_func(array(&$this, $this->getValidateMethod($this->getProp($props, 'type'))), $val, $key, $props);
            } else {
                $v = new MilkValidate();
                return call_user_func(array(&$v, $v->getValidateMethod($v->getProp($props, 'type'))), $val, $key, $props);
            }
        }

        public function id($val, $key, $props) {
            if (!MilkTools::isId($val) && (!self::getProp($props, 'pk', FALSE) || $val != '\N')) {
                self::setError(sprintf('%s is not a valid id value', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }

            self::save($key, $val);

            return TRUE;
        }

        public function text($val, $key, $props) {
            if (!is_scalar($val) && $val !== NULL && !is_bool($val)) {
                self::setError(sprintf('%s is not a valid piece of text', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }
            if (self::getProp($props, 'required', FALSE) && strlen($val) == 0) {
                self::setError(sprintf('%s is required', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }
            if (strlen($val) < self::getProp($props, 'min', 0)) {
                self::setError(sprintf('%s must be at least %d characters', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), self::getProp($props, 'min', 0)));
                return FALSE;
            }
            if (self::getProp($props, 'max', 0) > 0 && strlen($val) > self::getProp($props, 'max', 0)) {
                self::setError(sprintf('%s must be no more than %d characters', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), self::getProp($props, 'max', 0)));
                return FALSE;
            }
            if (self::getProp($props, 'regex', FALSE) && !preg_match('/' . self::getProp($props, 'regex') . '/', $val)) {
                self::setError(sprintf('%s does not match the specified pattern: %s', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), self::getProp($props, 'regex')));
                return FALSE;
            }

            self::save($key, $val);

            return TRUE;
        }

        public function password($val, $key, $props, $encrypt=true) {
            $val = ($encrypt && $val != '' ? crypt($val) : $val);
            return self::text($val, $key, $props);
        }

        public function stripprefix($val, $props) {
            if (array_key_exists(DD_ATTR_CURRENCY, $props)) {
                if (substr($val, 0, 1) == $props[DD_ATTR_CURRENCY]) {
                    $val = substr($val, 1);
                }
            }
            return $val;
        }

        public function number($val, $key, $props) {
            $val = self::stripprefix($val, $props);

            if (!is_numeric($val) && strlen($val) > 0) {
                self::setError(sprintf('%s is not a valid number', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }
            if (self::getProp($props, 'required', FALSE) && strlen($val) == 0) {
                self::setError(sprintf('%s is required', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }
            if ($val < self::getProp($props, 'min', 0)) {
                self::setError(sprintf('%s must be at least %d', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), self::getProp($props, 'min', 0)));
                return FALSE;
            }
            if (self::getProp($props, 'max', 0) > 0 && $val > self::getProp($props, 'max', 0)) {
                self::setError(sprintf('%s must be no more than %d', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), self::getProp($props, 'max', 0)));
                return FALSE;
            }
            if (self::getProp($props, 'regex', FALSE) && !preg_match('/' . self::getProp($props, 'regex') . '/', $val)) {
                self::setError(sprintf('%s does not match the specified pattern: %s', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), self::getProp($props, 'regex')));
                return FALSE;
            }

            self::save($key, $val);

            return TRUE;
        }

        public function bool($val, $key, $props) {
            if (!is_bool($val)) $val = MilkTools::strToBool($val);

            self::save($key, $val);

            return TRUE;
        }

        public function optionlist($val, $key, $props) {
            if (!is_array($val) && $val !== NULL) $val = (array)$val;
            if (self::getProp($props, 'required', FALSE) && $val === NULL) {
                self::setError(sprintf('%s is required', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }
            if (count($val) < self::getProp($props, 'min', 0)) {
                self::setError(sprintf('%s must have at least %d options selected', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), self::getProp($props, 'min', 0)));
                return FALSE;
            }
            if (self::getProp($props, 'max', 0) > 0 && count($val) > self::getProp($props, 'max', 0)) {
                self::setError(sprintf('%s must have no more than %d options selected', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), self::getProp($props, 'max', 0)));
                return FALSE;
            }
            if (self::getProp($props, 'options', FALSE)) {
                if (is_array($val)) {
                    foreach ($val as $v) {
                        if (!array_key_exists($v, self::getProp($props, 'options'))) {
                            self::setError(sprintf('%s is not a valid option for %s', $v, MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                            return FALSE;
                        }
                    }
                }
            }

            if (count($val) == 1) {
                self::save($key, @$val[0]);
            } else {
                self::save($key, $val);
            }

            return TRUE;
        }

        public function date($val, $key, $props) {
            if (!$val instanceof MilkDate && $val !== NULL) $val = new MilkDate($val, MilkTools::ifDef('CFG_DATE_FORMAT', '%d/%m/%Y'));
            if (self::getProp($props, 'required', FALSE) && $val === NULL) {
                self::setError(sprintf('%s is required', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }
            if (!self::isNull($val) && (!$val instanceof MilkDate || !$val->isValid())) {
                self::setError(sprintf('%s must be a valid date', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }

            self::save($key, $val);

            return TRUE;
        }

        public function datetime($val, $key, $props) {
            if (!$val instanceof MilkDateTime && $val !== NULL) $val = new MilkDateTime($val, MilkTools::ifDef('CFG_DATETIME_FORMAT', '%d/%m/%Y %H:%M:%S'));
            if (self::getProp($props, 'required', FALSE) && $val === NULL) {
                self::setError(sprintf('%s is required', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }
            if (!self::isNull($val) && (!$val instanceof MilkDateTime || !$val->isValid())) {
                self::setError(sprintf('%s must be a valid date/time', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }

            self::save($key, $val);

            return TRUE;
        }

        public function time($val, $key, $props) {
//             if (!$val instanceof HolTime && $val !== NULL) $val = new HolTime($val, ifdef('CFG_DEFAULT_TIME_FORMAT', '%H:%M:%S'));
            if (self::getProp($props, 'required', FALSE) && $val === NULL) {
                self::setError(sprintf('%s is required', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }
//             if (!$val instanceof HolTime || !$val->isValid()) {
//                 self::setError(sprintf('%s must be a valid time', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
//                 return FALSE;
//             }

            self::save($key, $val);

            return TRUE;
        }

        public function chooser($val, $key, $props) {
            if ((!is_array($val) || count($val) < 2) && $val !== NULL) {
                self::setError(sprintf('%s is not a valid chooser value', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }
            if (self::getProp($props, 'required', FALSE) && $val === NULL) {
                self::setError(sprintf('%s is required', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }

            self::save($key, $val[0]);

            return TRUE;
        }

        public function file($val, $key, $props) {
            if (is_array($val) && isset($val['error'])) {
                switch ($val['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        self::setError(sprintf('%s exceeds the maximum upload size of %dMb', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), ini_get('upload_max_filesize')));
                        $val = NULL;
                        return FALSE;

                    case UPLOAD_ERR_FORM_SIZE:
                        self::setError(sprintf('%s exceeds the maximum upload size of %dMb', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), $_REQUEST['MAX_FILE_SIZE']));
                        $val = NULL;
                        return FALSE;

                    case UPLOAD_ERR_PARTIAL:
                        self::setError(sprintf('%s was only partially uploaded', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                        $val = NULL;
                        return FALSE;

                    case UPLOAD_ERR_NO_FILE:
                        $val = NULL;
                        break;

                    case UPLOAD_ERR_NO_TMP_DIR:
                    case UPLOAD_ERR_CANT_WRITE:
//                     case UPLOAD_ERR_EXTENSION:
                        self::setError(sprintf('An error occured while uploading %s', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                        $val = NULL;
                        return FALSE;
                }
            }

            if (self::getProp($props, 'required', FALSE) && !is_array($val)) {
                self::setError(sprintf('%s is required', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key))));
                return FALSE;
            }

//             if (HOLMedia::isValidData($val)) {
//                 if (self::getProp($props, 'filetype') && !preg_match('/' . self::getProp($props, 'filetype') . '/', HOLMedia::getMimeType($val['type'], $val['tmp_name']))) {
//                     self::setError(sprintf(langstr('%s does not match required file type'), ifnull(self::getProp($props, 'label'), self::createLabel($key))));
//                     return FALSE;
//                 }
                if (!self::isNull($val) && ($exts = self::getProp($props, 'exts'))) {
                    $ext = strtolower(substr($val['name'], strrpos($val['name'], '.')+1));
                    if (is_array($exts) && !empty($exts) && !in_array($ext, $exts)) {
                        self::setError(sprintf('%s must have an extension of %s', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), implode(', ', $exts)));
                        return FALSE;
                    } else if (!is_array($exts) && $ext != $exts) {
                        self::setError(sprintf('%s must have an extension of %s', MilkTools::ifNull(self::getProp($props, 'label'), self::createLabel($key)), $exts));
                        return FALSE;
                    }
                }
// 
//                 if (!isset($val['skey'])) {
//                     $tmpfile = '/tmp/filebox-' . rand(10000, 99999) . time();
//                     if (move_uploaded_file($val['tmp_name'], $tmpfile)) {
//                         $val['sname'] = $tmpfile;
//                         $val['skey'] = crypt($tmpfile);
//                     }
//                 } else if (!isset($val['sname']) || crypt($val['sname'], $val['skey']) != $val['skey']) {
//                     self::setError(sprintf(langstr('%s data has been corrupted. Please upload the file again'), ifnull(self::getProp($props, 'label'), self::createLabel($key))));
//                     return FALSE;
//                 }
//             }
//
            if (!is_array($val)) $val = NULL;

            self::save($key, $val);
// 
            return TRUE;
        }
    }
