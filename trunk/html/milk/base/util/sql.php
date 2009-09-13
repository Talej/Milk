<?php

    /**
     * This library provides functionality to help build error free, well structured SQL statements
     *
     * $Id$
     *
     * @package core
     * @author Michael Little <michael@fliquidstudios.com>
     * @version 1.0
     */

    /**
     * The SQLFactory class is the main class provided for support in building SQL
     *
     * This class should generally be extended to provide "table" (or concept) specific
     * SELECT statement construction. The SQLFactory::toString function should be overloaded
     * in any extending class
     *
     * @package core
     */
    class SQLFactory
    {
        public $contexts        = array();
        public $indent          = 0;
        protected $options      = array();
        protected $cols         = array();
        protected $join         = array();
        protected $where        = array();
        protected $groupby      = array();
        protected $having       = array();
        protected $orderby      = array();
        protected $extracols    = array();
        protected $extrajoin    = array();
        protected $extrawhere   = array();
        protected $extragroupby = array();
        protected $extrahaving  = array();
        protected $extraorderby = array();
        protected $limit        = array();
        protected $lastopwhere  = array();
        protected $lastophaving = array();

        function __construct() {
            if (!in_array(NULL, $this->contexts)) $this->contexts[] = NULL;
            foreach ($this->contexts as $context) $this->addContext($context);
        }

        /**
         * addContext() is used to prepare all of the internal properties of this class for a new context.
         *
         * This method is called in the constructor and should not need
         * to be called after that unless adding a new context
         *
         * @access public
         * @return void
         * @param string $context the context to add
         */
        public function addContext($context) {
            $props = array(
                'options', 'cols', 'join', 'where', 'groupby', 'having',
                'orderby', 'extracols', 'extrajoin', 'extrawhere', 'extragroupby',
                'extrahaving', 'extraorderby', 'limit', 'lastopwhere', 'lastophaving'
            );

            if (!in_array($context, $this->contexts)) $this->contexts[] = $context;
            foreach ($props as $prop) {
                if (!is_array($this->{$prop})) $this->{$prop} = array();
                if (!isset($this->{$prop}[$context])) $this->{$prop}[$context] = '';
            }
        }

        /**
         * option() should be used to add any options to the SQL after the SELECT keyword.
         *
         * For example ALL, DISTINCT, STRAIGHT_JOIN, SQL_NO_CACHE, SQL_CALC_FOUND_ROWS etc
         *
         * @access public
         * @return bool TRUE on success, FALSE on failure
         * @param string $option the option to add
         * @param string $context Optional param, the context in which to add. Used to sub-queries. Defaults to NULL
         */
        public function option($option, $context=NULL) {
            if (isset($this->options[$context])) {
                if ($this->options[$context] != '') $this->options[$context].= ' ';
                $this->options[$context].= $option;

                return TRUE;
            } else {
                trigger_error(sprintf('SQLFactory::option() - Context %s does not exist', $context), E_USER_ERROR);
            }

            return FALSE;
        }

        /**
         * col() should be used to add a column to the SQL
         *
         * @access public
         * @return bool TRUE on success, FALSE on failure
         * @param string $col the column name to add
         * @param string $context Optional param, the context in which to add. Used to sub-queries. Defaults to NULL
         */
        public function col($col, $context=NULL) {
            if (isset($this->cols[$context]) && isset($this->extracols[$context])) {
                if ($this->cols[$context] != '') $this->cols[$context].= ', ';
                $this->cols[$context].= $col;
                $this->extracols[$context].= ', ' . $col;

                return TRUE;
            } else {
                trigger_error(sprintf('SQLFactory::col() - Context %s does not exist', $context), E_USER_ERROR);
            }

            return FALSE;
        }

        /**
         * join() should be used to join an addition table on to the SQL
         *
         * @access public
         * @return bool TRUE on success, FALSE on failure
         * @param string $join the JOIN statement
         * @param string $context Optional param, the context in which to add. Used to sub-queries. Defaults to NULL
         */
        public function join($join, $context=NULL) {
            if (isset($this->join[$context]) && isset($this->extrajoin[$context])) {
                if ($this->join[$context] != '') $this->join[$context].= "\n       " . str_repeat(' ', $this->indent);
                $this->join[$context].= $join;
                $this->extrajoin[$context].= "\n       " . str_repeat(' ', $this->indent);
                $this->extrajoin[$context].= $join;

                return TRUE;
            } else {
                trigger_error(sprintf('SQLFactory::join() - Context %s does not exist', $context), E_USER_ERROR);
            }

            return FALSE;
        }

        /**
         * where() should be used to add criteria to the SQL statements WHERE clause
         *
         * @access public
         * @return bool TRUE on success, FALSE on failure
         * @param string $where the where criteria
         * @param string $op the operator (Either AND or OR)
         * @param string $context Optional param, the context in which to add. Used to sub-queries. Defaults to NULL
         */
        public function where($where, $op='AND', $context=NULL) {
            if (isset($this->where[$context]) && isset($this->extrawhere[$context])) {
                if ($this->where[$context] == '') {
                    $this->where[$context].= 'WHERE ' . $where;
                } else {
                    if (strtoupper($op) != strtoupper($this->lastopwhere[$context])) {
                        $this->where[$context].= '(' . $this->where[$context] . ')';
                    }
                    if (strlen($this->where[$context]) > 0) $this->where[$context].= ' ';
                    $this->where[$context].= $op . ' ' . $where;
                }

                if ($this->extrawhere[$context] != '' && strtoupper($op) != strtoupper($this->lastopwhere[$context])) {
                    $this->extrawhere[$context].= '(' . $this->extrawhere[$context] . ')';
                }

                if (strlen($this->extrawhere[$context])) $this->extrawhere[$context].= ' ';
                $this->extrawhere[$context].= $op . ' ' . $where;
                $this->lastopwhere[$context] = $op;

                return TRUE;
            } else {
                trigger_error(sprintf('SQLFactory::where() - Context %s does not exist', $context), E_USER_ERROR);
            }

            return FALSE;
        }

        /**
         * groupby() should be used to add a column to the SQL statements GROUP BY clause
         *
         * @access public
         * @return bool TRUE on success, FALSE on failure
         * @param string $col the column name to add
         * @param string $context Optional param, the context in which to add. Used to sub-queries. Defaults to NULL
         */
        public function groupby($col, $context=NULL) {
            if (isset($this->groupby[$context]) && isset($this->extragroupby[$context])) {
                $this->groupby[$context].= ($this->groupby[$context] != '' ? ", " : 'GROUP BY ');
                $this->groupby[$context].= $col;
                $this->extragroupby[$context].= ", " . $col;

                return TRUE;
            } else {
                trigger_error(sprintf('SQLFactory::groupby() - Context %s does not exist', $context), E_USER_ERROR);
            }

            return FALSE;
        }

        /**
         * having() adds a HAVING clause to the SQL statement
         *
         * @access public
         * @return bool TRUE on success, FALSE on failure
         * @param string $having the criteria to add to the HAVING clause
         * @param string $op the operator (Either AND or OR)
         * @param string $context Optional param, the context in which to add. Used to sub-queries. Defaults to NULL
         */
        public function having($having, $op='AND', $context=NULL) {
            if (isset($this->having[$context]) && isset($this->extrahaving[$context])) {
                if ($this->having[$context] == '') {
                    $this->having[$context].= 'HAVING ' . $having;
                } else {
                    if (strtoupper($op) != strtoupper($this->lastophaving[$context])) {
                        $this->having[$context].= '(' . $this->having[$context] . ')';
                    }
                    $this->having[$context].= $op . ' ' . $having;
                }

                if ($this->extrahaving[$context] != '' && strtoupper($op) != strtoupper($this->lastophaving[$context])) {
                    $this->extrahaving[$context].= '(' . $this->extrahaving[$context] . ')';
                }
                $this->extrahaving[$context].= $op . ' ' . $having;
                $this->lastophaving[$context] = $op;

                return TRUE;
            } else {
                trigger_error(sprintf('SQLFactory::having() - Context %s does not exist', $context), E_USER_ERROR);
            }

            return FALSE;
        }

        /**
         * order() should be used to add a column to the SQL statements ORDER BY clause
         *
         * @access public
         * @return bool TRUE on success, FALSE on failure
         * @param string $col the column name to add
         * @param string $direction the sort direction to use (Either ASC or DESC)
         * @param string $context Optional param, the context in which to add. Used to sub-queries. Defaults to NULL
         */
        public function orderby($col, $direction='ASC', $context=NULL) {
            if (isset($this->orderby[$context]) && isset($this->extraorderby[$context])) {
                $this->orderby[$context].= ($this->orderby[$context] != '' ? ", " : 'ORDER BY ');
                $this->orderby[$context].= $col;
                $this->orderby[$context].= ' ' . $direction;
                $this->extraorderby[$context].= ", " . $col;

                return TRUE;
            } else {
                trigger_error(sprintf('SQLFactory::orderby() - Context %s does not exist', $context), E_USER_ERROR);
            }

            return FALSE;
        }

        /**
         * limit() sets a LIMIT on the SELECT statement
         *
         * @access public
         * @return bool TRUE on success, FALSE on failure
         * @param int $rowcount the number of rows to limit the results to
         * @param int $offset Optional param, the offset of the first row to return. Defaults to 0 (e.g. the first row)
         * @param string $context Optional param, the context in which to add. Used to sub-queries. Defaults to NULL
         */
        public function limit($rowcount, $offset=0, $context=NULL) {
            if (isset($this->limit[$context])) {
                $this->limit[$context] = 'LIMIT ' . $offset . ', ' . $rowcount;

                return TRUE;
            } else {
                trigger_error(sprintf('SQLFactory::limit() - Context %s does not exist', $context), E_USER_ERROR);
            }

            return FALSE;
        }

        /**
         * toString() generates the final SQL output
         *
         * @access public
         * @return string the generates SQL output
         */
        public function toString() {
            $SQL = '';
            if ($this->table !== NULL) {
                $SQL = "SELECT {$this->options[NULL]} {$this->cols[NULL]}\n"
                     . "  FROM {$this->table} {$this->extrajoin[NULL]}\n"
                     . " {$this->where[NULL]}\n"
                     . " {$this->groupby[NULL]} {$this->having[NULL]}\n"
                     . " {$this->orderby[NULL]} {$this->limit[NULL]}\n";
            }

            return $SQL;
        }
    }
