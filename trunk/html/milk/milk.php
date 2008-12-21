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
                        $handler();
                    } else if (is_callable($handler)) {
                        call_user_func($handler);
                    }
                }
            }
        }
    }

    class MilkLauncher extends MilkFrameWork {
        public $module;
        protected $moduleName;

        public function __construct($mod) {
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
                    if ($file == '.' || $file == '..' || $file{0} = '.') continue;
                    self::load($file);
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
                if (class_exists($this->moduleName) && is_subclass_of($this->moduleName, 'MilkModule')) {
                    $this->module = new $this->moduleName();
                    return TRUE;
                } else {
                    trigger_error('MilkLauncher::loadModule() - Unable to load module class ' . $this->moduleName, E_USER_ERROR);
                }
            } else {
                return FALSE;
            }
        }
    }

    class MilkModule extends MilkFrameWork {
        public $defaultAction = 'default';
        public $theme         = 'default';
        public $idSeq         = 0;
        public $idPrefix      = '';
        public $request;

        public function __construct() {
            $this->request = $_REQUEST;

            $this->addHook('prepare');
            $this->addHook('execute');
            $this->addHook('deliver');

            $cb = array($this, 'execute');
            $this->addHookHandler('execute', $cb);
            $cb = array($this, 'deliver');
            $this->addHookHandler('deliver', $cb);
        }

        public function execute() {
            if (isset($this->request['act']) && method_exists($this, $this->request['act'])) {
                $this->{$this->request['act']}();
            } else if (method_exists($this, $this->defaultAction)) {
                $this->{$this->defaultAction}();
            }
        }

        public function deliver() { }

        public function run() {
            $this->execHook('prepare');
            $this->execHook('execute');
            $this->execHook('deliver');
        }

        public function newControl($ctrl) {
            $args = func_get_args();
            $cb = array('MilkControl', 'create');
            return call_user_func_array($cb, $args);
        }
    }

    class MilkControl extends MilkFrameWork {
        public $id;
        public $parent;
        public $module;
        public $prev;
        public $controls = array();
        public $request;

        public function __construct($parent, $id=NULL) {
            $this->parent = $parent;
            if ($this->parent instanceof MilkModule) {
                $this->module = $this->parent;
            } else if ($this->parent->module instanceof MilkModule) {
                $this->module = $this->parent->module;
            }
            $this->id     = ($id === NULL ? $this->getID() : (array)$id);
            $this->name   = get_class($this);
            $this->setRequest();
        }

        public static function create($p, $ctrl) {
            if (class_exists($ctrl) && is_subclass_of($ctrl, 'MilkControl')) {
                $args = func_get_args();
                array_shift($args);

                switch (count($args)) {
                    case 0: return new $ctrl($p);
                    case 1: return new $ctrl($p, $args[0]);
                    case 2: return new $ctrl($p, $args[0], $args[1]);
                    case 3: return new $ctrl($p, $args[0], $args[1], $args[2]);
                    case 4: return new $ctrl($p, $args[0], $args[1], $args[2], $args[3]);
                    default: return eval('return new $ctrl($p, $args[' . implode('], $args[', range(0, count($args)-1)) . ']);');
                }
            } else {
                trigger_error('MilkControl::create() - Class for control ' . $ctrl . ' does not exist', E_USER_ERROR);
            }
        }

        public function setRequest() {
            $this->request = NULL;
            $tmp =& $this->mod->request;
            foreach ((array)$this->id as $key) {
                if (!is_array($tmp) || !isset($tmp[$key])) return;
                $tmp =& $tmp[$key];
            }
            $this->request =& $tmp;
        }

        public function getID() {
            $id = $this->idSpace();
            $id[] = ++$this->module->idSeq;

            return $id;
        }

        public function idSpace() {
            if ($this->parent) {
                return $this->parent->idSpace();
            } else if ($this->module->idPrefix) {
                return array($this->module->idPrefix);
            } else {
                return array();
            }
        }

        public function add($ctrl) {
            $args = func_get_args();
            $cb = array('MilkControl', 'create');
            if ($control = call_user_func_array($cb, $args)) {
                $this->controls[] = $control;
                $this->prev = $control;
                return $control;
            }
        }
    }

    class MilkTheme extends MilkFramework { }
