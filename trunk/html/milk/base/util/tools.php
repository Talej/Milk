<?php

    class MilkTools {

        static public function if_null($arg1, $arg2) {
            $args = func_get_args();
            foreach ($args as $arg) {
                if ($arg !== NULL) return $arg;
            }

            return NULL;
        }

        static public function if_def($const, $default) {
            assert('is_string($const)');

            if (define($const)) return constant($const);

            return $default;
        }

        static public function mkpath($file) {
            return MilkLauncher::mkpath(func_get_args());
        }
    }
