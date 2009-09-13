<?php

    MilkLauncher::load(MILK_BASE_DIR, 'util', 'form.php');

    class default_MilkTheme extends MilkTheme {

        public function init() {
            $this->includecss('/milk/base/theme/default/css/style.css');
            if (($ua = $this->mod->getProp('ua')) && $ua->isApp('MSIE', '7.0')) {
                $this->includecss('/milk/base/theme/default/css/ie7.css');
            }

            $this->includejs('/milk/base/theme/default/js/flqevent.js');
            $this->includejs('/milk/base/theme/default/js/flqbase.js');
            $this->includejs('/milk/base/theme/default/js/flqurl.js');
            $this->includejs('/milk/base/theme/default/js/default.js');
        }

        protected function imgSrc() {
            return '/milk/base/themes/default/img/';
        }

        protected function flexratio($ctrl) {
            $totalflex = $noflex = 0;
            for ($i = 0; $i < count($ctrl->controls); $i++) {
                if ($ctrl->controls[$i]->flex > 0) {
                    $totalflex += $ctrl->controls[$i]->flex;
                } else {
                    $noflex++;
                }
            }
            if ($totalflex > 0) return (100-$noflex)/$totalflex;
            return 0;
        }

        protected function flexsize($ratio, $ctrl) {
            if ($ratio == 0) return '';
            if ($ctrl->flex > 0) return round($ctrl->flex*$ratio);
            return 1;
        }

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
            $class = 'text';
            if ($ctrl->hasAnyConnected()) {
                $class.= ' text-link';
                $jsconn = TRUE;
                if (($conns = $ctrl->getConnections('click')) && count($conns) == 1) {
                    $c = $conns[0];
                    if (
                        @$c->args['send'] == FALSE &&
                        @$c->args['nohistory'] == TRUE &&
                        @$c->args['noact'] == TRUE &&
                        isset($c->args['modurl']) &&
                        is_scalar($c->dest)
                    ) {
                        $url = new FLQURL($c->args['modurl']);
                        foreach ($c->args as $key => $val) {
                            if (in_array($key, array('send', 'nohistory', 'noact', 'modurl'))) continue;
                            $url->addArgument($key, $val);
                        }
                        $tag = 'a href="' . $this->entitise($url->toString()) . '" target="' . $c->dest . '"';
                        $jsconn = FALSE;
                    }
                }

                if ($jsconn) {
                    $this->jsControl($ctrl);
                    $tag = 'a href="#"';
                }
            } else {
                $tag = 'div';
            }

            $style = '';
            if ($ctrl->nowrap) $style.= 'white-space:nowrap;';
            if ($style != '') $style = ' style="' . $style . '"';

            $str = '<' . $tag . ' id="' . $this->entitise($this->getID($ctrl)) . '" class="' . $class . '"' . $style . '>' . $this->entitise($ctrl->value) . '</' . $tag . '>';

            $this->put('xhtml', $str);
        }

        public function Label($ctrl) {
            $class = 'label';
            if ($ctrl->hasAnyConnected()) {
                $class.= ' label-link';
                $jsconn = TRUE;
                if (($conns = $ctrl->getConnections('click')) && count($conns) == 1) {
                    $c = $conns[0];
                    if (
                        @$c->args['send'] == FALSE &&
                        @$c->args['nohistory'] == TRUE &&
                        @$c->args['noact'] == TRUE &&
                        isset($c->args['modurl']) &&
                        is_scalar($c->dest)
                    ) {
                        $url = new FLQURL($c->args['modurl']);
                        foreach ($c->args as $key => $val) {
                            if (in_array($key, array('send', 'nohistory', 'noact', 'modurl'))) continue;
                            $url->addArgument($key, $val);
                        }
                        $tag = 'a href="' . $this->entitise($url->toString()) . '" target="' . $c->dest . '"';
                        $jsconn = FALSE;
                    }
                }

                if ($jsconn) {
                    $this->jsControl($ctrl);
                    $tag = 'a href="#"';
                }
            } else {
                $tag = 'div';
            }
            if ($ctrl->wrap) $class.= ' label-wrap';

            $str = '<' . $tag . ' id="' . $this->entitise($this->getID($ctrl)) . '" class="' . $class . '">' . $this->entitise($ctrl->value) . '</' . $tag . '>';

            $this->put('xhtml', $str);
        }

        public function Heading($ctrl) {
            $class = 'heading';
            if ($ctrl->hasAnyConnected()) {
                $class.= ' heading-link';
                $jsconn = TRUE;
                if (($conns = $ctrl->getConnections('click')) && count($conns) == 1) {
                    $c = $conns[0];
                    if (
                        @$c->args['send'] == FALSE &&
                        @$c->args['nohistory'] == TRUE &&
                        @$c->args['noact'] == TRUE &&
                        isset($c->args['modurl']) &&
                        is_scalar($c->dest)
                    ) {
                        $url = new FLQURL($c->args['modurl']);
                        foreach ($c->args as $key => $val) {
                            if (in_array($key, array('send', 'nohistory', 'noact', 'modurl'))) continue;
                            $url->addArgument($key, $val);
                        }
                        $atag = '<a href="' . $this->entitise($url->toString()) . '" target="' . $c->dest . '">';
                        $jsconn = FALSE;
                    }
                }

                if ($jsconn) {
                    $this->jsControl($ctrl);
                }
            }
            if ($ctrl->wrap) $class.= ' heading-wrap';

            $tag = 'h' . ((int)$ctrl->style > 0 && (int)$ctrl->style <= 5 ? $ctrl->style : 1);
            $str = '<' . $tag . ' id="' . $this->entitise($this->getID($ctrl)) . '" class="' . $class . '">'
                 . (isset($atag) ? $atag : '')
                 . $this->entitise($ctrl->value)
                 . (isset($atag) ? '</a>' : '')
                 . '</' . $tag . '>';

            $this->put('xhtml', $str);
        }

        public function HTML($ctrl) {
            $this->put('xhtml', $ctrl->value);
        }

        public function Image($ctrl) {
            $class = 'image';
            if ($ctrl->hasAnyConnected()) {
                $this->jsControl($ctrl);
                $class.= ' image-link';
            }
            if ($ctrl->noborder) $class.= ' image-noborder';

            $sprite = (is_numeric($ctrl->x) && is_numeric($ctrl->y) ? TRUE : FALSE);

            $str = '<div id="' . $this->entitise($this->getID($ctrl)) . '" class="' . $class . '">'
                 . '<img src="' . $this->entitise($sprite ? '/milk/base/theme/standard/img/1px.png' : $ctrl->src) . '" '
                 . ($ctrl->width > 0 ? ' width="' . $ctrl->width . '" ' : '')
                 . ($ctrl->height > 0 ? ' height="' . $ctrl->height . '" ' : '')
                 . ($ctrl->alt ? ' alt="' . $this->entitise($ctrl->alt) . '" title="' . $this->entitise($ctrl->alt) . '" ' : '')
                 . ($sprite ? ' style="background:url(' . $this->entitise($ctrl->src) . ') ' . $ctrl->x . 'px ' . $ctrl->y . 'px no-repeat" ' : '')
                 . '/>'
                 . '</div>';

            $this->put('xhtml', $str);
        }

        public function Spacer($ctrl) {
            $styles = array();
            if ($ctrl->width > 0) $styles[] = 'width:' . $ctrl->width . 'px';
            if ($ctrl->height > 0) $styles[] = 'height:' . $ctrl->height . 'px';
            $style = (!empty($styles) ? ' style="' . implode(';', $styles) . '"' : '');

            $str = '<div class="spacer"' . $style . '></div>';
            
            $this->put('xhtml', $str);
        }

        public function Terminator($ctrl) {
            $jsprops = array(
                'reload' => MilkTools::jsEncode($ctrl->reload, JSTYPE_BOOL),
                'url'    => MilkTools::jsEncode($ctrl->url, JSTYPE_STRING),
                'close'  => MilkTools::jsEncode($ctrl->close, JSTYPE_BOOL)
            );

            $this->jsControl($ctrl, $jsprops);

            print $this->xhtmlDoc();
        }

        public function VerticalBox($ctrl) {
            if ($ctrl->fitHeight) {
                $jsprops = array(
                    'fitHeight' => MilkTools::jsEncode($ctrl->fitHeight, JSTYPE_BOOL)
                );

                $this->jsControl($ctrl, $jsprops);
            }

            $str = '<div class="verticalbox" id="' . $this->entitise($this->getID($ctrl)) . '">';
            for ($i=0; $i < count($ctrl->controls); $i++) {
                $this->deliver($ctrl->controls[$i]);

                $str.= '<div class="verticalbox-cell">' . $this->get('xhtml') . '</div>';
            }
            $str.= '</div>';

            $this->put('xhtml', $str);
        }

        public function HorizontalBox($ctrl) {
            $fr = $this->flexratio($ctrl);

            $str = '<div class="horizontalbox"><table class="hbox-table"><tr>';
            for ($i=0; $i < count($ctrl->controls); $i++) {
                $this->deliver($ctrl->controls[$i]);

                $style = '';
                $width = $this->flexsize($fr, $ctrl->controls[$i]);
                if ($width > 0) $style.= ' style="width:' . $width . '%;"';

                $str.= '<td' . $style . '>' . $this->get('xhtml') . '</td>';
            }
            $str.= '</tr></table></div>';

            $this->put('xhtml', $str);
        }

        public function Template($ctrl) {
            $this->jsControl($ctrl);

            foreach ($ctrl->css as $file) {
                $this->includecss($file);
            }

            $str = 'Milk.history = ' . MilkTools::jsEncode($ctrl->module->history, JSTYPE_ARRAY) . '; '
                 . (!empty($ctrl->module->errors) ? 'Milk.notify(Milk.NOTIFY_ERROR, ' . MilkTools::jsEncode($ctrl->module->errors, JSTYPE_ARRAY) . ')' : '') . ' ';

            $this->put('loadjs', $str);

            $this->deliverChildren($ctrl);
            include($ctrl->file);
        }

        public function Table($ctrl) {
            $fr = $totalflex = $noflex = 0;
            if (!empty($ctrl->controls)) {
                for ($i = 0; $i < count($ctrl->controls[0]); $i++) {
                    if ($ctrl->controls[0][$i]->flex > 0) {
                        $totalflex += $ctrl->controls[0][$i]->flex;
                    } else {
                        $noflex++;
                    }
                }
            }
            if ($totalflex > 0) $fr = (100-$noflex)/$totalflex;

            $str = '<div class="table"><table>';
            for ($i=0; $i < count($ctrl->controls); $i++) {
                if (!empty($ctrl->controls[$i])) {
                    $str.= '<tr>';
                    $cols = count($ctrl->controls[$i])-1;
                    for ($o=0; $o <= $cols; $o++) {
                        $this->deliver($ctrl->controls[$i][$o]);


                        $style = '';
                        $width = $this->flexsize($fr, $ctrl->controls[$i][$o]);
                        if ($width > 0) $style.= ' style="width:' . $width . '%;"';
                        if ($cols == $o && $cols < $ctrl->maxcols) $style.= ' colspan="' . ($ctrl->maxcols-$cols) . '"';

                        $str.= '<td' . $style . '>' . $this->get('xhtml') . '</td>';
                    }
                    $str.= '</tr>';
                }
            }
            $str.= '</table></div>';

            $this->put('xhtml', $str);
        }

        public function Tabs($ctrl) {
            $jsprops = array(
                'tab' => MilkTools::jsEncode($ctrl->tab)
            );

            $this->jsControl($ctrl, $jsprops);

            $str = '<div class="tabs" id="' . $this->entitise($this->getID($ctrl)) . '">'
                 . '<div class="tablabels">';

            for ($i=0; $i < count($ctrl->tabs); $i++) {
                $class = 'tablabel';
                if ($i == $ctrl->tab) $class.= ' tablabel-selected';
                $str.= '<div class="' . $class . '" id="' . $this->entitise($this->getID($ctrl)) . '-' . $i . '-label">' . $this->entitise($ctrl->tabs[$i]) . '</div>';
            }

            $str.= '</div><div class="tabbodies">';

            for ($i=0; $i < count($ctrl->controls); $i++) {
                $this->deliver($ctrl->controls[$i]);

                $class = 'tab';
                if ($i == $ctrl->tab) $class.= ' tab-selected';

                $str.= '<div class="' . $class . '" id="' . $this->entitise($this->getID($ctrl)) . '-' . $i . '-tab">' . $this->get('xhtml') . '</div>';
            }
            $str.= '</div></div>';

            $this->put('xhtml', $str);
        }

        public function DataGrid($ctrl) {
            $jsprops = array(
                'perpage'   => MilkTools::jsEncode($ctrl->perpage, JSTYPE_INT),
                'totalrows' => MilkTools::jsEncode($ctrl->totalrows, JSTYPE_INT),
                'offset'    => MilkTools::jsEncode($ctrl->offset, JSTYPE_INT),
            );
            $this->jsControl($ctrl, $jsprops);

            $str = '<div class="datagrid"><table class="datagrid-table" id="' . $this->entitise($this->getID($ctrl)) . '"><tr class="dg-row">';
            for ($i=0; $i < $ctrl->numcols; $i++) {
                $str.= '<th>' . $this->entitise(MilkTools::ifNull($ctrl->getProp($i, 'header'), 'Col' . ($i+1))) . '</th>';
            }
            $str.= '</tr>';
            $c = count($ctrl->data);
            for ($i=0; $i < $c; $i++) {
                $class = 'dg-row';
                $class.= ($i%2==0 ? '' : ' datagrid-alt');
                $args = (isset($ctrl->data[$i][1]) ? $ctrl->data[$i][1] : array());
                $str.= '<tr actarg="' . $this->entitise(FLQURL::argsToString($args)) . '" class="' . $class . '">';
                for ($o=0; $o < $ctrl->numcols; $o++) {
                    $str.= '<td class="dg-cell">' . ($ctrl->data[$i][0][$o] != NULL ? $this->entitise($ctrl->data[$i][0][$o]) : '&nbsp;') . '</td>';
                }
                $str.= '</tr>';
            }
            if ($c == 0) {
                $str.= '<tfoot><tr><td class-"dg-cell" colspan="' . $ctrl->numcols . '"><em>There\'s currently no data to display.</em></td></tr></tfoot>';
            }

            $str.= '</table></div>';

            $this->put('xhtml', $str);
        }

        public function Button($ctrl) {
            if ($ctrl->hasConnected('click')) {
                $this->jsControl($ctrl);
            } else {
                $this->disabled = TRUE;
            }

            $img = '';
            if ($ctrl->src != '') $img = '<img src="' . (substr($ctrl->src, 0, 1) == '/' ? '' : $this->imgSrc()) . $ctrl->src . '" alt="' . $this->entitise($ctrl->value) . '" />';

            $href = '';
            $class = 'button';
            if ($ctrl->disabled) {
                $class.= ' button-disabled';
            } else {
                 $href = ' href="#"';
            }
            $str = '<a id="' . $this->entitise($this->getID($ctrl)) . '" class="' . $class . '"' . $href . '>' . $img . $this->entitise($ctrl->value) . '</a>';

            $this->put('xhtml', $str);
        }

        public function TextBox($ctrl) {
            $jsprops = array(
                'value'    => MilkTools::jsEncode($ctrl->value, JSTYPE_STRING),
                'reqValue' => MilkTools::jsEncode($ctrl->reqValue, JSTYPE_STRING)
            );

            $this->jsControl($ctrl, $jsprops);

            $props = array();
            if ($ctrl->disabled) $props['disabled'] = 1;
            if ($ctrl->readonly) $props['readonly'] = 1;
            if ($ctrl->maxlen)   $props['maxlength'] = $ctrl->maxlen;
//             $props['autocomplete'] = 'off';

            $class = 'textbox';
            if ($ctrl->getAttrib(DD_ATTR_REQUIRED)) $class.= ' textbox-required';

            $str = '<div id="' . $this->entitise($this->getID($ctrl)) . '" class="' . $class . '">';
            if ($ctrl->getAttrib(DD_ATTR_MULTILINE)) {
                $str.= FLQForm::textarea($this->getID($ctrl, ''), $props, $ctrl->reqValue);
            } else {
                $str.= FLQForm::textbox($this->getID($ctrl, ''), $props, $ctrl->reqValue);
            }
            $str.= '</div>';

            $this->put('xhtml', $str);
        }

        public function PasswordBox($ctrl) {
            $jsprops = array(
                'value'    => MilkTools::jsEncode($ctrl->value, JSTYPE_STRING),
                'reqValue' => MilkTools::jsEncode($ctrl->reqValue, JSTYPE_STRING)
            );
            $this->jsControl($ctrl, $jsprops);

            $props = array();
            if ($ctrl->disabled) $props['disabled'] = 1;
            if ($ctrl->readonly) $props['readonly'] = 1;

            $class = 'passwordbox';
            if ($ctrl->getAttrib(DD_ATTR_REQUIRED)) $class.= ' passwordbox-required';

            $str = '<div id="' . $this->entitise($this->getID($ctrl)) . '" class="' . $class . '">'
                 . FLQForm::password($this->getID($ctrl, ''), $props, $ctrl->reqValue)
                 . '</div>';

            $this->put('xhtml', $str);
        }

        public function ListBox($ctrl) {
            $jsprops = array(
                'value'    => MilkTools::jsEncode($ctrl->value),
                'reqValue' => MilkTools::jsEncode($ctrl->reqValue),
                'min'      => MilkTools::jsEncode($ctrl->minsel),
                'max'      => MilkTools::jsEncode($ctrl->maxsel)
            );

            $this->jsControl($ctrl, $jsprops);

            $props = array();
            if ($ctrl->disabled) $props['disabled'] = 1;
            if ($ctrl->minsel > 1 || $ctrl->maxsel > 1 | $ctrl->maxsel == NULL) $props['multiple'] = 1;

            $options = array();
            if (!$ctrl->getAttrib(DD_ATTR_REQUIRED) && !isset($ctrl->options[NULL]) && !isset($props['multiple'])) {
                $options[NULL] = '(None)';
            }
            $options = $options+$ctrl->options;

            $class = 'listbox';
            if ($ctrl->minsel > 1 || $ctrl->maxsel > 1) $class.= ' listbox-multiple';
            if ($ctrl->getAttrib(DD_ATTR_REQUIRED)) $class.= ' listbox-required';

            $str = '<div id="' . $this->entitise($this->getID($ctrl)) . '" class="' . $class . '">'
                 . FLQForm::dropbox($this->getID($ctrl, ''), $props, $options, $ctrl->reqValue)
                 . '</div>';

            $this->put('xhtml', $str);
        }

        public function BoolBox($ctrl) {
            $jsprops = array(
                'value'    => MilkTools::jsEncode($ctrl->value, JSTYPE_BOOL),
                'reqValue' => MilkTools::jsEncode($ctrl->reqValue, JSTYPE_BOOL)
            );

            $this->jsControl($ctrl, $jsprops);

            $str = '<div id="' . $this->entitise($this->getID($ctrl)) . '" class="boolbox">'
                 . FLQForm::checkbox($this->getID($ctrl, ''), NULL, $ctrl->reqValue)
                 . '</div>';

            $this->put('xhtml', $str);
        }

        public function ChooseBox($ctrl) {
            $jsprops = array(
                'value'    => MilkTools::jsEncode($ctrl->value),
                'reqValue' => MilkTools::jsEncode($ctrl->reqValue)
            );

            $this->jsControl($ctrl, $jsprops);

            $str = '<div id="' . $this->entitise($this->getID($ctrl)) . '" class="choosebox">'
                 . FLQForm::textbox($this->getID($ctrl, ''), NULL, $ctrl->reqValue)
                 . '<div class="choosebox-button"></div>'
                 . '</div>';

            $this->put('xhtml', $str);
        }

        public function DateBox($ctrl) {
            $jsprops = array(
                'value'    => MilkTools::jsEncode($ctrl->value),
                'reqValue' => MilkTools::jsEncode($ctrl->reqValue),
                'fmt'      => MilkTools::jsEncode(MilkTools::ifDef('CFG_DATETIME_FORMAT', '%d/%m/%Y'))
            );

            $this->jsControl($ctrl, $jsprops);

            $str = '<div id="' . $this->entitise($this->getID($ctrl)) . '" class="datebox">'
                 . FLQForm::textbox($this->getID($ctrl, ''), NULL, $ctrl->reqValue)
                 . '<div class="datebox-button"></div>'
                 . '</div>';

            $this->put('xhtml', $str);
        }

        public function DateTimeBox($ctrl) {
            $jsprops = array(
                'value'    => MilkTools::jsEncode($this->entitise($ctrl->value)),
                'reqValue' => MilkTools::jsEncode($this->entitise($ctrl->reqValue)),
                'fmt'      => MilkTools::jsEncode(MilkTools::ifDef('CFG_DATETIME_FORMAT', '%d/%m/%Y %H:%M:%S'))
            );

            $this->jsControl($ctrl, $jsprops);

            $str = '<div id="' . $this->entitise($this->getID($ctrl)) . '" class="datetimebox">'
                 . FLQForm::textbox($this->getID($ctrl, ''), NULL, $this->entitise($ctrl->reqValue))
                 . '<div class="datetimebox-button"></div>'
                 . '</div>';

            $this->put('xhtml', $str);
        }

        public function FileBox($ctrl) {
            $this->jsControl($ctrl);

            $str = '<div id="' . $this->entitise($this->getID($ctrl)) . '" class="filebox">';
            if ($ctrl->value) {
                $str.= var_export($ctrl->value, TRUE);
            } else {
                $str.= FLQForm::file($this->getID($ctrl, ''), NULL, $ctrl->reqValue);
            }
            $str.= '</div>';

            $this->put('xhtml', $str);
        }

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
