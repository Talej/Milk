<?php

    class Home extends MilkModule {

        public function index() {
            $t = $this->newControl('Template');

            $this->addControl($t);
        }
    }
