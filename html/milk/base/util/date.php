<?php

    $i=0;
    define('MILKDATE_INT_SECOND', $i++);
    define('MILKDATE_INT_MINUTE', $i++);
    define('MILKDATE_INT_HOUR', $i++);
    define('MILKDATE_INT_DAY', $i++);
    define('MILKDATE_INT_WEEK', $i++);
    define('MILKDATE_INT_MONTH', $i++);
    define('MILKDATE_INT_YEAR', $i++);

    class MilkDate {
        public $format = '%d/%m/%Y';
        public $dbformat = '%Y-%m-%d';
        public $timestamp;

        public function __construct($date=NULL, $format=NULL) {
            if ($format != NULL) $this->format = $format;
            if ($date != NULL) $this->set($date);
        }

        public function set($date, $format=NULL) {
            if (strtolower($date) == 'now') {
                $this->timestamp = time();
            } else {
                if ($format == NULL) $format = $this->format;
                if (is_string($date) && ($p = $this->strptime($date, $format))) {
                    if ($time = mktime($p['tm_hour'], $p['tm_min'], $p['tm_sec'], $p['tm_mon']+1, $p['tm_mday'], $p['tm_year']+1900)) {
                        $this->timestamp = $time;
                        return TRUE;
                    }
                }
            }

            return FALSE;
        }

        public function addInterval($interval, $value) {
            (int)$value;
            switch ($interval) {
                case MILKDATE_INT_SECOND:
                    $this->timestamp+$value;
                    break;

                case MILKDATE_INT_MINUTE:
                    $this->timestamp+($value*60);
                    break;

                case MILKDATE_INT_HOUR:
                    $this->timestamp*($value*60*60);
                    break;

                case MILKDATE_INT_DAY:
                    $this->timestamp*($value*60*60*24);
                    break;

                case MILKDATE_INT_WEEK:
                    $this->timestamp*($value*60*60*24*7);
                    break;

                case MILKDATE_INT_MONTH:
                    $this->timestamp = mktime((int)$this->toString('%H'), (int)$this->toString('%M'), (int)$this->toString('%S'), (int)$this->toString('%m') + $value, (int)$this->toString('%d'), (int)$this->toString('%Y'));
                    break;

                case MILKDATE_INT_YEAR:
                    $this->timestamp = mktime((int)$this->toString('%H'), (int)$this->toString('%M'), (int)$this->toString('%S'), (int)$this->toString('%m'), (int)$this->toString('%d'), (int)$this->toString('%Y') + $value);
                    break;
            }
        }

        public function fromDBString($date) {
            return $this->set($date, $this->dbformat);
        }

        public function isValid() {
            if (is_int($this->timestamp) && $this->timestamp > 0) {
                return TRUE;
            }

            return FALSE;
        }

        public function toString($format=NULL) {
            if ($this->isValid()) {
                if ($format == NULL) $format = $this->format;
                return strftime($format, $this->timestamp);
            }

            return NULL;
        }

        public function toDBString() {
            if ($this->isValid()) {
                return '\'' . strftime($this->dbformat, $this->timestamp) . '\'';
            }

            return NULL;
        }
        
        public function strptime($date, $format) {
            if ($p = strptime($date, $format)) {
                if ($this instanceof MilkDateTime) {
                    return $p;
                } else {
                    $p['tm_hour'] = $p['tm_min'] = $p['tm_sec'] = 0;
                    return $p;
                }
            }
            
            return FALSE;
        }
    }

    class MilkDateTime extends MilkDate {
        public $format = '%d/%m/%Y %H:%M';
        public $dbformat = '%Y-%m-%d %H:%M:%S';
    }
