<?php

    class MilkModule extends MilkFrameWork {
        public $defaultAction = 'index';
        public $theme         = 'standard';
        public $idSeq         = 0;
        public $idPrefix      = '';
        public $dataDefs      = array();
        public $errors        = array();
        public $request;
        public $dataDef;
        public $rootControl;

        public function __construct() {
            $this->request = $_REQUEST; // TODO: Correctly set the request (Get, post, files)

            $this->addHook('prepare');
            $this->addHook('execute');
            $this->addHook('deliver');

            $cbe = array($this, 'execute');
            $this->addHookHandler('execute', $cbe);
            $cbd = array($this, 'deliver');
            $this->addHookHandler('deliver', $cbd);

            // TODO: User agent handling
        }

        public function execute() {
            if (isset($this->request['act']) && method_exists($this, $this->request['act'])) {
                $this->{$this->request['act']}();
            } else if (method_exists($this, $this->defaultAction)) {
                $this->{$this->defaultAction}();
            } else {
                trigger_error('MilkModule::execute() - Unable to find action to execute', E_USER_ERROR);
            }
        }

        public function deliver() {
            if ($this->rootControl instanceof MilkControl) {
                $this->rootControl->deliver();
            } else {
                trigger_error('MilkModule::deliver() - Can not deliver output without a root control set', E_USER_ERROR);
                exit;
            }
        }

        public function run() {
            $this->execHook('prepare');
            $this->execHook('execute');
            $this->execHook('deliver');
        }

        public function newControl($ctrl) {
            $args = func_get_args();
            $cb = array('MilkControl', 'create');
            array_unshift($args, $this);
            return call_user_func_array($cb, $args);
        }

        public function addControl($ctrl) {
            if ($this->rootControl instanceof MilkControl) {
                trigger_error('MilkModule::addControl() - A root control has already been added for this module', E_USER_ERROR);
                exit;
            } else {
                $this->rootControl = $ctrl;
                return $ctrl;
            }
        }

        /**
         * addDD() adds a new data definition to the module
         *
         * @access public
         * @return object the data definition object
         * @param string $name optional arg, the name of the data definition
         */
        public function addDD($name=NULL) {
            if (!isset($this->dataDefs[$name])) {
                $this->dataDefs[$name] = new dataDef($this, $name);
                return $this->dataDefs[$name];
            } else {
                trigger_error('MilkModule::addDD() - A data definition named \'' . $name . '\' already exists', E_USER_WARNING);
            }

            return $this->dataDefs[$name];
        }

        /**
         * getDD() retrieves an existing data definition from the module
         *
         * @access public
         * @return mixed the data definition object on success or NULL on failure
         * @param string $name optional param, the name of the datadef to retrieve
         */
        public function getDD($name=NULL) {
            if ($name === NULL) $name = $this->dataDef;
            if (isset($this->dataDefs[$name])) {
                return $this->dataDefs[$name];
            } else {
                trigger_error('MilkModule::getDD() - A data definition named \'' . $name . '\' does not exist', E_USER_ERROR);
            }

            return NULL;
        }

        /**
         * hasDD() checks if a data definition exists in the module
         *
         * @access public
         * @return bool TRUE if the data definition exists, FALSE otherwise
         * @param string $name optional param, the name of the dataset to retrieve
         */
        public function hasDD($name=NULL) {
            if ($name === NULL) $name = $this->dataDef;
            if (isset($this->dataDefs[$name])) {
                return TRUE;
            }

            return FALSE;
        }
    }
