<?php

use Medicines\MedicinesClient;
use Flow\JSONPath\JSONPath;

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('acf_field_vtm') ) :


class acf_field_vtm extends acf_field {
	
	private $jsonpath = null;
	
	const MEDICINES_FIELDS = array(
		"vtm_id" => "id",
		"vtm_name" => "name",
		"vtm_applicable_from" => "applicable_from",
		
		"vmp_id" => "virtual_medicinal_products[0].id",
		"vmp_applicable_from" => "virtual_medicinal_products[0].applicable_from",
		"vmp_name" => "virtual_medicinal_products[0].name",
		"vmp_name_standard" => null,
		"vmp_name_applicable_from" => "virtual_medicinal_products[0].name_applicable_from",
		"vmp_sugar_free" => "virtual_medicinal_products[0].sugar_free",
		"vmp_gluten_free" => "virtual_medicinal_products[0].gluten_free",
		"vmp_preservative_free" => "virtual_medicinal_products[0].preservative_free",
		"vmp_cfc_free" => "virtual_medicinal_products[0].cfc_free",
		"vmp_prescribing_status" => "virtual_medicinal_products[0].prescribing_status",
		"vmp_dose_form_indicator" => "virtual_medicinal_products[0].unit_dose_form",
		"vmp_dose_form_size" => "virtual_medicinal_products[0].unit_dose_form_size",
		"vmp_dose_form_units" => "virtual_medicinal_products[0].unit_dose_form_units",
		"vmp_dose_form_unit_measure" => "virtual_medicinal_products[0].unit_dose_unit_of_measure",
		
		"amp_id" => "virtual_medicinal_products[0].actual_medicinal_products[0].id",
		"amp_pack_description" => "virtual_medicinal_products[0].actual_medicinal_products[0].summary",
		"amp_combination_pack_indicator" => "virtual_medicinal_products[0].actual_medicinal_products[0].combination_product",
		"amp_quantity" => null,
		"amp_quantity_units" => null,
		
		"vmpp_id" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].id",
		"vmpp_pack_description" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].summary",
		"vmpp_combination_pack_indicator" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].combination_pack",
		"vmpp_quantity" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].quantity",
		"vmpp_quantity_units" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].quantity_measure",
		
		"ampp_name" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].actual_medicinal_product_packs[0].name",
		"ampp_combination_pack_indicator" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].actual_medicinal_product_packs[0].ampp_combination_pack_indicator",
		"ampp_sub_pack_information" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].actual_medicinal_product_packs[0].sub_pack_information",
		"ampp_legal_category" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].actual_medicinal_product_packs[0].legal_category",
		"ampp_price" => "virtual_medicinal_products[0].virtual_medicinal_product_packs[0].actual_medicinal_product_packs[0].medicinal_product_price.price"
	);
	
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
	
	function __construct( $settings ) {
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
		add_action('wp_ajax_nopriv_acf/fields/vtm/query',	array($this, 'ajax_query'));

		add_action('acf/save_post', array($this, 'update_medicine_information'), 1);
		
		// do not delete!
    	parent::__construct();
    	
	}

	/*
	*  get_ampps
	*
	*  load matches at AMPP level from DM+D given a partial 
	*  virtual therapeutic moiety name 
	*
	*  @type	function
	*  @date	01/06/2016
	*  @since	1.0.0
	*
	*  @param	$args post args (s contains the query)
	*  @return	$results array of vtm -> [ampp] results 
	*/
	function get_ampps( $args ) {
		$query = array();
		$query['name'] = $args['s'];

		$results = $this->api->ampps($query);
		
		return $results;
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
	
	function ajax_query() {

		error_log('ajax_query');
		error_log(print_r($_POST, true));
		
		// validate
		if( !acf_verify_ajax() ) die();
		
		
		// get choices
		$choices = $this->get_ampps( $_POST );
		
		// validate
		if( !$choices ) die();
		
		
		// return JSON
		$json = json_encode( $choices );
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
	
	function render_field_settings( $field ) {
		
		/*
		*  acf_render_field_setting
		*
		*  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
		*  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
		*
		*  More than one setting can be added by copy/paste the above code.
		*  Please note that you must also have a matching $defaults value for the field name (font_size)
		*/
		
		acf_render_field_setting( $field, array(
			'label'			=> __('Font Size','acf-vtm'),
			'instructions'	=> __('Customise the input font size','acf-vtm'),
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
	
	function render_field( $field ) {
		
		$field['type'] = 'select';
		$field['ui'] = 1;
		$field['ajax'] = 1;

		if( !empty($field['value']) ) {
			$name = '';
			
			// get the medicine
			$ampp = $this->api->ampp($field['value']);
			if ($ampp) {
				$jsonpath = new JSONPath($ampp);
				$selector = '$.'.self::MEDICINES_FIELDS['ampp_name'];
				$match = $jsonpath->find($selector);
				$data = $match->data();
				if ($data && is_array($data) && count($data) === 1) {
					$name = $data[0];
				}
			}
			
			// populate the choices
			$field['choices'] = array();
			$field['choices'][$field['value']] = $name;
		}
		
		acf_render_field( $field );
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
	
	function input_admin_enqueue_scripts() {
		
		// vars
		$url = $this->settings['url'];
		$version = $this->settings['version'];
		
		
		// register & include JS
		wp_register_script( 'acf-input-vtm', "{$url}assets/js/input.js", array('acf-input'), $version );
		wp_enqueue_script('acf-input-vtm');
		
		
		// register & include CSS
		wp_register_style( 'acf-input-vtm', "{$url}assets/css/input.css", array('acf-input'), $version );
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

	function update_medicine_information($post_id) {
		$field = $this->get_acf_field_by_name('ampp_id', false, $post_id);
		if ( isset($field['key'])) {
			$field_key = $field['key'];
			$ampp_id = $_POST['acf'][$field_key];
			
			// get the medicine 
			$medicine = $this->api->ampp( $ampp_id );
			
			//if ($medicine) {
			foreach(self::MEDICINES_FIELDS as $key => $pattern) {
				$this->update_medicine_field($key, $pattern, $medicine);
			}
			//}
		}
	}
	
	function update_medicine_field($field_name, $pattern, $medicine) {

		if (!$this->jsonpath) {
			$this->jsonpath = new JSONPath($medicine);
		}
		
		$field = $this->get_acf_field_by_name($field_name, false);
		if (isset($field['key'])) {
			$field_key = $field['key'];
			
			$value = null;
			
			if ($pattern) {
				$selector = '$.'.$pattern;
				$match = $this->jsonpath->find($selector);
				$value = $match->data();
				
				if (is_array($value) && count($value) === 1) {
					$value = $value[0];
				}
			}	
			
			$_POST['acf'][$field_key] = $value;
			
		}
	}
	
	function get_acf_field_by_name($name = '', $db_only = false) {
		$args = array(
			'posts_per_page'	=> 0,
			'post_type'			=> 'acf-field',
			'orderby' 			=> 'menu_order title',
			'order'				=> 'ASC',
			'suppress_filters'	=> false,
			'acf_field_name'	=> $name
		);
		// load posts
		$posts = get_posts( $args );
		
		// return first one that is not a tab
		foreach($posts as $post) {
			$field = _acf_get_field_by_id($post->ID, $db_only);
			if ( $field['type'] !== 'tab') {
				return $field;
			}
		}
	}
	
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
new acf_field_vtm( $this->settings );


// class_exists check
endif;

?>
