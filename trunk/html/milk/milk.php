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
        protected $moduleName;

        public function __construct($mod) {
            // Load core framework classes
            $this->load(MILK_BASE_DIR, 'framework', 'module.php');
            $this->load(MILK_BASE_DIR, 'framework', 'control.php');
            $this->load(MILK_BASE_DIR, 'framework', 'datadef.php');
            $this->load(MILK_BASE_DIR, 'framework', 'theme.php');

            // include all required files
            $this->load(MILK_BASE_DIR, 'util', 'tools.php');

            // load config
            $this->loadDir(MILK_APP_DIR, 'config');
            $this->loadDir(MILK_EXT_DIR, 'config');
            $this->load(MILK_BASE_DIR, 'config', 'default.php');

            // Load base and extension controls
            $this->loadDir(MILK_BASE_DIR, 'control');
            $this->loadDir(MILK_EXT_DIR, 'control');

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
                    self::load($dir, $file);
                }
            } else {
                trigger_error('MilkLauncher::load() - Unable to load directory ' . $dir, E_USER_ERROR);
                exit;
            }

            return FALSE;
        }

        static public function mkPath($arg) {
            if (is_array($arg)) {
                $args =& $arg;
            } else {
                $args = func_get_args();
            }

            return implode(DIR_SEP, $args);
        }

        protected function loadModule() {
            if ($this->load(MILK_APP_DIR, 'module', strtolower($this->moduleName) . '.php')) {
                $classname = $this->moduleName . '_MilkModule';
                if (class_exists($classname) && is_subclass_of($classname, 'MilkModule')) {
                    $this->module = new $classname();
                    return TRUE;
                } else {
                    trigger_error('MilkLauncher::loadModule() - Unable to load module class ' . $classname, E_USER_ERROR);
                }
            } else {
                return FALSE;
            }
        }
    }
