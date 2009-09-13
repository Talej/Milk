<?php

    define('MILK_ACTION', 'act');
    define('MILK_ACTION_RELOAD',   'reload');
    define('MILK_ACTION_REFRESH', 'refresh');
    define('MILK_ACTION_BACK',       'back');

    define('MILK_SLOT_SAMEWIN',      '_self');
    define('MILK_SLOT_NEWWIN',      '_blank');
    define('MILK_SLOT_CHILDWIN',    '_child');
    define('MILK_SLOT_MODALWIN',    '_modal');
    define('MILK_SLOT_AJAX',         '_ajax');
    define('MILK_SLOT_LAUNCHER', '_launcher');

    class MilkConnection extends MilkFrameWork {
        public $source;
        public $signal;
        public $dest;
        public $slot;
        public $args;

        function __construct($source, $signal, $dest, $slot, $args=NULL) {
            assert('$source instanceof MilkControl');

            $this->source = $source;
            $this->signal = $signal;
            $this->dest   = $dest;
            $this->slot   = $slot;
            $this->args   = $args;
        }

        public function getSlotControl() {
            if ($this->dest instanceof MilkControl) {
                return implode('-', $this->dest->id);
            } else {
                return $this->dest;
            }
        }
    }
