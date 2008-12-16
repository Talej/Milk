<?php

    class Text extends MilkControl {
        public $value;

        public function __construct($parent, $value) {
            parent::__construct($parent);
            $this->value = $value;
        }
    }

    class Label extends Text { }

    class HTML extends MilkControl { }

    class Image extends MilkControl { }

    class Terminator extends MilkControl { }

    class VerticalBox extends MilkControl { }

    /* Synonyms for the VerticalBox control */
    class VertBox extends VerticalBox { }
    class VBox extends VerticalBox { }
    class VertContainer extends VerticalBox { }
    class VertCont extends VerticalBox { }
    class VCont extends VerticalBox { }
    /* End Synonyms for the VerticalBox control */

    class HorizontalBox extends MilkControl { }

    /* Synonyms for the HorizontalBox control */
    class HorizBox extends HorizontalBox { }
    class HBox extends HorizontalBox { }
    class HorizContainer extends HorizontalBox { }
    class HorizCont extends HorizontalBox { }
    class HCont extends HorizontalBox { }

    class Table extends MilkControl { }

    class Tabs extends MilkControl { }

    class ListView extends MilkControl { }

    /* Form controls */
    class Button extends MilkControl { }

    abstract class Form extends MilkControl { }

    class TextBox extends Form { }

    class PasswordBox extends Form { }

    class ListBox extends Form { }

    class Boolean extends Form { }

    class Chooser extends Form { }
    /* End form controls */

    class XML extends MilkControl { }

    class CSV extends MilkControl { }
