<?php

use Medicines\MedicinesClient;
use Flow\JSONPath\JSONPath;

// exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}


// check if class already exists
if (!class_exists('acf_field_vtm')) :


class acf_field_vtm extends acf_field
{
    private $jsonpath = null;

    /*
    *  __construct
    *
    *  This function will setup the field type data
    *
    *  @type	function
    *  @date	5/03/2014
    *  @since	5.0.0
    *
    *  @param	n/a
    *  @return	n/a
    */

    public function __construct($settings)
    {
        $this->name = 'vtm';
        $this->label = __('Medicine (VTM)', 'acf-vtm');
        $this->category = 'choice';
        $this->defaults = array(
            'post_type'		=> array(),
            'taxonomy'		=> array(),
            'allow_null' 	=> 0,
            'multiple'		=> 0,
        );
        $this->api = new MedicinesClient();


        /*
        *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
        *  var message = acf._e('vtm', 'error');
        */

        $this->l10n = array(
            'error'	=> __('Error! Please enter a higher value', 'acf-vtm'),
        );


        /*
        *  settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
        */
        $this->settings = $settings;

        add_action('wp_ajax_acf/fields/vtm/query', array($this, 'ajax_query'));
        add_action('wp_ajax_nopriv_acf/fields/vtm/query', array($this, 'ajax_query'));

        // do not delete!
        parent::__construct();
    }

    /*
    *  find_vtms
    *
    *  Find all matching VTMs to a query against the medicines API
    *
    *  @type	function
    *  @date	01/06/2016
    *  @since	1.0.0
    *
    *  @param	$args post args (s contains the query)
    *  @return	$results array of vtm -> [ampp] results
    */
    public function find_vtms($name)
    {
        $query = array();
        $query['name'] = $name;
        $query['per'] = 100;

        $results = $this->api->vtms($query);

        return $results;
    }

    /**
     * Transform a set of API results into id / text pairs
     */
    public function transform($matches)
    {
        $transformed = array();

        foreach ($matches as $match) {
            $entry = array(
                'id' => strval($match['id']),
                'text' => $match['name']
            );

            array_push($transformed, $entry);
        }

        return $transformed;
    }

    /*
    *  ajax_query
    *
    *  description
    *
    *  @type	function
    *  @date	24/10/13
    *  @since	5.0.0
    *
    *  @param	$post_id (int)
    *  @return	$post_id (int)
    */

    public function ajax_query()
    {

        // validate
        if (!acf_verify_ajax()) {
            die();
        }


        // get choices
        $query = $_POST['s'];
        $matches = $this->find_vtms($query);
        $choices = $this->transform($matches);

        // validate
        if (!$choices) {
            die();
        }


        // return JSON
        $json = json_encode(array('results' => $choices ));

        //error_log($json);
        echo $json;
        die();
    }


    /*
    *  render_field_settings()
    *
    *  Create extra settings for your field. These are visible when editing a field
    *
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field (array) the $field being edited
    *  @return	n/a
    */

    public function render_field_settings($field)
    {

        /*
        *  acf_render_field_setting
        *
        *  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
        *  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
        *
        *  More than one setting can be added by copy/paste the above code.
        *  Please note that you must also have a matching $defaults value for the field name (font_size)
        */

        acf_render_field_setting($field, array(
            'label'			=> __('Font Size', 'acf-vtm'),
            'instructions'	=> __('Customise the input font size', 'acf-vtm'),
            'type'			=> 'number',
            'name'			=> 'font_size',
            'prepend'		=> 'px',
        ));
    }



    /*
    *  render_field()
    *
    *  Create the HTML interface for your field
    *
    *  @param	$field (array) the $field being rendered
    *
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field (array) the $field being edited
    *  @return	n/a
    */

    public function render_field($field)
    {
        $this->field_name = $field['_name'];

        $field['type'] = 'select';
        $field['ui'] = 1;
        $field['ajax'] = 1;
        $field['choices'] = array();

        if (!empty($field['value'])) {
            $name = '';

            // get the medicine
            $query = array(
                "scheme" => "core"
            );
            $vtm = $this->api->vtm($field['value'], $query);
            if ($vtm) {
                $jsonpath = new JSONPath($vtm);
                $selector = '$.name';
                $match = $jsonpath->find($selector);
                $data = $match->data();
                if ($data && is_array($data) && count($data) === 1) {
                    $name = $data[0];
                }
            }

            // populate the choices
            $field['choices'][$field['value']] = $name;
        }

        acf_render_field($field);
        /*
        *  Create a simple text input using the 'font_size' setting.


        ?>
        <input type="text" name="<?php echo esc_attr($field['name']) ?>" value="<?php echo esc_attr($field['value']) ?>" style="font-size:<?php echo $field['font_size'] ?>px;" />
        <?php
        */
    }


    /*
    *  input_admin_enqueue_scripts()
    *
    *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
    *  Use this action to add CSS + JavaScript to assist your render_field() action.
    *
    *  @type	action (admin_enqueue_scripts)
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	n/a
    *  @return	n/a
    */

    public function input_admin_enqueue_scripts()
    {

        // vars
        $url = $this->settings['url'];
        $version = $this->settings['version'];


        // register & include JS
        wp_register_script('acf-input-vtm', "{$url}assets/js/input.js", array('acf-input'), $version);
        wp_enqueue_script('acf-input-vtm');


        // register & include CSS
        wp_register_style('acf-input-vtm', "{$url}assets/css/input.css", array('acf-input'), $version);
        wp_enqueue_style('acf-input-vtm');
    }


    /*
    *  input_admin_head()
    *
    *  This action is called in the admin_head action on the edit screen where your field is created.
    *  Use this action to add CSS and JavaScript to assist your render_field() action.
    *
    *  @type	action (admin_head)
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	n/a
    *  @return	n/a
    */

