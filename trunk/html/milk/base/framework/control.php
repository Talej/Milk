<?php

    class MilkControl extends MilkFrameWork {
        public $id;
        public $name;
        public $parent;
        public $module;
        public $prev;
        public $controls = array();
        public $request;
        public $theme;
        public $connections = array();
        public $slotConnections = array();
        public $flex = 0;
        public $signals = array();
        public $slots   = array();
        public $strictConns = TRUE;

        public function __construct($parent, $id=NULL) {
            $this->parent = $parent;
            if ($this->parent instanceof MilkModule) {
                $this->module = $this->parent;
            } else if ($this->parent->module instanceof MilkModule) {
                $this->module = $this->parent->module;
            }
            $this->id     = ($id === NULL ? $this->getID() : (array)$id);
            $this->name   = str_replace('_MilkControl', '', get_class($this));
            
            $this->setRequest();
        }

        public static function create($p, $ctrl) {
            $ctrl.= '_MilkControl';
            if (class_exists($ctrl) && is_subclass_of($ctrl, 'MilkControl')) {
                $args = func_get_args();
                array_shift($args);
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
            $tmp =& $this->module->request;
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
            if ($this->parent instanceof MilkControl) {
                return $this->parent->idSpace();
            } else if ($this->module->idPrefix) {
                return array($this->module->idPrefix);
            } else {
                return array();
            }
        }

        public function hasParent($class=NULL) {
            if (!is_string($class) && $this->parent instanceof MilkControl) {
                return TRUE;
            } else if (class_exists($class) && $this->parent instanceof $class) {
                return TRUE;
            }

            return FALSE;
        }

        public function add($ctrl) {
            $args = func_get_args();
            $cb = array('MilkControl', 'create');
            array_unshift($args, $this);
            if ($control = call_user_func_array($cb, $args)) {
                $this->controls[] = $control;
                $this->prev = $control;
                return $control;
            }
        }

        public function deliver($theme) {
            if ($theme instanceof MilkTheme) {
                $cb = array($theme, str_replace('_MilkControl', '', get_class($this)));
                if (is_callable($cb)) {
                    call_user_func($cb, $this);
                } else {
                    trigger_error('MilkControl::deliver() - Unable to find delivery method for ' . $cb[1] . ' in ' . get_class($theme) . ' theme', E_USER_ERROR);
                    exit;
                }
            }
        }

        public function connect($signal, $target, $slot, $args=NULL) {
            if (!$this->hasSignal($signal)) trigger_error('MilkControl::connect() - ' . get_class($this) . ' control does not have a ' . $signal . ' signal', E_USER_ERROR);
            if ($target instanceof MilkControl) {
                if (!$target->hasSlot($slot)) trigger_error('MilkControl::connect() - ' . get_class($target) . ' control does not have a ' . $slot . ' slot', E_USER_ERROR);
            } else if (
                $target != MILK_SLOT_SAMEWIN &&
                $target != MILK_SLOT_NEWWIN &&
                $target != MILK_SLOT_CHILDWIN &&
                $target != MILK_SLOT_MODALWIN &&
                $target != MILK_SLOT_LAUNCHER &&
                $target != MILK_SLOT_AJAX
            ) {
                trigger_error('MilkControl::connect() - ' . $target . ' is not a valid connection target', E_USER_ERROR);
            } else if ($target != MILK_SLOT_LAUNCHER && (!is_array($args) || !isset($args['modurl']))) {
                if (!$this->module->hasAction($slot)) trigger_error('MilkControl::connect() - action ' . $slot . ' is not a valid slot', E_USER_ERROR);
            }

            $conn = new MilkConnection($this, $signal, $target, $slot, $args);
            $this->connections[] = $conn;
            $target->slotConnections[] = $conn;
        }

        public function hasSignal($signal) {
            return (in_array($signal, $this->signals) || !$this->strictConns ? TRUE : FALSE);
        }

        public function hasSlot($slot) {
            return (in_array($slot, $this->slots) || !$this->strictConns ? TRUE : FALSE);
        }

        public function getConnections($signal) {
            if ($this->hasSignal($signal)) {
                $conns = array();
                foreach ($this->connections as $conn) {
                    if ($conn->signal == $signal) {
                        $conns[] = $conn;
                    }
                }

                return $conns;
            }

            return FALSE;
        }

        public function hasConnected($signal) {
            if ($this->hasSignal($signal)) {
                foreach ($this->connections as $conn) {
                    if ($conn->signal == $signal) {
                        return TRUE;
                    }
                }
            }

            return FALSE;
        }

        public function hasAnyConnected() {
            foreach ($this->signals as $signal) {
                if ($this->hasConnected($signal)) return TRUE;
            }

            return FALSE;
        }
    }
