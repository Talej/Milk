<?php

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
