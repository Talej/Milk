<?php

    class MilkConfig {
        protected $settings = array();

        public function set($key, $value, $force=TRUE) {
            if ($force || !isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }

        public function get($key) {
            if (isset($this->settings[$key])) {
                return $this->settings[$key];
            }

            return NULL;
        }
        
        public function define() {
            foreach ($this->settings as $key => $val) {
                define('CFG_' . strtoupper($key), $val);
            }
        }
    }