    /*

    function input_admin_head() {



    }

    */


    /*
    *  input_form_data()
    *
    *  This function is called once on the 'input' page between the head and footer
    *  There are 2 situations where ACF did not load during the 'acf/input_admin_enqueue_scripts' and
    *  'acf/input_admin_head' actions because ACF did not know it was going to be used. These situations are
    *  seen on comments / user edit forms on the front end. This function will always be called, and includes
    *  $args that related to the current screen such as $args['post_id']
    *
    *  @type	function
    *  @date	6/03/2014
    *  @since	5.0.0
    *
    *  @param	$args (array)
    *  @return	n/a
    */

    /*

    function input_form_data( $args ) {



    }

    */


    /*
    *  input_admin_footer()
    *
    *  This action is called in the admin_footer action on the edit screen where your field is created.
    *  Use this action to add CSS and JavaScript to assist your render_field() action.
    *
    *  @type	action (admin_footer)
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	n/a
    *  @return	n/a
    */

    /*

    function input_admin_footer() {



    }

    */


    /*
    *  field_group_admin_enqueue_scripts()
    *
    *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
    *  Use this action to add CSS + JavaScript to assist your render_field_options() action.
    *
    *  @type	action (admin_enqueue_scripts)
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	n/a
    *  @return	n/a
    */

    /*

    function field_group_admin_enqueue_scripts() {

    }

    */


    /*
    *  field_group_admin_head()
    *
    *  This action is called in the admin_head action on the edit screen where your field is edited.
    *  Use this action to add CSS and JavaScript to assist your render_field_options() action.
    *
    *  @type	action (admin_head)
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	n/a
    *  @return	n/a
    */

    /*

    function field_group_admin_head() {

    }

    */


    /*
    *  load_value()
    *
    *  This filter is applied to the $value after it is loaded from the db
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value (mixed) the value found in the database
    *  @param	$post_id (mixed) the $post_id from which the value was loaded
    *  @param	$field (array) the field array holding all the field options
    *  @return	$value
    */
    /*
    function load_value( $value, $post_id, $field ) {

        return $value;

    }*/

    /*
    *  update_value()
    *
    *  This filter is applied to the $value before it is saved in the db
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value (mixed) the value found in the database
    *  @param	$post_id (mixed) the $post_id from which the value was loaded
    *  @param	$field (array) the field array holding all the field options
    *  @return	$value
    */
    /*
    function update_value( $value, $post_id, $field ) {

        return $value;

    }
    */

    /*
    *  format_value()
    *
    *  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value (mixed) the value which was loaded from the database
    *  @param	$post_id (mixed) the $post_id from which the value was loaded
    *  @param	$field (array) the field array holding all the field options
    *
    *  @return	$value (mixed) the modified value
    */
    /*
    function format_value( $value, $post_id, $field ) {

        // bail early if no value
        if( empty($value) ) {

            return $value;

        }


        // apply setting
        if( $field['font_size'] > 12 ) {

            // format the value
            // $value = 'something';

        }


        // return
        return $value;
    }
    */

    /*
    *  validate_value()
    *
    *  This filter is used to perform validation on the value prior to saving.
    *  All values are validated regardless of the field's required setting. This allows you to validate and return
    *  messages to the user if the value is not correct
    *
    *  @type	filter
    *  @date	11/02/2014
    *  @since	5.0.0
    *
    *  @param	$valid (boolean) validation status based on the value and the field's required setting
    *  @param	$value (mixed) the $_POST value
    *  @param	$field (array) the field array holding all the field options
    *  @param	$input (string) the corresponding input name for $_POST value
    *  @return	$valid
    */
    /*
    function validate_value( $valid, $value, $field, $input ){
        $valid = true;

        // Basic usage
        if( $value < $field['custom_minimum_setting'] )
        {
            $valid = false;
        }


        // Advanced usage
        if( $value < $field['custom_minimum_setting'] )
        {
            $valid = __('The value is too little!','acf-vtm');
        }

        // return
        return $valid;

    }
    */

    /*
    *  delete_value()
    *
    *  This action is fired after a value has been deleted from the db.
    *  Please note that saving a blank value is treated as an update, not a delete
    *
    *  @type	action
    *  @date	6/03/2014
    *  @since	5.0.0
    *
    *  @param	$post_id (mixed) the $post_id from which the value was deleted
    *  @param	$key (string) the $meta_key which the value was deleted
    *  @return	n/a
    */
    /*
    function delete_value( $post_id, $key ) {

    }
    */

    /*
    *  load_field()
    *
    *  This filter is applied to the $field after it is loaded from the database
    *
    *  @type	filter
    *  @date	23/01/2013
    *  @since	3.6.0
    *
    *  @param	$field (array) the field array holding all the field options
    *  @return	$field
    */
    /*
    function load_field( $field ) {

        return $field;

    }
    */

    /*
    *  update_field()
    *
    *  This filter is applied to the $field before it is saved to the database
    *
    *  @type	filter
    *  @date	23/01/2013
    *  @since	3.6.0
    *
    *  @param	$field (array) the field array holding all the field options
    *  @return	$field
    */
    /*
    function update_field( $field ) {

        return $field;

    }
    */

    /*
    *  delete_field()
    *
    *  This action is fired after a field is deleted from the database
    *
    *  @type	action
    *  @date	11/02/2014
    *  @since	5.0.0
    *
    *  @param	$field (array) the field array holding all the field options
    *  @return	n/a
    */
    /*
    function delete_field( $field ) {



    }
    */
}


// initialize
new acf_field_vtm($this->settings);


// class_exists check
endif;
