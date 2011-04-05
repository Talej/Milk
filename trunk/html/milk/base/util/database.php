<?php

    /**
     * Database handling library
     *
     * This library provides extensive functionality for connecting to a database
     * server and performing various database operations
     *
     * $Id$
     *
     * @package core
     * @author Michael Little <michael@fliquidstudios.com>
     * @version 1.0
     */

    define('PDOENGINE_MYSQL', 'mysql');

    /**
     * PDO based database class
     *
     * This class is an extension of the PHP PDO class that
     * provides various methods and wrappers around existing PDO
     * functionality to facilitate reading and modification of data
     * within a database
     *
     * @package core
     */
    class Database extends PDO
    {
        public $type;
        public $host;
        public $user;
        public $name;
        public $port;
        public $fetchtype   = PDO::FETCH_OBJ;
        public $useprepared = TRUE;
        public $safemode    = TRUE;

        protected $pass;
        protected $getnextcache = array();
        protected $prestmtcache = array();
        protected $stmtcache    = array();
        protected $_useprepared = TRUE;
        protected $escapechar   = "`";
        protected $inTransaction    = FALSE;
        protected $transactionStack = 0;

        /**
         * constructor method for Database class
         *
         * All parameters are optional and if they are not provided will be retrieved from defines loaded from a config file
         *
         * @access public
         * @return a PDO object on success
         * @param string $type a valid DSN or DSN alias
         * @param string $host the host name/ip to connect to
         * @param string $user the database username
         * @param string $pass the database password for the user
         * @param string $name the database to connect to
         * @param int $port the port number to connect using
         * @param array $options A key=>value array of driver-specific connection options
         */
        function __construct($type=NULL, $host=NULL, $user=NULL, $pass=NULL, $name=NULL, $port=NULL, $options=array()) {
            $this->type    = ($type === NULL && defined('CFG_DATABASE_TYPE') ? CFG_DATABASE_TYPE : $type);
            $this->host    = ($host === NULL && defined('CFG_DATABASE_HOST') ? CFG_DATABASE_HOST : $host);
            $this->user    = ($user === NULL && defined('CFG_DATABASE_USER') ? CFG_DATABASE_USER : $user);
            $this->tmppass = ($pass === NULL && defined('CFG_DATABASE_PASS') ? CFG_DATABASE_PASS : $pass);
            $this->name    = ($name === NULL && defined('CFG_DATABASE_NAME') ? CFG_DATABASE_NAME : $name);
            $this->port    = ($port === NULL && defined('CFG_DATABASE_PORT') ? CFG_DATABASE_PORT : $port);

            // Attempt to fill in any config gaps by loading the my.cnf file
            if (strtolower($this->type) == 'mysql') {
                $this->load_mycnf();
            }

            // xor the string here so that the password won't be obvious in the event
            // that the object is dumped eg with var_dump(), print_r() etc.
            $this->pass = bin2hex($this->tmppass ^ php_uname());
            unset($this->tmppass);

            // Undo the bin2hex()
            $password = pack('H*', $this->pass);

            // Bump up the max buffer size to allow loading of large media (15M)
            if (strtolower($this->type) == 'mysql') $options[PDO::MYSQL_ATTR_MAX_BUFFER_SIZE] = 1024*1024*15;

            $result = parent::__construct($this->getDSN(), $this->user, $password ^ php_uname(), $options);

            // Turn on buffering for MySQL to allow getnext to work
            if (strtolower($this->type) == 'mysql') self::setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, TRUE);

            return $result;
        }

        /**
         * load_mycnf() attempts to load a my.cnf file to populate any empty config variables
         *
         * @access public
         * @return void
         */
        public function load_mycnf() {
            if ($this->host === NULL || $this->user === NULL || $this->tmppass === NULL || $this->name === NULL || $this->port === NULL) {
                // attempt to locate my.cnf in the following locations
                $dirs = array(
                    @$_SERVER['DOCUMENT_ROOT'],
                    @$_ENV['HOME'],
                    '~/'
                );

                $path = NULL;
                foreach ($dirs as $dir) {
                    if (($path = MilkTools::mkPath($dir, '.my.cnf')) && file_exists($path) && is_readable($path)) {
                        break;
                    } else if (($path = MilkTools::mkPath($dir, 'my.cnf')) && file_exists($path) && is_readable($path)) {
                        break;
                    } else {
                        $path = NULL;
                    }
                }

                if ($path && ($cnf = parse_ini_file($path))) {
                    if ($this->host === NULL && isset($cnf['host'])) $this->host = $cnf['host'];
                    if ($this->user === NULL && isset($cnf['user'])) $this->user = $cnf['user'];
                    if ($this->tmppass === NULL && isset($cnf['password'])) $this->tmppass = $cnf['password'];
                    if ($this->name === NULL && isset($cnf['database'])) $this->name = $cnf['database'];
                    if ($this->port === NULL && isset($cnf['port'])) $this->port = $cnf['port'];
                }
            }
        }

        /**
         * getDSN() builds a DSN string using the engine type, database name, host and port
         *
         * @access public
         * @return string the built DSN string
         */
        public function getDSN() {
            $str = strtolower($this->type) . ":dbname={$this->name};host=" . MilkTools::ifNull($this->host, '127.0.0.1');
            if ($this->port !== NULL) $str.= ";port={$this->port}";

            return $str;
        }

        /**
         * getstmt() retrieves a PDOStatement object for an SQL statement if it exists in cache
         *
         * @access public
         * @return mixed a PDOStatement object on success or FALSE on failure
         * @param string $SQL the SQL statement to retrieve the PDOStatement for
         */
        public function getstmt($SQL) {
            if (isset($this->prestmtcache[$SQL])) {
                return $this->prestmtcache[$SQL];
            } else if (isset($this->stmtcache[$SQL])) {
                return $this->stmtcache[$SQL];
            }

            return FALSE;
        }

        /**
         * stmtexec() is an internal method used to execute $SQL
         *
         * This function uses the appropriate method either by creating a prepared statement of simply by executing SQL directly
         *
         * @todo Should any other statements other than SELECT use the query method?
         * @todo detection and passing of types when binding parameters
         * @access protected
         * @return mixed a PDOStatement object on success, FALSE on failure
         * @param string $SQL an SQL statement to execute
         * @param array &$params an array of key/value pairs of parameters used to bind to a prepared statement
         */
        protected function stmtexec($SQL, &$params) {
            if ($this->_useprepared) {
                // If we are using prepared statements check if a statement
                // already exists in the cache and use it otherwise create a new one
                if (!isset($this->prestmtcache[$SQL])) {
                    if ($stmt = $this->prepare($SQL, array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE))) {
                        $this->prestmtcache[$SQL] = $stmt;
                    } else if (count($info = $this->errorInfo()) >= 2) {
                        trigger_error(sprintf('Database::stmtexec() - %s: %s', $this->errorCode(), $info[2]), E_USER_WARNING);
                    } else {
                        trigger_error('Database::stmtexec() - Unable to prepare statement: ' . $SQL, E_USER_WARNING);
                    }
                }
                if (isset($this->prestmtcache[$SQL]) && $this->prestmtcache[$SQL]) {
                    if (is_array($params)) {
                        foreach ($params as $key => $val) {
                            $this->prestmtcache[$SQL]->bindParam($key, $params[$key]);
                        }
                    }
                    if ($r = $this->prestmtcache[$SQL]->execute()) {
                        if (substr($SQL, 0, 6) == 'INSERT') $this->prestmtcache[$SQL]->closeCursor();
                        return $this->prestmtcache[$SQL];
                    } else if (count($info = $this->prestmtcache[$SQL]->errorInfo()) >= 2) {
                        trigger_error(sprintf('Database::stmtexec() - %s: %s', $this->errorCode(), $info[2]), E_USER_WARNING);
                    } else {
                        trigger_error('Database::stmtexec() - Unable to execute prepared statement: ' . $SQL, E_USER_WARNING);
                    }
                }
            } else if (preg_match('/^\(?SELECT /i', $SQL)) {
                // If it's a SELECT statement use query
                if ($stmt = $this->query($SQL)) {
                    $this->stmtcache[$SQL] = $stmt;
                    return $stmt;
                } else if (count($info = $this->errorInfo()) >= 2) {
                    trigger_error(sprintf('Database::stmtexec() - %s: %s', $this->errorCode(), $info[2]), E_USER_WARNING);
                } else {
                    trigger_error('Database::stmtexec() - Unable to execute query: ' . $SQL, E_USER_WARNING);
                }
            } else {
                // Otherwise use exec
                if (($stmt = $this->exec($SQL)) !== FALSE) {
                    $this->stmtcache[$SQL] = $stmt;
                    return $stmt;
                } else if (count($info = $this->errorInfo()) >= 2) {
                    trigger_error(sprintf('Database::stmtexec() - %s: %s', $this->errorCode(), @$info[2]), E_USER_WARNING);
                } else {
                    trigger_error('Database::stmtexec() - Unable to execute query: ' . $SQL, E_USER_WARNING);
                }
            }

            return FALSE;
        }

        public function beginTransaction() {
            if (!$this->inTransaction) {
                $this->inTransaction = TRUE;
                return parent::beginTransaction();
            } else {
                $this->transactionStack++;
            }

            return TRUE;
            // TODO: Handle break points
        }

        public function commit() {
            if ($this->transactionStack == 0 && $this->inTransaction) {
                return parent::commit();
                $this->inTransaction = FALSE;
            } else {
                $this->transactionStack--;
            }

            return TRUE;
        }

        public function rollBack() {
            if ($this->transactionStack == 0 &&$this->inTransaction) {
                return parent::rollBack();
                $this->inTransaction = FALSE;
            } else {
                $this->transactionStack--;
            }

            return TRUE;
        }

        /**
         * quotename() is used for escaping table/column names
         *
         * @todo this will currently only work with MySQL in non-ANSI mode. Find a way of detecting the engine/mode (ANSI uses '"')
         * @access public
         * @return string the escaped string
         * @param string $str the string to escape
         */
        public function quotename($str) {
            return $this->escapechar . implode($this->escapechar . "." . $this->escapechar, explode('.', $str)) . $this->escapechar;
        }

        /**
         * quote() is a wrapper around the PDO::quote function
         *
         * This function should be used for quoting/escaping any PHP variables used
         * in SQL.
         *
         * Note: If Database::quote() is used on data, it can NOT be used in a prepared statement. It is
         * up to the developer to ensure the useprepared option is disabled for that query
         *
         * @access public
         * @return mixed the quoted/escaped value
         * @param mixed $var the value to quote/escape
         */
        public function quote($var) {
            if (is_null($var)) {
                return 'NULL';
            } else if (is_bool($var)) {
                return ($var ? 1 : 0);
            } else if (is_object($var) && method_exists($var, 'toDBString')) {
                return $var->toDBString();
            } else {
                return parent::quote($var);
            }
        }

        /**
         * quotearray() is a wrapper around quote that can be used for quoting/escaping an entire array
         *
         * @access public
         * @return array the quoted/escaped array
         * @param array $var the array to quote/escape
         */
        public function quotearray($arr) {
            if (is_array($arr)) {
                foreach ($arr as $key => $val) {
                    $arr[$key] = self::quote($val);
                }
            }

            return $arr;
        }

        /**
         * canUsePrepared() takes an array of data and determines whether it can safely be used in a prepared statement or not.
         *
         * Currently this method checks for the existance of anything that looks like a
         * SQL function in the values
         *
         * @access public
         * @return bool TRUE if prepared a statement can be used with the data, FALSE otherwise
         * @param array $data an array of key/value pairs to check
         */
        public function canUsePrepared($data) {
            if (is_array($data)) {
                foreach ($data as $val) {
                    if (is_string($val) && preg_match('/[A-Z_]\([^\)]*\)/', $val)) return FALSE;
                }

                return TRUE;
            }

            return FALSE;
        }

        /**
         * buildwhere() is an internal method used for creating an SQL WHERE clause given an array of key/value pairs.
         *
         * If the internal "_useprepared" flag is on the WHERE clause will be generated
         * with parameters ready for binding, otherwise the actual values will be used
         *
         * @access protected
         * @return string the generated WHERE clause
         * @param array &$criteria an array of key/value pairs use to generate the criteria
         * @param string $where Optional param, the string to start the WHERE clause with. Defaults to WHERE
         */
        protected function buildwhere(&$criteria, $where='WHERE') {
            $SQL = '';
            if (is_array($criteria) && !empty($criteria)) {
                foreach ($criteria as $key => $val) {
                    $SQL.= (strlen($SQL) == 0 ? $where : ' AND') . ' ';
                    $SQL.= $this->quotename($key) . ' = ' . ($this->_useprepared ? ":{$key}" : $val);
                }
            } else if (is_string($criteria) && strlen($criteria) > 0) {
                if (!preg_match('/^\s*WHERE/i', $criteria)) {
                    $SQL = ' ' . $where . ' ' . $criteria;
                } else {
                    $SQL = $criteria;
                }
            }

            return $SQL;
        }

        /**
         * buildselect() is an internal method used to create an SQL SELECT statment (including WHERE clause if required)
         *
         * @todo Need to work out how much of a good idea it is to use prepared statements here
         * @access protected
         * @return string the generated SELECT statement
         * @param string $table either a single table name or a complete SQL statement
         * @param array &$criteria Optional param, an array of key/value pairs to be used in the WHERE clause
         * @param bool $useprepared Optional param, turn on/off prepared statements for this query. if not specified the current connection setting will be used
         */
        protected function buildselect($table, &$criteria=NULL, $useprepared=NULL) {
            if (preg_match('/^\(?SELECT /i', $table)) {
                $this->_useprepared = FALSE;
                $SQL = $table;
            } else {
                $this->_useprepared = ($useprepared !== NULL ? $useprepared : $this->useprepared) && $this->canUsePrepared($criteria);
                $SQL = "SELECT * FROM " . $this->quotename($table);
                if ($criteria !== NULL) $SQL.= " " . $this->buildwhere($criteria);
            }

            return $SQL;
        }

        /**
         * get() is used to retrieve a single record from the database
         *
         * @access public
         * @return mixed a record (in the fetch type format) on success or FALSE on failure
         * @param string $table either a single table name or a complete SQL statement
         * @param array $criteria Optional param, an array of key/value pairs to be used in the WHERE clause
         * @param bool $useprepared Optional param, turn on/off prepared statements for this query. if not specified the current connection setting will be used
         */
        public function get($table, $criteria=NULL, $useprepared=NULL) {
            $SQL = $this->buildselect($table, $criteria, $useprepared);

            // run the statement and get the first record
            if ($stmt = $this->stmtexec($SQL, $criteria)) {
                $result = $stmt->fetch($this->fetchtype);

                // clean up the statement as we won't be needing it any more
                $this->cleanup($stmt);

                return $result;
            }

            return FALSE;
        }

        /**
         * getnext() returns the next record in a result set until there are no more records.
         *
         * Should be used for looping over a result set
         *
         * @todo determine the effects of attempting to execute the same prepared statement (with different params) multiple times at once
         * @access public
         * @return mixed a record (in the fetch type format) on success or FALSE on failure
         * @param string $table either a single table name or a complete SQL statement
         * @param array $criteria Optional param, an array of key/value pairs to be used in the WHERE clause
         * @param bool $useprepared Optional param, turn on/off prepared statements for this query. if not specified the current connection setting will be used
         */
        public function getnext($table, $criteria=NULL, $useprepared=FALSE) {
            $SQL = $this->buildselect($table, $criteria, $useprepared);

            $usehash = FALSE;
            if (isset($this->prestmtcache[$SQL])) {
                $hash = md5(serialize($criteria));
                $usehash = TRUE;
            }

            // cache and run the statement
            if (!isset($this->getnextcache[$SQL])) {
                if ($stmt = $this->stmtexec($SQL, $criteria)) {
                    if (isset($this->prestmtcache[$SQL])) {
                        $hash = md5(serialize($criteria));
                        $usehash = TRUE;
                    }
                    if ($usehash) {
                        $this->getnextcache[$SQL] = array($hash => $stmt);
                    } else {
                        $this->getnextcache[$SQL] = $stmt;
                    }
                }
            } else if ($usehash && !isset($this->getnextcache[$SQL][$hash])) {
                if ($stmt = $this->stmtexec($SQL, $criteria)) {
                    $this->getnextcache[$SQL][$hash] = $stmt;
                }
            } else {
                $stmt = ($usehash ? $this->getnextcache[$SQL][$hash] : $this->getnextcache[$SQL]);
            }

            if ($stmt) {
                // get the next record
                if ($row = $stmt->fetch($this->fetchtype)) {
                    return $row;
                }

                // assume there's a problem or we've reached the end of the
                // result set so clean up the cache
                if ($usehash) {
                    $this->getnextcache[$SQL][$hash] = NULL;
                    unset($this->getnextcache[$SQL][$hash]);
                } else {
                    $this->getnextcache[$SQL] = NULL;
                    unset($this->getnextcache[$SQL]);
                }
            }

            return FALSE;
        }

        /**
         * cleargetnext() clears the cache for a query
         *
         * This method should be used to clear the cache so the results can be looped over again if
         * the end was not reached in the previous loop
         *
         * @access public
         * @return bool TRUE if the cache exists and was reset, FALSE otherwise
         * @param string $table either a single table name or a complete SQL statement
         * @param array $criteria Optional param, an array of key/value pairs to be used in the WHERE clause
         */
        public function cleargetnext($table, $criteria=NULL) {
            $SQL = $this->buildselect($table, $criteria);

            $usehash = FALSE;
            if (isset($this->prestmtcache[$SQL])) {
                $hash = md5(var_export($criteria, TRUE));
                $usehash = TRUE;
            }

            if (isset($this->getnextcache[$SQL])) {
                if ($usehash) {
                    $this->getnextcache[$SQL][$hash] = NULL;
                    unset($this->getnextcache[$SQL][$hash]);
                } else {
                    $this->getnextcache[$SQL] = NULL;
                    unset($this->getnextcache[$SQL]);
                }

                return TRUE;
            }

            return FALSE;
        }

        /**
         * getall() is used to retrieve an array containing all of the records in a result set
         *
         * @access public
         * @return mixed an array of records (in the fetch type format) on success or FALSE on failure
         * @param string $table either a single table name or a complete SQL statement
         * @param array $criteria Optional param, an array of key/value pairs to be used in the WHERE clause
         * @param bool $useprepared Optional param, turn on/off prepared statements for this query. if not specified the current connection setting will be used
         */
        public function getall($table, $criteria=NULL, $useprepared=NULL) {
            $SQL = $this->buildselect($table, $criteria, $useprepared);

            // run the statement and get all records
            if ($stmt = $this->stmtexec($SQL, $criteria)) {
                $result =& $stmt->fetchAll($this->fetchtype);

                // cleanup the statement if required
                $this->cleanup($stmt);

                return $result;
            }

            return FALSE;
        }

        /**
         * count() retrieves the number of rows from the table with the specified criteria
         *
         * Note that if complete SQL is passed in the first argument a column named rowCount
         * must be specified that provides the expected count
         *
         * @access public
         * @return mixed the number of counted records on success or NULL on failure
         * @param string $table either a single table name or a complete SQL statement
         * @param array $criteria Optional param, an array of key/value pairs to be used in the WHERE clause
         * @param bool $useprepared Optional param, turn on/off prepared statements for this query. if not specified the current connection setting will be used
         */
        public function count($table, $criteria=NULL, $useprepared=NULL) {
            if (preg_match('/^\(?SELECT /i', $table)) {
                $SQL = $this->buildselect($table, $crtieria, $useprepared);
            } else {
                $tmp = 'SELECT COUNT(*) AS rowCount FROM ' . $this->quotename($table);
                if ($criteria != NULL) $tmp.= ' ' . $this->buildwhere($criteria);

                if (!is_array($criteria)) $criteria = NULL;
                $SQL = $this->buildselect($tmp, $criteria, $useprepared);
            }

            if (($stmt = $this->stmtexec($SQL, $crtieria)) && ($row = $stmt->fetch($this->fetchtype)) && isset($row->rowCount)) {
                return (int)$row->rowCount;
            }

            return NULL;
        }

        /**
         * gethash() retrieves an associative array of values indexed by the specified key
         *
         * @access public
         * @return mixed an array of key/value pairs on success or FALSE on failure
         * @param string $table either a single table name or a complete SQL statement
         * @param array $criteria Optional param, an array of key/value pairs to be used in the WHERE clause
         * @param int $key1 the column offset to use for array keys
         * @param int $key2 the column offset to use for array values
         * @param bool $useprepared Optional param, turn on/off prepared statements for this query. if not specified the current connection setting will be used
         */
        public function gethash($table, $criteria=NULL, $key1=0, $key2=1, $useprepared=NULL) {
            $tmp = $this->fetchtype;
            $this->fetchtype = PDO::FETCH_NUM;
            if (($data = $this->getall($table, $criteria, $useprepared)) !== FALSE) {
                $result = array();
                foreach ($data as $val) {
                    $result[$val[$key1]] = $val[$key2];
                }

                $this->fetchtype = $tmp;
                return $result;
            }

            $this->fetchtype = $tmp;

            return FALSE;
        }
        /**
         * insert() is used to create and execute an SQL INSERT statment
         *
         * @todo Need to work out how much of a good idea it is to use prepared statements here
         * @todo does INSERT IGNORE exist for engines other than MySQL?
         * @access public
         * @return mixed the id of the inserted record on success, FALSE on failure
         * @param string $table either a single table name or a complete SQL statement
         * @param array $data an array of key/value pairs of data to insert
         * @param bool $ignore Optional param, whether to use INSERT IGNORE. Defaults to FALSE
         * @param bool $useprepared Optional param, turn on/off prepared statements for this query. if not specified the current connection setting will be used
         */
        public function insert($table, $data, $ignore=FALSE, $useprepared=NULL) {
            $this->_useprepared = ($useprepared !== NULL ? $useprepared : $this->useprepared) && $this->canUsePrepared($data);

            $SQL = "INSERT " . ($ignore ? "IGNORE " : "") . "INTO " . $this->quotename($table)
                 . " (" . implode(", ", array_map(array($this, 'quotename'), array_keys($data))) . ")"
                 . " VALUES (" . ($this->_useprepared ? ':' . implode(", :", array_keys($data)) : implode(", ", $data)) . ")";

            if ($this->stmtexec($SQL, $data) !== FALSE) {
                return $this->lastInsertID();
            }

            return FALSE;
        }

        /**
         * update() is used to create and execute an SQL UPDATE statement
         *
         * @todo investigate issues here with having the same key in data and criteria for a prepared statement
         * @access public
         * @return mixed the number of affected records on success or FALSE on failure
         * @param string $table either a single table name or a complete SQL statement
         * @param array $data an array of key/value pairs of data to insert
         * @param array $criteria Optional param, an array of key/value pairs to be used in the WHERE clause
         * @param bool $useprepared Optional param, turn on/off prepared statements for this query. if not specified the current connection setting will be used
         */
        public function update($table, $data, $criteria=NULL, $useprepared=NULL) {
            $this->_useprepared = ($useprepared !== NULL ? $useprepared : $this->useprepared) && $this->canUsePrepared($data);

            $SQL = "UPDATE " . $this->quotename($table) . " SET ";
            $sqldata = '';
            foreach ($data as $key => $val) {
                if (strlen($sqldata)) $sqldata.= ", ";
                $sqldata.= $this->quotename($key) . " = " . ($this->_useprepared ? ":{$key}" : $val);
            }
            $SQL.= $sqldata;
            if ($criteria !== NULL) $SQL.= " " . $this->buildwhere($criteria);

            $tmp = $data + (array)$criteria;
            if ($stmt =  $this->stmtexec($SQL, $tmp)) {
                return ($stmt instanceof PDOStatement ? $stmt->rowCount() : $stmt);
            }

            return $stmt;
        }

        public function replace($table, $data, $useprepared=NULL) {
            $this->_useprepared = ($useprepared !== NULL ? $useprepared : $this->useprepared) && $this->canUsePrepared($data);

            $SQL = "REPLACE INTO " . $this->quotename($table)
                 . " (" . implode(", ", array_map(array($this, 'quotename'), array_keys($data))) . ")"
                 . " VALUES (" . ($this->_useprepared ? ':' . implode(", :", array_keys($data)) : implode(", ", $data)) . ")";

            if ($this->stmtexec($SQL, $data) !== FALSE) {
                return $this->lastInsertID();
            }

            return FALSE;
        }
        /**
         * delete() is used to create and execute an SQL DELETE statement
         *
         * @access public
         * @return mixed the number of affected records on success or FALSE on failure
         * @param string $table either a single table name or a complete SQL statement
         * @param array $criteria Optional param, an array of key/value pairs to be used in the WHERE clause
         * @param bool $useprepared Optional param, turn on/off prepared statements for this query. if not specified the current connection setting will be used
         */
        public function delete($table, $criteria=NULL, $useprepared=NULL) {
            $this->_useprepared = ($useprepared !== NULL ? $useprepared : $this->useprepared) && $this->canUsePrepared($criteria);

            $SQL = "DELETE FROM " . $this->quotename($table);
            if ($criteria !== NULL) {
                $SQL.= " " . $this->buildwhere($criteria);
            } else if ($this->safemode) {
                trigger_error('Database::delete() - delete method called with no criteria', E_USER_ERROR);
            }

            return ($this->safemode && $criteria === NULL ? FALSE : $this->stmtexec($SQL, $criteria));
        }

        /**
         * cleanup() is an internal method used to close/destroy a PDOStatement object once it has been finished with
         *
         * @access protected
         * @return void
         * @param PDOStatement $stmt the statement to clean up
         */
        protected function cleanup($stmt) {
            $stmt->closeCursor();
            if (!$this->_useprepared) {
                $stmt = NULL;
            }
        }

        /**
         * Returns the number of rows in the previously executed SQL statement
         *
         * This method uses the MySQL FOUND_ROWS() function so the previously executed
         * SQL statement must have the SQL_CALC_FOUND_ROWS option set for this to work.
         * This returns the number of records ignoring any LIMIT clause
         *
         * @access public
         * @return mixed The number of rows found on success or NULL on failure
         */
        public function foundrows() {
            if ($row = $this->get("SELECT FOUND_ROWS() AS Rows", NULL, FALSE)) {
                return $row->Rows;
            }

            return NULL;
        }

        /**
         * getColumnMeta() retrieves column meta information for a column based on SQL
         *
         * @TODO Should this be passed SQL or table and criteria?
         * @see http://www.php.net/pdostatement.getcolumnmeta
         * @access public
         * @return mixed the column meta information on success or FALSE on failure
         * @param string $SQL the SQL statement to get meta information for
         * @param int $column the column offset
         */
        public function getColumnMeta($SQL, $column) {
            if ($stmt = $this->getstmt($SQL)) {
                return $stmt->getColumnMeta($column);
            }

            return FALSE;
        }
    }
