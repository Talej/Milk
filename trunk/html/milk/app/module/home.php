<?php

    class Home_MilkModule extends MilkModule {

        public function act_index() {
            $t = $this->newControl('Template');

            $t->add('Text', 'This is a text module in Milk');

            $this->addControl($t);
        }
    }
