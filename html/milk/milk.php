<?php

    define('DIR_SEP', DIRECTORY_SEPARATOR);
    define('MILK_DIR', dirname(realpath(__FILE__)));
    define('MILK_EXT_DIR', MilkLauncher::mkpath(MILK_DIR, 'ext'));
    define('MILK_APP_DIR', MilkLauncher::mkpath(MILK_DIR, 'app'));

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
                        $handler();
                    } else if (is_callable($handler)) {
                        call_user_func($handler);
                    }
                }
            }
        }
    }

    class MilkLauncher extends MilkFrameWork {

        public function __construct($mod) {
            // include all required files
            $this->load(MILK_DIR, 'base', 'util', 'tools.php');

            // load config
            $this->loadDir(MILK_APP_DIR, 'config');
            $this->loadDir(MILK_EXT_DIR, 'config');
            $this->load(MILK_DIR, 'base', 'config', 'default.php');

            // load & execute module
        }

        public static function load($arg) {
            if (is_readable($file = self::mkpath(func_get_args()))) {
                require_once($file);
            } else {
                trigger_error('MilkLauncher::load() - Unable to load file ' . $file, E_USER_ERROR);
                exit;
            }

            return FALSE;
        }

        public static function loadDir($arg) {
            if (is_readable($dir = self::mkpath(func_get_args())) && ($dp = opendir($dir))) {
                while (($file = readdir($dp)) !== FALSE) {
                    if ($file == '.' || $file == '..') continue;
                    self::load($file);
                }
            } else {
                trigger_error('MilkLauncher::load() - Unable to load directory ' . $dir, E_USER_ERROR);
                exit;
            }

            return FALSE;
        }

        static public function mkpath($arg) {
            if (is_array($arg)) {
                $args =& $arg;
            } else {
                $args = func_get_args();
            }
            return implode(DIR_SEP, $args);
        }

        protected function loadModule() {
            // TODO: Use CFG_MODULE_PATH to load file
        }
    }

    class MilkModule extends MilkFrameWork { }

    class MilkWidget extends MilkFrameWork { }

    class MilkTheme extends MilkFramework { }
