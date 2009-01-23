<?php

    class standard extends MilkTheme {

        public function Template($ctrl) {
            MilkLauncher::load($ctrl->file);
        }
    }
