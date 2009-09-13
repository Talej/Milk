<?php

    class MilkModule extends MilkFrameWork {
        public $defaultAction = 'act_index';
        public $theme         = 'default';
        public $idSeq         = 0;
        public $idPrefix      = '';
        public $actions       = array();
        public $dataDefs      = array();
        public $errors        = array();
        public $history       = array();
        public $URI;
        public $request;
        public $dataDef;
        public $rootControl;

        public function __construct() {
            $this->request = $_REQUEST;

            if (is_array($_FILES)) {
                foreach (array_keys($_FILES) as $field) {
                    $this->request[$field] =& $_FILES[$field];
                }
            }

            $this->URI = new FLQURL($_SERVER['PHP_SELF']);
            foreach ($_GET as $key => $val) $this->URI->addArgument($key, $val);
            foreach ($_POST as $key => $val) $this->URI->addArgument($key, $val);
            $this->URI->delArgument('history');

            $this->addHook('prepare');
            $this->addHook('execute');
            $this->addHook('deliver');

            $cbp = array($this, 'prepare');
            $this->addHookHandler('prepare', $cbp);
            $cbe = array($this, 'execute');
            $this->addHookHandler('execute', $cbe);
            $cbd = array($this, 'deliver');
            $this->addHookHandler('deliver', $cbd);

            if (is_array($this->request) && isset($this->request['history'])) {
                if (is_array($this->request['history'])) $this->history = array_values($this->request['history']);
                unset($this->request['history']);
            }
            if (is_array($this->request) && isset($this->request['errors'])) {
                if (is_array($this->request['errors'])) $this->errors = array_values($this->request['errors']);
                unset($this->request['errors']);
            }

            $this->setProp('ua', new FLQUserAgent());
        }

        public function prepare() { }

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
                $this->history[] = $this->URI->toString();
                $theme = new MilkTheme($this);
                $theme->deliver($this->rootControl);
            } else {
                trigger_error('MilkModule::deliver() - Can not deliver output without a root control set', E_USER_ERROR);
                exit;
            }
        }

        public function run() {
            $this->execHook('prepare');
            $this->execHook('execute');
            $this->execHook('deliver');
            exit;
        }

        /**
         * addAction() registers an action method within the module
         *
         * The use of this method is optional and if no actions are registered
         * all actions will automatically be available in the module. If any
         * methods are registered using addAction() then all methods must
         * be registered to be available for execution.
         *
         * addAction() should be used to implement security restriction
         * on a specific action (for example editing, saving, deleting)
         *
         * @access public
         * @return void
         * @param string $action the name of a function/method defined in the module class
         */
        public function addAction($action) {
            if (method_exists($this, $action)) {
                $this->actions[] = $action;
            } else {
                trigger_error('MilkModule::addAction() - ' . $action . ' method does not exist in this module', E_USER_ERROR);
            }
        }

        /**
         * hasAction() checks if the specified action exists in the module
         *
         * @access public
         * @return bool TRUE if the action exists, FALSE otherwise
         * @param string $action the action to check for
         */
        public function hasAction($action) {
            $actions = array_merge($this->actions, array(MILK_ACTION_REFRESH, MILK_ACTION_RELOAD, MILK_ACTION_BACK));
            if (
                (in_array($action, $actions) || empty($this->actions)) &&
                method_exists($this, $action)
            ) {
                return TRUE;
            }

            return FALSE;
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
                trigger_error('MilkModule::getDD() - A data definition named \'' . MilkTools::ifNull($name, 'NULL') . '\' does not exist', E_USER_ERROR);
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

        public function setHistory($key, $val=NULL) {
            if (!empty($this->history)) {
                $url = new FLQURL(end($this->history));
                if ($val == NULL) {
                    $url->delArgument($key);
                } else {
                    $url->addArgument($key, $val, TRUE);
                }
                $this->history[key($this->history)] = $url->toString();
            }
        }

        /**
         * go() redirects or reloads the screen by going back the specified number of steps in the history
         *
         * @access public
         * @return void
         * @param int $steps the number of steps in history to go back
         * @param bool $redirect whether to redirect. Redirecting will cause all of the current request data to be discarded
         */
        public function go($steps, $redirect=TRUE) {
            if ($steps > 0 && $steps <= count($this->history)) {
                // Pop history off the stack until we reach the desired distance
                for ($i=0; $i < $steps; $i++) {
                    $newuri = array_pop($this->history);
                }
                $this->URI = new FLQURL($newuri);

                // If we should redirect add the history and redirect
                if ($redirect) {
                    $this->URI->addArgument('history', $this->history);
                    $this->URI->redirect();
                }

                // If we are not redirecting, merge the historical URI into the passed
                // request variables without overwriting. However, insist that the
                // action is overwriten.
                $classname = get_class($this);
                $module                = new $classname();
                $module->theme         =& $this->theme;
                $module->db            = $this->db;
                $module->history       =& $this->history;
                $module->errors        =& $this->errors;
                $module->URI           = clone $this->URI;
                $module->defaultAction = $this->defaultAction;

                foreach ($this->request as $key => $val) {
                    if ($key != 'act' && $key != 'actarg') $module->URI->addArgument($key, $val, TRUE);
                }

                $module->request = $module->URI->arguments;
                $module->run();
            } else {
                $this->addControl($this->newControl('Terminator'));
                $this->deliver();
                exit;
            }
        }

        /**
         * back() calls go with 2 steps resulting in the screen being redirected to the previous page
         *
         * @access public
         * @return void
         */
        public function back() {
            $this->go(2);
        }

        /**
         * reload() calls go with 1 step resulting in the screen being reloaded without any of the current request data
         *
         * @access public
         * @return void
         */
        public function reload() {
            $this->go(1);
        }

        /**
         * refresh() calls go with 1 step resulting in the screen being refreshed with all of the current request data maintained
         *
         * @access public
         * @return void
         */
        public function refresh() {
            $this->go(1, false);
        }
    }

    class MilkDBModule extends MilkModule {
        public $db;

        public function __construct() {
            parent::__construct();

            // TODO: Add checking here
            if (isset($GLOBALS['db'])) {
                $this->db = $GLOBALS['db'];
            } else {
                MilkLauncher::load(MILK_BASE_DIR, 'util', 'database.php');
                $this->db = new Database();
            }
        }

        /**
         * get() retrieves a record from the database for the specified data structure
         *
         * If the primary key provided is a new key, a new record will be returned
         * all of the default values populated
         *
         * @access public
         * @return mixed the retrieved record on success or FALSE on failure
         * @param string $datadef optional param, the data definition to retrieve the record for
         * @param array $pk optional param, a valid or new primary key for the record
         */
        public function get($datadef=NULL, $pk=NULL) {
            if (is_null($datadef)) $datadef = $this->dataDef;
            $dd = $this->getDD($datadef);
            if (is_null($pk)) $pk = $dd->getPk();
            if (is_null($dd->table)) trigger_error('MilkDBModule::get() - No table set for data definition', E_USER_ERROR);
            if ($dd->isNewPk($pk)) {
                $record = new StdClass();
                foreach (array_keys($dd->fields) as $key) {
                    $record->{$key} = $dd->getAttrib($key, DD_ATTR_DEFAULT, NULL);
                }
            } else if ($dd->isValidPk($pk)) {
                if ($record = $this->db->get($dd->table, $pk)) {
                    $record = $this->normaliserecord($dd, $record);
                } else {
                    $this->errors[] = 'Record could not be found';
                }
            } else {
                $record = FALSE;
                $this->errors[] = 'Invalid primary key';
            }

            return $record;
        }

        public function normaliserecord($dd, $record) {
            foreach ($dd->fields as $key => $attribs) {
                if (isset($record->{$key})) {
                    switch ($attribs['type']) {
                        case 'date':
                            $tmp = $record->{$key};
                            $record->{$key} = new MilkDate();
                            $record->{$key}->fromDBString($tmp);
                            break;

                        case 'datetime':
                            $tmp = $record->{$key};
                            $record->{$key} = new MilkDateTime();
                            $record->{$key}->fromDBString($tmp);
                            break;
                    }
                }
            }

            return $record;
        }

        /**
         * newRecord() creates a new primary key in the request for the specified data definition
         *
         * @access public
         * @return void
         * @param string $datadef optional param, the name of the data definition to create a new pk for
         */
        public function newRecord($datadef=NULL) {
            if (is_null($datadef)) $datadef = $this->dataDef;
            $dd = $this->getDD($datadef);
            $this->request['pk'] = $dd->getNewPk();
        }

        /**
         * delete() deletes the current record for the specified dataset
         *
         * @access public
         * @return bool TRUE if the record was deleted, FALSE otherwise
         * @param string $datadef the name of the data definition to delete the record for
         */
        public function delete($datadef=NULL) {
            if ($datadef === NULL) $datadef = $this->dataDef;
            $dd = $this->getDD($datadef);
            return $dd->delete();
        }

        /**
         * presave() is a hook that is called before the saving of any record in a data definition
         *
         * This hook should be overloaded in the module to manipulate any data
         * or perform extra validation before saving
         *
         * @access public
         * @return void
         * @param object $dd the data definition being saved
         */
        public function presave($dd) { }

        /**
         * save() is used to save a record for the specified data definition
         *
         * @access public
         * @return mixed the record pk on success or FALSE on failure
         * @param string $datadef optional param, the data definition of the record. If not specified the current data definition is used
         */
        public function save($datadef=NULL) {
            if ($datadef === NULL) $datadef = $this->dataDef;
            $dd = $this->getDD($datadef);
            return $dd->save();
        }

        /**
         * savesubset() saves records in the sub-data structure
         *
         * @access public
         * @return bool TRUE if saving the sub-data structure was successful, FALSE otherwise
         * @param string $subset the sub-data structure field name in the parent data structure
         * @param string $datadef the parent data definition
         * @param array
         */
        public function savesubset($subset, $datadef=NULL, $pk=NULL, $pkmap) {
            if ($datadef === NULL) $datadef = $this->dataDef;
            $dd = $this->getDD($datadef);
            return $dd->savesubset($subset, $pk, $pkmap);
        }

        /**
         * postsave() is a hook that is called after the saving of any record in a data structure
         *
         * This hook should be overloaded in the module to manipulate any data
         * or perform extra validation after saving
         *
         * @access public
         * @return void
         * @param object $ds the data structure being saved
         */
        public function postsave($dd) { }
    }
