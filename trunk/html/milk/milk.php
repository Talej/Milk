<?php

    define('DIR_SEP',                          DIRECTORY_SEPARATOR);
    define('MILK_DIR',                 dirname(realpath(__FILE__)));
    define('MILK_BASE_DIR', MilkLauncher::mkPath(MILK_DIR, 'base'));
    define('MILK_EXT_DIR',   MilkLauncher::mkPath(MILK_DIR, 'ext'));
    define('MILK_APP_DIR',   MilkLauncher::mkPath(MILK_DIR, 'app'));

    abstract class MilkFrameWork {
        protected $props;
        protected $hooks;

        public function __construct() {
            $this->props = array();
            $this->hooks = array();
        }

        public function setProp($key, $val, $override=FALSE) {
            assert('is_string($key)');

            if ($override || !isset($this->props[$key])) {
                $this->props[$key] = $val;
            }
        }

        public function getProp($key, $default=NULL) {
            assert('is_string($key)');

            if (isset($this->props[$key])) {
                return $this->props[$key];
            }

            return $default;
        }

        public function removeProp($key) {
            assert('is_string($key)');

            unset($this->props[$key]);
        }

        protected function addHook($hook) {
            assert('is_string($hook)');

            if (!isset($this->hooks[$hook])) {
                $this->hooks[$hook] = array();
            }
        }

        public function hasHook($hook) {
            assert('is_string($hook)');

            return isset($this->hooks[$hook]);
        }

        public function removeHookHandler($hook, &$cb, $obj=NULL) {
            if ($obj == NULL) $obj = $this;
            assert('is_string($hook)');
            assert('$obj instanceof MilkFrameWork');

            if ($obj->hasHook($hook) && ((is_string($cb) && function_exists($cb)) || is_callable($cb))) {
                for ($i=0; $i < count($this->hooks[$hook]); $i++) {
                    if ($this->hooks[$hook][$i] === $cb) {
                        array_splice($this->hooks[$hook], $i, 1);
                        return TRUE;
                    }
                }
            }

            return FALSE;
        }

        public function addHookHandler($hook, &$cb, $obj=NULL) {
            if ($obj == NULL) $obj = $this;
            assert('is_string($hook)');
            assert('$obj instanceof MilkFrameWork');

            if ($obj->hasHook($hook) && ((is_string($cb) && function_exists($cb)) || is_callable($cb))) {
                $this->hooks[$hook][] =& $cb;
                return TRUE;
            }

            return FALSE;
        }

        protected function execHook($hook) {
            assert('is_string($hook)');

            if (isset($this->hooks[$hook]) && is_array($this->hooks[$hook])) {
                foreach ($this->hooks[$hook] as $handler) {
                    if (is_string($handler) && function_exists($handler)) {
                        return $handler();
                    } else if (is_callable($handler)) {
                        return call_user_func($handler);
                    } else {
                        trigger_error('MilkFramework::execHook() - ' . $handler . ' is not a valid callback', E_USER_ERROR);
                    }
                }
            } else {
                trigger_error('MilkFramework::execHook() - Hook ' . $hook . ' does not exist', E_USER_ERROR);
            }
            
            return FALSE;
        }
    }

    class MilkLauncher extends MilkFrameWork {
        public $module;
        public $config;
        protected $moduleName;
        static protected $HTTPStatuses = array(
            '200' => 'OK',
            '304' => 'Not Modified',
            '403' => 'Forbidden',
            '404' => 'Not Found'
        );

        public function __construct($mod) {
            // Load core framework classes
            $this->load(MILK_BASE_DIR, 'framework', 'module.php');
            $this->load(MILK_BASE_DIR, 'framework', 'control.php');
            $this->load(MILK_BASE_DIR, 'framework', 'datadef.php');
            $this->load(MILK_BASE_DIR, 'framework', 'connection.php');
            $this->load(MILK_BASE_DIR, 'framework', 'validate.php');
            $this->load(MILK_BASE_DIR, 'framework', 'theme.php');

            // include all required files
            $this->load(MILK_BASE_DIR, 'util', 'tools.php');
            $this->load(MILK_BASE_DIR, 'util', 'date.php');
            $this->load(MILK_BASE_DIR, 'util', 'url.php');
            $this->load(MILK_BASE_DIR, 'util', 'sql.php');
            $this->load(MILK_BASE_DIR, 'util', 'useragent.php');

            // load config
            $this->config = $this->loadConfig();

            // Load base and extension controls
            $this->loadDir(MILK_BASE_DIR, 'control');
            $this->loadDir(MILK_EXT_DIR, 'control');

            // Load all app library files
            $this->loadDir(MILK_APP_DIR, 'lib');

            // load & execute module
            $this->moduleName = $mod;
            $this->loadModule();
        }

        public static function load($arg) {
            if (is_readable($file = self::mkPath(func_get_args()))) {
                if (require_once($file)) return TRUE;
            } else {
                trigger_error('MilkLauncher::load() - Unable to load file ' . $file, E_USER_ERROR);
                exit;
            }

            return FALSE;
        }

        public static function loadDir($arg) {
            if (is_readable($dir = self::mkPath(func_get_args())) && ($dp = opendir($dir))) {
                while (($file = readdir($dp)) !== FALSE) {
                    if ($file == '.' || $file == '..' || $file{0} == '.') continue;
                    $path = self::mkPath($dir, $file);
                    if (is_dir($path)) {
                        self::loadDir($path);
                    } else {
                        self::load($dir, $file);
                    }
                }
            } else {
                trigger_error('MilkLauncher::load() - Unable to load directory ' . $dir, E_USER_ERROR);
                exit;
            }

            return FALSE;
        }

        public static function loadConfig() {
            self::load(MILK_BASE_DIR, 'framework', 'config.php');
            $GLOBALS['config'] = new MilkConfig();

            self::loadDir(MILK_APP_DIR, 'config');
            self::loadDir(MILK_EXT_DIR, 'config');
            self::load(MILK_BASE_DIR, 'config', 'default.php');

            $GLOBALS['config']->define();
            return $GLOBALS['config'];
        }

        static public function mkPath($arg) {
            if (is_array($arg)) {
                $args =& $arg;
            } else {
                $args = func_get_args();
            }

            return implode(DIR_SEP, $args);
        }

        static public function moduleExists($module) {
            $file = self::mkPath(MILK_APP_DIR, 'module', strtolower($module) . '.php');
            if (is_readable($file)) {
                return TRUE;
            }

            return FALSE;
        }

        protected function loadModule() {
            if ($this->load(MILK_APP_DIR, 'module', strtolower($this->moduleName) . '.php')) {
                $classname = str_replace('.', '', $this->moduleName) . '_MilkModule';
                if (class_exists($classname) && is_subclass_of($classname, 'MilkModule')) {
                    $this->module = new $classname();
                    $this->module->config = $this->config;
                    return TRUE;
                } else {
                    trigger_error('MilkLauncher::loadModule() - Unable to load module class ' . $classname, E_USER_ERROR);
                }
            } else {
                return FALSE;
            }
        }

        public function addControlSet($name) {
            if ($this->load(MILK_EXT_DIR, 'control', $name . '.php')) {
                return TRUE;
            }

            return FALSE;
        }

        static public function http_virtualise() {
            global $PHP_SELF, $QUERY_STRING, $HTTP_SERVER_VARS, $HTTP_GET_VARS;

            if  (@substr_compare(urldecode($_SERVER['REQUEST_URI']), $_SERVER['SCRIPT_NAME'], 0) !== 0) {
                @list($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']) = explode('?', $_SERVER['REQUEST_URI'], 2);
                // Decode URI encoding
                $_SERVER['PHP_SELF'] = urldecode($_SERVER['PHP_SELF']);
                // Extract GET request parameters
                parse_str($_SERVER['QUERY_STRING'], $_GET);

                // Update HTTP_SERVER_VARS & HTTP_GET_VARS if configured
                if (ini_get('register_long_arrays') || version_compare(PHP_VERSION, '5.0.0', '<')) {
                    $HTTP_SERVER_VARS['PHP_SELF']     = $_SERVER['PHP_SELF'];
                    $HTTP_SERVER_VARS['QUERY_STRING'] = $_SERVER['QUERY_STRING'];
                    $HTTP_GET_VARS                    = $_GET;
                }

                // Update globals if configured
                if (ini_get('register_globals')) {
                    $PHP_SELF     = $_SERVER['PHP_SELF'];
                    $QUERY_STRING = $_SERVER['QUERY_STRING'];
                    // Re-import everything into the global scope, we can't just import
                    // HTTP_GET_VARS as entries in it may have been overridden by
                    // other types (eg HTTP_POST_VARS etc)
                    // Suppress warning about the empty prefix
                    @import_request_variables(ini_get('variables_order'), '');
                }

                // Reconstruct _SERVER in the correct order with the new _GET
                // Don't use array_merge() here, it re-indexes numeric keys
                foreach (str_split(ini_get('variables_order'), 1) as $type) {
                    switch ($type) {
                        case 'G': $_REQUEST = $_GET    + $_REQUEST; break;
                        case 'P': $_REQUEST = $_POST   + $_REQUEST; break;
                        case 'C': $_REQUEST = $_COOKIE + $_REQUEST; break;
                    }
                }

                return TRUE;
            } else {
                return FALSE;
            }
        }

        static public function http_set_status($status) {
            $desc = (isset(self::$HTTPStatuses[$status]) ? self::$HTTPStatuses[$status] : NULL);
            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status  . ' ' . $desc);
        }
    }
