<?php

    class standard_MilkTheme extends MilkTheme {

        public function xhtmlDoc($title=NULL) {
            $str = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n"
                 . "     \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n"
                 . "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n"
                 . "<head>\n"
                 . "<title>" . $this->entitise($title) . "</title>\n"
                 . "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>\n"
                 . $this->includes()
                 . "</head>\n"
                 . "<body onload=\"load()\">" . $this->get('xhtml') . "</body>\n"
                 . "</html>";

            print $str;
        }

        public function Text($ctrl) {
            $str = '<div class="text">' . $this->entitise($ctrl->value) . '</div>';

            $this->put('xhtml', $str);
        }

        public function Label($ctrl) {
            $str = '<div class="label">' . $this->entitise($ctrl->value) . '</div>';

            $this->put('xhtml', $str);
        }

        public function HTML($ctrl) {
            $this->put($ctrl->value, $str);
        }

        public function Image($ctrl) {
            $sprite = (is_numeric($ctrl->x) && is_numeric($ctrl->y) ? TRUE : FALSE);

            $str = '<div class="image">'
                 . '<img src="' . $this->entitise($sprite ? '/milk/base/theme/standard/img/1px.png' : $ctrl->src) . '" '
                 . ($ctrl->width > 0 ? ' width="' . $ctrl->width . '" ' : '')
                 . ($ctrl->height > 0 ? ' height="' . $ctrl->height . '" ' : '')
                 . ($ctrl->alt ? ' alt="' . $this->entitise($ctrl->alt) . '" title="' . $this->entitise($ctrl->alt) . '" ' : '')
                 . ($sprite ? ' style="background:url(' . $this->entitise($ctrl->src) . ') ' . $ctrl->x . 'px ' . $ctrl->y . 'px no-repeat" ' : '')
                 . '/>'
                 . '</div>';

            $this->put('xhtml', $str);
        }

        public function Terminator($ctrl) { }

        public function VerticalBox($ctrl) {
            $str = '<div class="verticalbox"><table>';
            foreach ($ctrl->controls as $control) {
                $control->deliver();
                $str.= '<tr><td>' . $control->_theme->get('xhtml') . '</td></tr>';
            }
            $str.= '</table></div>';

            $this->put('xhtml', $str);
        }

        public function HorizontalBox($ctrl) {
            $str = '<div class="horizontalbox"><table><tr>';
            foreach ($ctrl->controls as $control) {
                $control->deliver();
                $str.= '<td>' . $control->_theme->get('xhtml') . '</td>';
            }
            $str.= '</tr></table></div>';

            $this->put('xhtml', $str);
        }

        public function Template($ctrl) {
            $this->deliverChildren($ctrl);
            include($ctrl->file);
        }

        public function Table($ctrl) {
            $str = '<div class="table"><table>';
            foreach ($ctrl->controls as $row) {
                $str.= '<tr>';
                foreach ($row as $col) {
                    $col->deliver();
                    $str.= '<td>' . $col->get('xhtml') . '</td>';
                }
                $str.= '</tr>';
            }
            $str.= '</table></div>';

            $this->put('xhtml', $str);
        }

        public function Tabs($ctrl) { }

        public function ListView($ctrl) { }

        public function Button($ctrl) { }

        public function TextBox($ctrl) { }

        public function PasswordBox($ctrl) { }

        public function ListBox($ctrl) { }

        public function Boolean($ctrl) { }

        public function ChoosBox($ctrl) { }

        public function DateBox($ctrl) { }

        public function DateTimeBox($ctrl) { }

        public function FileBox($ctrl) { }

        public function XML($ctrl) {
            if (!$ctrl->hasParent('XML')) {
                header('Content-type: text/xml; charset=utf-8');
                print '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
            }

            // TODO: attribs
            print '<' . $ctrl->entitise($ctrl->tag);
            if (count($ctrl->children) > 0) {
                print '>';
                $this->deliverChildren($ctrl);
                print '</' . $ctrl->entitise($ctrl->tag) . '>';
            } else if ($ctrl->value != '__XML_NOVALUE__') {
                print '>' . $ctrl->entitise($ctrl->value) . '</' . $ctrl->entitise($ctrl->tag) . '>';
            } else {
                print '/>';
            }
        }

        public function CSV($ctrl) {
            $ctrl->toFile();
        }
    }
