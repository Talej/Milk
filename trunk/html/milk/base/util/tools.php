<?php


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

            if (define($const)) return constant($const);

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
                    if (!is_array($arr) || !isset($arr[$args[$i]])) {
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
    }
