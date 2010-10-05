<?php

    $i=0;
    define('JSTYPE_STRING', $i++);
    define('JSTYPE_INT',    $i++);
    define('JSTYPE_FLOAT',  $i++);
    define('JSTYPE_NULL',   $i++);
    define('JSTYPE_BOOL',   $i++);
    define('JSTYPE_ARRAY',  $i++);
    define('JSTYPE_OBJECT', $i++);
    define('JSTYPE_HASH',   $i++);
    define('JSTYPE_REGEX',  $i++);

    class MilkTools {

        static public function isId($val) {
            if ((is_int($val) || (is_string($val) && ctype_digit($val))) && (int)$val > 0) {
                return TRUE;
            }

            return FALSE;
        }

        static public function ifNull($arg1, $arg2) {
            $args = func_get_args();
            foreach ($args as $arg) {
                if ($arg !== NULL) return $arg;
            }

            return NULL;
        }

        static public function ifDef($const, $default) {
            assert('is_string($const)');

            if (defined($const)) return constant($const);

            return $default;
        }

        static public function mkPath($file) {
            return MilkLauncher::mkPath(func_get_args());
        }

        /**
         * arrayKeypathExists is a multi-key version of array_key_exists.
         *
         * It takes a multi-dimensional array and checks that the entire key path exists
         *
         * @return boolean TRUE if the key path exists, FALSE otherwise
         * @param array $arr the array to check for the key path in
         * @param mixed ... any number of key arguments to check for
         */
        static public function arrayKeypathExists($arr, $key1) {
            if (is_array($arr)) {
                if (is_array($key1)) {
                    $keys = $key1;
                } else {
                    $args = func_get_args();
                    array_shift($args);
                    $keys = count($args);
                }
                for ($i=0; $i < $keys; $i++) {
                    if (!is_array($arr) || !array_key_exists($args[$i], $arr)) {
                        return FALSE;
                    } else {
                        $arr = $arr[$args[$i]];
                    }
                }

                return TRUE;
            } else {
                trigger_error('MilkTools::arrayKeypathExists() - The first argument should be either an array or an object', E_USER_WARNING);
            }

            return FALSE;
        }

        /**
         * associativeKeyExists() recursively checks if a key exists in a multi-dimensional array
         *
         * @access public
         * @return bool TRUE if the key is found, FALSE otherwise
         * @param array $arr the array to search for the key in
         * @param string $key the name of the key to search for
         */
        static public function associativeKeyExists($arr, $key) {
            if (is_array($arr)) {
                if (isset($arr[$key])) {
                    return TRUE;
                } else {
                    foreach ($arr as $val) {
                        if (is_array($val) && MilkTools::associativeKeyExists($val, $key)) {
                            return TRUE;
                        }
                    }
                }

                return FALSE;
            } else {
                trigger_error('MilkTools::associativeKeyExists() - The first argument should be an array or an object', E_USER_WARNING);
            }
        }

        /**
         * getAssociativeKeypath() retrieves the path to the specified key
         *
         * @access public
         * @return mixed an array containing the path to the specified key
         * @param array $arr the array to search in
         * @param string $key the key to find the path for
         */
        static public function getAssociativeKeypath($arr, $key) {
            if (MilkTools::associativeKeyExists($arr, $key)) {
                $path = array();
                if (isset($arr[$key])) {
                    $path[] = $key;
                } else {
                    foreach ($arr as $subkey => $subarr) {
                        if (is_array($subarr) && ($subpath = MilkTools::getAssociativeKeypath($subarr, $key))) {
                            $path[] = $subkey;
                            $path = array_merge($path, $subpath);
                        }
                    }
                }

                return $path;
            }

            return FALSE;
        }

        /**
         * findArrayValue() retrieves the value from an array given a key path
         *
         * @access public
         * @return mixed the value on success or FALSE on failure
         * @param array $arr the array to find the value in
         * @param string ... the key path to find the value for
         */
        static public function findArrayValue($arr, $key1) {
            if (is_array($arr)) {
                if (is_array($key1)) {
                    $keys = $key1;
                } else {
                    $keys = func_get_args();
                    array_shift($keys);
                }
                if (count($keys)) {
                    $tmp =& $arr;
                    $failed = FALSE;
                    foreach ($keys as $key) {
                        if (isset($tmp[$key])) {
                            $tmp =& $tmp[$key];
                        } else {
                            $failed = TRUE;
                            break;
                        }
                    }

                    if (!$failed) return $tmp;
                }
            }

            return FALSE;
        }

        /**
        * isAssociative() checks if an array is associative
        *
        * @return bool TRUE if the argument passed is an associative array, FALSE otherwise
        * @param array $arr an array to check for associativeness
        */
        public static function isAssociative($arr) {
            if (is_array($arr)) {
                if (range(0, count($arr)-1) !== array_keys($arr)) {
                    return TRUE;
                }
            }

            return FALSE;
        }

        /**
         * associative_to_object() is used to convert an associative array to an object
         *
         * @return object the converted object on success or FALSE on failure
         * @param array $arr the array to convert to an object
         */
        public static function associativeToObject($arr) {
            if (self::isAssociative($arr) || is_object($arr)) {
                $obj = new StdClass();
                foreach ($arr as $key => $val) {
                    $obj->{$key} = (self::isAssociative($val) ? self::associativeToObject($val) : $val);
                }

                return $obj;
            }

            return FALSE;
        }

        /**
         * jsEncode() encodes any variable to be safe for use within javascript
         *
         * This function is UTF-8/Unicode safe.
         *
         * @return string the javascript encoded string
         * @param mixed $val the variable to encode for use within javascript
         * @param int $cast optional param, the javascript type to encode to (See JSTYPE defines).
         *                  If this param is not specified jsEncode will attempt to detect the type.
         */
        public static function jsEncode($val, $cast=NULL, $deep=TRUE) {
            if ($cast === NULL) {
                if (is_string($val)) {
                    $cast = JSTYPE_STRING;
                } else if (is_int($val)) {
                    $cast = JSTYPE_INT;
                } else if (is_float($val)) {
                    $cast = JSTYPE_FLOAT;
                } else if (is_null($val)) {
                    $cast = JSTYPE_NULL;
                } else if (is_bool($val)) {
                    $cast = JSTYPE_BOOL;
                } else if (is_array($val)) {
                    $cast = (MilkTools::isAssociative($val) ? JSTYPE_HASH : JSTYPE_ARRAY);
                } else if (is_object($val)) {
                    $cast = JSTYPE_OBJECT;
                }
            }

            if ($cast == JSTYPE_ARRAY) {
                $cb = array('MilkTools', 'jsEncode');
                return '[' . implode(',', array_map($cb, (array)$val)) . ']';
            } else if ($cast == JSTYPE_OBJECT || $cast == JSTYPE_HASH) {
                if (is_array($val)) {
                    $keys = array_keys($val);
                } else if (is_object($val)) {
                    $keys = get_object_vars($val);
                } else if ($val == NULL) {
                    return 'null';
                } else {
                    trigger_error('jsEncode() - Unable to convert non-object/array value to javascript object', E_USER_ERROR);
                }

                static $reserved = array(
                    'abstract', 'as', 'boolean', 'break', 'byte', 'case', 'catch', 'char', 'class', 'continue', 'const',
                    'debugger', 'default', 'delete', 'do', 'double', 'else', 'enum', 'export', 'extends', 'false', 'final',
                    'finally', 'float', 'for', 'function', 'goto', 'if', 'implements', 'import', 'in', 'instanceof', 'int',
                    'interface', 'is', 'long', 'namespace', 'native', 'new', 'null', 'package', 'private', 'protected',
                    'public', 'return', 'short', 'static', 'super', 'switch', 'synchronized', 'this', 'throw', 'throws',
                    'transient', 'true', 'try', 'typeof', 'use', 'var', 'void', 'volatile', 'while', 'with'
                );

                $pairs = array();
                foreach ($val as $k => $v) {
                    if (preg_match('/[A-Z]+[A-Z_]*/i', $k) || in_array($k, $reserved)) {
                        if ($cast == JSTYPE_OBJECT) trigger_error('jsEncode() - Invalid character or reserved word used in object property. Try using JSTYPE_HASH', E_USER_ERROR);
                        $k = MilkTools::jsEncode($k, JSTYPE_STRING);
                    }

                    $pairs[] = $k . ':' . ($deep ? MilkTools::jsEncode($v) : $v);
                }

                return '{' . implode(',', $pairs) . '}';
            } else if ($cast == JSTYPE_BOOL) {
                return ($val ? 'true' : 'false');
            } else if ($cast == JSTYPE_NULL) {
                return 'null';
            } else if ($cast == JSTYPE_INT) {
                return (string)((int)$val);
            } else if ($cast == JSTYPE_FLOAT) {
                return (string)((double)$val);
            } else if ($cast == JSTYPE_STRING) {
                $v = mb_convert_encoding((string)$val, "UTF-8");
                // Characters with single letter escapes (eg \n, \r converted to their escape sequence)
                // Also some browsers dislike </ so convert to <\/ which evaluates to the same thing
                $v = preg_replace(
                        array("/\\\\/u",  "/\n/u",  "/\r/u",  "/\t/u", "/'/u", "|</|u"),
                        array("\\\\\\\\",       "\\n",    "\\r",    "\\t",   "\\'", "<\\/"),
                        $v
                );
                // Unicode characters > 0xFF => \uDDDD
                $v = preg_replace('/([\\x{0100}-\\x{FFFF}])/ue', 'sprintf("\\\\u%04x", MilkTools::mb_ord("$1"))', $v);
                // Non-printing ascii charactarers => \xDD
                $v = preg_replace('/([^\x20-\x7E])/ue', 'sprintf("\\\\x%02x", MilkTools::mb_ord("$1"))', $v);

                return "'{$v}'";
            } else {
                trigger_error('jsEncode() - Unsupported type', E_USER_ERROR);
            }
        }

        public function normaliseNl($str, $nl="\n"){
            return str_replace(array("\n", "\r\n", "\r"), $nl, $str);
        }

        public function strToBool($s) {
            $s = strtolower($s);
            if ($s == 'yes' || $s == 'on' || $s === TRUE || $s == 1) {
                return TRUE;
            } else if ($s == 'no' || $s == 'off' || $s === FALSE || $s == 0) {
                return FALSE;
            }

            return NULL;
        }

        public static function mb_ord($c, $encoding=NULL) {
            $ucs2 = mb_convert_encoding($c, 'UCS-2LE', self::mb_encoding($encoding));
            if (strlen($ucs2) == 0) {
                return 0xFFFD;
            }
            $ucs2 = unpack('v', $ucs2);
            return reset($ucs2); // different versions of php have different values for the index, so use reset
        }

        public static function mb_encoding($force=NULL) {
            if ($force) {
                return $force;
            } else if ($_ = ini_get('mbstring.internal_encoding')) {
                return $_;
            } else {
                return 'UTF-8';
            }
        }

        public static function get_mime_type($filename) {
            if (function_exists('mime_content_type')) {
                return mime_content_type($filename);
            } else {
                return finfo_file(finfo_open(FILEINFO_MIME), $filename);
            }
        }

        public static function csvencode(&$data, $delim=',') {
            $rowbuffer = '';
            foreach ($data as $field) {
                if (is_object($field) || is_array($field)) $field = '';
                if ($rowbuffer != '') $rowbuffer .= $delim;
                if ($field == '' || strchr($field, $delim) || strchr($field, '"') || strstr($field, "\n")) {
                    $field = '"' . str_replace('"', '""', $field) . '"';
                }
                $rowbuffer .= $field;
            }

            return $rowbuffer . "\r\n";
            // Note: UTF-16LE encoding is required for asian character set support in excel.
            // However, a BOM (Byte-Order-Mark must also be used at the beginning of the file for this to work
            // return mb_convert_encoding($rowbuffer . "\r\n", 'UTF-16LE');
        }
    }
