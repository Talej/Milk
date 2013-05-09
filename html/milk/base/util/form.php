<?php

    // Available input  types
    define('FLQFORM_TEXT',         'text');
    define('FLQFORM_CHECKBOX', 'checkbox');
    define('FLQFORM_RADIO',       'radio');
    define('FLQFORM_BUTTON',     'button');
    define('FLQFORM_PASSWORD', 'password');
    define('FLQFORM_SUBMIT',     'submit');
    define('FLQFORM_FILE',         'file');
    define('FLQFORM_HIDDEN',     'hidden');
    define('FLQFORM_EMAIL',       'email');
    define('FLQFORM_PHONE',         'tel');
    define('FLQFORM_URL',           'url');
    define('FLQFORM_DATE',         'date');
    define('FLQFORM_DATETIME', 'datetime');
    define('FLQFORM_NUMBER',     'number');
    define('FLQFORM_SEARCH',     'search');

    class FLQForm {

        /**
         * parseAttribs will take the attributes passed to a public function and create a string of attributes for use in the form field.
         *
         * @return string
         * @param mixed $attribs Either a string of attributes or a attrib=>value paired array.
         */
        protected static function parseAttribs($attribs) {
            $attrib_string = '';
            if (is_array($attribs)) {
                foreach ($attribs AS $attrib => $value) {
                    $attrib_string.= " {$attrib}=\"{$value}\"";
                }
            } else if (is_string($attribs)) {
                $attrib_string = stripslashes($attribs);
            }

            return $attrib_string;
        }

        /**
         * checkValue will see if there is current post data with the same name as the field being added
         * it will automatically assign that post data to the value
         *
         * @return string
         * @param string $name The name of the form field
         */
        protected static function checkValue($name, $value) {
            $newval = '';
            if (isset($_REQUEST[$name]) && !empty($_REQUEST[$name])) {
                $newval = $_REQUEST[$name];
            } else if (isset($GLOBALS[$name]) && !empty($GLOBALS[$name])) {
                $newval = $GLOBALS[$name];
            } else {
                $newval = $value;
            }

            if (is_array($newval)) {
                foreach ($newval as $k => $v) {
                    $newval[$k] = htmlentities($v, ENT_QUOTES, 'UTF-8');
                }
                return $newval;
            } else {
                return htmlentities($newval, ENT_QUOTES, 'UTF-8');
            }
        }

        /**
         * input is the base method for creating any form fields. This is a private method and should be called using the wrapper methods (textbox, hidden, etc).
         *
         * @return string valid HTML form field
         * @param string $type the type of input, valid options are; radio, checkbox, textbox, file, hidden, button, submit
         * @param string $name the name of the field
         * @param mixed $attribs either a string or an array of attributes for the field
         * @param string $value the fields value
         */
        protected static function input($type, $name, $attribs, $value) {
            $newval = $value;
            // Multiple radio buttons share the same name.
            if ($type != FLQFORM_CHECKBOX) {
                if ($type != FLQFORM_RADIO) {
                    $newval = self::checkValue($name, $value);
                } else {
                    $newval = $value;
                    if (!empty($_REQUEST[$name]) && $value == self::checkValue($name, $newval)) $attribs['checked'] = 'checked';
                }
            }
            $attribs = self::parseAttribs($attribs);
            return "<input type=\"{$type}\" name=\"{$name}\" value=\"{$newval}\"{$attribs}>";
        }

        /**
         * parseOptions builds a string of options for a select box
         *
         * @return string the string of options
         * @param array $options the options in a value=>label array
         * @param string selectedValue the value to be selected by default.
         */
        protected static function parseOptions($options, $selectedValue) {
            if (is_array($options)) {
                $optionlist = '';
                foreach ($options AS $value => $label) {
                    $selected = "";
                    $value = htmlentities($value, ENT_QUOTES, 'UTF-8');
                    if ($selectedValue == $value || (is_array($selectedValue) && in_array($value, $selectedValue))) {
                        $selected = " selected=\"selected\"";
                    }
                    $optionlist .= "<option value=\"{$value}\"{$selected}>" . htmlentities($label, ENT_QUOTES, 'UTF-8') . "</option>";
                }
                return $optionlist;
            }

            return FALSE;
        }

        /**
         * build is for building the initial <form> tag
         *
         * @return string the form tag
         * @param string $name the name of the form
         * @param mixed $attribs either a string or an array of attributes for the field
         */
        public static function build($name, $attribs=array()) {
            $attribs = self::parseAttribs($attribs);
            return "<form name=\"{$name}\"{$attribs}>";
        }

        /**
         * textbox is a wrapper for input and generates a textbox field
         *
         * @return string the textbox field
         * @param string $name the name of the field
         * @param mixed $attribs either a string or an array of attributes for the field
         * @param string $value the default value of the field
         */
        public static function textbox($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_TEXT, $name, $attribs, $value);
        }

        /**
         * emailbox is a wrapper for input and generates a textbox field
         *
         * @return string the emailbox field
         * @param string $name the name of the field
         * @param mixed $attribs either a string or an array of attributes for the field
         * @param string $value the default value of the field
         */
        public static function emailbox($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_EMAIL, $name, $attribs, $value);
        }
        
        /**
         * phonebox is a wrapper for input and generates a textbox field
         *
         * @return string the phonebox field
         * @param string $name the name of the field
         * @param mixed $attribs either a string or an array of attributes for the field
         * @param string $value the default value of the field
         */
        public static function phonebox($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_PHONE, $name, $attribs, $value);
        }

        public static function urlbox($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_URL, $name, $attribs, $value);
        }

        public static function datebox($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_DATE, $name, $attribs, $value);
        }

        public static function datetimebox($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_DATETIME, $name, $attribs, $value);
        }

        public static function numberbox($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_NUMBER, $name, $attribs, $value);
        }

        public static function searchbox($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_SEARCH, $name, $attribs, $value);
        }

        /**
         * hidden is a wrapper for input and generates a hidden field
         *
         * @return string the hidden field
         * @param string $name the name of the field
         * @param mixed $attribs either a string or an array of attributes for the field
         * @param string $value the default value of the field
         */
        public static function hidden($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_HIDDEN, $name, $attribs, $value);
        }

        /**
         * file is a wrapper for input and generates a file field
         *
         * @return string the file field
         * @param string $name the name of the field
         * @param mixed $attribs either a string or an array of attributes for the field
         * @param string $value the default value of the field
         */
        public static function file($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_FILE, $name, $attribs, $value);
        }

        /**
         * submit is a wrapper for input and generates a submit button
         *
         * @return string the submit button
         * @param string $name the name of the button
         * @param mixed $attribs either a string or an array of attributes for the button
         * @param string $value the default value of the button
         */
        public static function submit($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_SUBIT, $name, $attribs, $value);
        }

        /**
         * checkbox is a wrapper for input and generates a checkbox
         *
         * @return string the checkbox
         * @param string $name the name of the checkbox
         * @param mixed $attribs either a string or an array of attributes for the checkbox
         * @param string $value the default value of the checkbox
         */
        public static function checkbox($name, $attribs=array(), $checked=false, $value=1) {
            if (($checked && !isset($attribs['checked'])) || isset($_REQUEST[$name])) $attribs['checked'] = 'checked';
            return self::input(FLQFORM_CHECKBOX, $name, $attribs, $value);
        }

        /**
         * radio is a wrapper for input and generates a radio button
         *
         * @return string the radio button
         * @param string $name the name of the radio button
         * @param mixed $attribs either a string or an array of attributes for the radio button
         * @param string $value the default value of the radio button
         */
        public static function radio($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_RADIO, $name, $attribs, $value);
        }

        /**
         * submit is a wrapper for input and generates a button
         *
         * @return string the button
         * @param string $name the name of the button
         * @param mixed $attribs either a string or an array of attributes for the button
         * @param string $value the default value of the button
         */
        public static function button($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_BUTTON, $name, $attribs, $value);
        }

        /**
         * password is a wrapper for input and generates a password field
         *
         * @return string the field
         * @param string $name the name of the field
         * @param mixed $attribs either a string or an array of attributes for the field
         * @param string $value the default value of the field
         */
        public static function password($name, $attribs=array(), $value=NULL) {
            return self::input(FLQFORM_PASSWORD, $name, $attribs, $value);
        }

        /**
         * select is a wrapper for dropbox and generates a select list
         *
         * @return string the select list
         * @param string $name the name of the select list
         * @param mixed $attribs either a string or an array of attributes for the select list
         * @param string $value the default value of the select list
         */
        public static function select($name, $attribs=array(), $options, $selectedValue=NULL) {
            return self::dropbox($name, $attribs, $options, $selectedValue);
        }

        /**
         * dropbox generates a select list
         *
         * @return string the select list
         * @param string $name the name of the select list
         * @param mixed $attribs either a string or an array of attributes for the select list
         * @param string $value the default value of the select list
         */
        public static function dropbox($name, $attribs=array(), $options, $selectedValue=NULL) {
            $selectedValue = self::checkValue($name, $selectedValue);
            $newoptions = self::parseOptions($options, $selectedValue);
            $attribs = self::parseAttribs($attribs);
            return "<select name=\"{$name}\"{$attribs}>{$newoptions}</select>";
        }

        public static function textarea($name, $attribs=array(), $value=NULL) {
            $value = self::checkValue($name, $value);
            $attribs = self::parseAttribs($attribs);
            return "<textarea name=\"{$name}\"{$attribs}>{$value}</textarea>";
        }
    }
