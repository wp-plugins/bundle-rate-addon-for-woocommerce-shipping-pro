<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WF_Settings_Shipping_Pro' ) ) :

class WF_Settings_Page {

	protected $id    = '';
	protected $label = '';

	/**
	 * Add this page to settings
	 */
	public function add_settings_page( $pages ) {
		$pages[ $this->id ] = $this->label;
		return $pages;
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		return apply_filters( 'woocommerce_get_settings_' . $this->id, array() );
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {
		return apply_filters( 'woocommerce_get_sections_' . $this->id, array() );
	}

	/**
	 * Output sections
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}

	/**
	 * Output the settings
	 */
	public function output() {
		$settings = $this->get_settings();
		WC_Admin_Settings::output_fields( $settings );	
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings();
		
		if(!isset($_POST[ 'wf_add_on_matrix']) || empty($_POST[ 'wf_add_on_matrix'])){
			delete_option( 'wf_add_on_matrix');
		}
		
		
		
		WC_Admin_Settings::save_fields( $settings );

		if ( $current_section ) {
			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
		}
	}
}

/**
 * WF_Settings_Shipping_Pro
 */
class WF_Settings_Shipping_Pro extends WF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    = 'wf_shipping_pro_addon';
		$this->label = __( 'Shipping Pro AddOn', 'wf_shipping_pro_addon' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 30 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_admin_field_wf_add_on_matrix', array( $this, 'wf_add_on_matrix_setting' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );	
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters( 'woocommerce_' . $this->id . '_settings', 
		array(
			array(	'title' => __( 'Bundle Rate AddOn for WooCommerce Shipping Pro', 'wf_shipping_pro_addon' ), 
					'type' => 'title', 
					'desc' => __( '<strong>This plugin is an add-on for <a href="http://www.wooforce.com/product/woocommerce-shipping-pro-with-table-rates-plugin/" target="_blank">Shipping Pro Plugin</a> and does not work independently.</strong></br>Calculation mode in Shipping Pro plug-in should be set as "Per Category" Or "Per Shipping class".</br>- Shipping rate of a particular Category of product can be set to "Free" or "Fixed Cost" when purchased along with other Category of products. </br>- Similarly, Shipping rate of a particular Shipping Class can be set to "Free" or "Fixed Cost", when purchased along with other Shipping Class.', 'wf_shipping_pro_addon' )),
			array(
				'title'   => __( 'Enable/Disable', 'wf_shipping_pro_addon' ),
				'type'    => 'checkbox',
				'desc'   => __( 'Enable this AddOn', 'wf_shipping_pro_addon' ),
				'default' => 'no',
				'id' => 'wf_add_on_enabled'
			),	
			array(
				'type' => 'wf_add_on_matrix',
				'id' => 'wf_add_on_matrix'
			),
			array( 'type' => 'sectionend', 'id' => 'wf_add_on_matrix_end')
		) );
		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}
	public function wf_hidden_matrix_column($column_name){
		return in_array($column_name,$this->displayed_columns) ? '' : 'hidecolumn';	
	}
	
	public function wf_product_category_dropdown_options( $selected_categories = array()) {
		if ($this->product_category) foreach ( $this->product_category as $product_id=>$product_name) :
			echo '<option value="' . $product_id .'"';
			if (!empty($selected_categories) && ((is_array($selected_categories) && in_array($product_id,$selected_categories)) || (!is_array($selected_categories) && $product_id == $selected_categories))) echo ' selected="selected"';
			echo '>' . esc_js( $product_name ) . '</option>';
		endforeach;
	}
	
	public function wf_shipping_class_dropdown_options( $selected_class = array()) {
		if ($this->shipping_classes) foreach ( $this->shipping_classes as $class) :
			echo '<option value="' . esc_attr($class->slug) .'"';
			if (!empty($selected_class) && ((is_array($selected_class) && in_array($class->slug,$selected_class)) || (!is_array($selected_class) && $class->slug == $selected_class))) echo ' selected="selected"';
			echo '>' . esc_js( $class->name ) . '</option>';
		endforeach;
	}
	
	public function wf_add_on_matrix_setting() {	
		$this->displayed_columns       = array(
					'shipping_class',
					'product_category' ,'cost' ) ;
		
		$this->shipping_classes =WC()->shipping->get_shipping_classes();
		
		$this->product_category  = get_terms( 'product_cat', array('fields' => 'id=>name'));
		$this->wf_add_on_matrix = get_option( 'wf_add_on_matrix' );
		ob_start();					
		?>
		<tr valign="top" id="packing_options">
			<td class="titledesc" colspan="2" style="padding-left:0px">
				<strong><?php _e( 'Override the Shipping Pro rates if below configured rules are satisfied:', 'wf_shipping_pro_addon' ); ?></strong><br />
				<?php _e( '- If calculation mode in Shipping Pro plug-in is "Per Shipping class", then only "Shipping Class" columns are applicable in the below matrix.', 'wf_shipping_pro_addon' ); ?><br />
				<?php _e( '- If calculation mode in Shipping Pro plug-in is "Per Category", then only "Category" columns are applicable in the below matrix.', 'wf_shipping_pro_addon' ); ?>
				<br /><br />
				<style type="text/css">
					.shipping_pro_boxes .row_data td
					{
						border-bottom: 1pt solid #e1e1e1;
					}
					
					.shipping_pro_boxes input, 
					.shipping_pro_boxes select, 
					.shipping_pro_boxes textarea,
					.shipping_pro_boxes .select2-container-multi .select2-choices{
						background-color: #fbfbfb;
						border: 1px solid #e9e9e9;
					}
					 					
					.shipping_pro_boxes td, .shipping_pro_services td {
						vertical-align: top;
							padding: 4px 7px;
							
					}
					.shipping_pro_boxes th, .shipping_pro_services th {
						padding: 9px 7px;
					}
					.shipping_pro_boxes td input {
						margin-right: 4px;
					}
					.shipping_pro_boxes .check-column {
						vertical-align: top;
						text-align: left;
						padding: 4px 7px;
					}
					.shipping_pro_services th.sort {
						width: 16px;
					}
					.shipping_pro_services td.sort {
						cursor: move;
						width: 16px;
						padding: 0 16px;
						cursor: move;
						background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAHUlEQVQYV2O8f//+fwY8gJGgAny6QXKETRgEVgAAXxAVsa5Xr3QAAAAASUVORK5CYII=) no-repeat center;
					}
					@media screen and (min-width: 781px) 
					{
						th.tiny_column
						{
						  width:2em;
						  max-width:2em;
						  min-width:2em;									  
						}
						th.small_column
						{
						   width:4em;	
						   max-width:4em; 	
						   min-width:4em;
						}
						th.smallp_column
						{
						   width:4.5em;	
						   max-width:4.5em; 	
						   min-width:4.5em;
						}
						th.medium_column
						{
						   min-width:90px;	 
						}
						th.big_column
						{
							min-width:250px;
						}									
					}
					th.hidecolumn,
					td.hidecolumn
					{
							display:none;
					}
								
				</style>
				
				<table class="shipping_pro_boxes widefat" style="background-color:#f6f6f6;">
					<thead>
						<tr>
							<th class="check-column tiny_column"><input type="checkbox" /></th>
							<th class="small_column <?php echo $this->wf_hidden_matrix_column('shipping_class');?>">
							<?php _e( 'For this Shipping Class', 'wf_shipping_pro_addon' );  ?>
							<img class="help_tip" style="float:none;" data-tip="<?php _e( 'I would like to change the shipping rate for the following shipping class products', 'wf_shipping_pro_addon' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /><br>
							</th>
							<th class="big_column <?php echo $this->wf_hidden_matrix_column('shipping_class');?>">
							<?php _e( 'With these Shipping Class(s) ', 'wf_shipping_pro_addon' );  ?>
							<img class="help_tip" style="float:none;" data-tip="<?php _e( 'When customer purchase with any of the following shipping class products', 'wf_shipping_pro_addon' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
							</th>
							<th class="small_column <?php echo $this->wf_hidden_matrix_column('product_category');?>">
							<?php _e( 'For this product Category', 'wf_shipping_pro_addon' );  ?>
							<img class="help_tip" style="float:none;" data-tip="<?php _e( 'I would like to change the shipping rate for the following category products', 'wf_shipping_pro_addon' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /><br>
							</th>
							<th class="big_column <?php echo $this->wf_hidden_matrix_column('product_category');?>">
							<?php _e( 'With these Product Category(s)', 'wf_shipping_pro_addon' );  ?>
							<img class="help_tip" style="float:none;" data-tip="<?php _e( 'When customer purchase with any of the following category products', 'wf_shipping_pro_addon' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
							</th>
							<th class="small_column <?php echo $this->wf_hidden_matrix_column('cost');?>">
							<?php _e( 'New Cost', 'wf_shipping_pro_addon' );  ?>
							<img class="help_tip" style="float:none;" data-tip="<?php _e( 'This cost will override shipping class/product category cost calculated by the parent shipping pro plugin', 'wf_shipping_pro_addon' ); ?>" src="<?php echo WC()->plugin_url();?>/assets/images/help.png" height="16" width="16" /> 
							</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="3">
								<a href="#" class="button insert"><?php _e( 'Add rule', 'wf_shipping_pro_addon' ); ?></a>
								<a href="#" class="button remove"><?php _e( 'Remove rule(es)', 'wf_shipping_pro_addon' ); ?></a>
								<a href="#" class="button duplicate"><?php _e( 'Duplicate rule(es)', 'wf_shipping_pro_addon' ); ?></a>
							</th>
							<th colspan="3">
								<small class="description"><?php _e( 'Weight Unit & Dimensions Unit as per WooCommerce settings.', 'wf_shipping_pro_addon' ); ?></small>
							</th>
						</tr>
					</tfoot>
					<tbody id="rates">
					<?php								
					$matrix_rowcount = 0;
					if ( $this->wf_add_on_matrix ) {
						foreach ( $this->wf_add_on_matrix as $key => $box ) {												
							$set_for_shipping_classes = isset($box['set_for_shipping_class']) ? $box['set_for_shipping_class'] : array();
							$if_exist_defined_shipping_classes = isset($box['if_exists_shipping_class']) ? $box['if_exists_shipping_class'] : array();
							$set_for_product_category = isset($box['set_for_product_category']) ? $box['set_for_product_category'] : array();
							$if_exist_defined_product_category = isset($box['if_exists_product_category']) ? $box['if_exists_product_category'] : array();
							?>
							<tr class="row_data"><td class="check-column"><input type="checkbox" /></td>
							<td class="<?php echo $this->wf_hidden_matrix_column('shipping_class');?>">
							<select id="set_for_shipping_class_<?php echo $key;?>" class="select singleselect" name="wf_add_on_matrix[<?php echo $key;?>][set_for_shipping_class]" data-identifier="set_for_shipping_class">
							<option>Select Shipping Class</option>
							<?php $this->wf_shipping_class_dropdown_options($set_for_shipping_classes); ?>
							</select></td>
							<td class="<?php echo $this->wf_hidden_matrix_column('shipping_class');?>" style='overflow:visible'>
							<select id="if_exists_shipping_class_<?php echo $key;?>" class="multiselect chosen_select enabled" data-identifier="if_exists_shipping_class" multiple="true" style="width:100%;" name='wf_add_on_matrix[<?php echo $key;?>][if_exists_shipping_class][]'>								
								<?php $this->wf_shipping_class_dropdown_options($if_exist_defined_shipping_classes); ?>
							</select>
							</td>
							<td class="<?php echo $this->wf_hidden_matrix_column('product_category');?>" style='overflow:visible'>
							<select id="set_for_product_category_<?php echo $key;?>" class="select singleselect" data-identifier="set_for_product_category"  name='wf_add_on_matrix[<?php echo $key;?>][set_for_product_category]'>								
								<option>Select Product Category</option>
								<?php $this->wf_product_category_dropdown_options($set_for_product_category); ?>
								</select>
							</td>
							<td class="<?php echo $this->wf_hidden_matrix_column('product_category');?>">
							<select id="if_exists_product_category_<?php echo $key;?>" multiple="true" style="width:100%;" class="multiselect chosen_select enabled" name="wf_add_on_matrix[<?php echo $key;?>][if_exists_product_category][]" data-identifier="if_exists_product_category">
							<?php $this->wf_product_category_dropdown_options($if_exist_defined_product_category); ?>
							</select></td>
							<td class="<?php echo $this->wf_hidden_matrix_column('cost');?>"><input type='text' size='5' name='wf_add_on_matrix[<?php echo $key;?>][cost]'			value='<?php echo $box['cost']; ?>' /></td>
							</tr>
							<?php
							if(!empty($key) && $key >= $matrix_rowcount)
								$matrix_rowcount = $key;
						}
					}
					?>
					<input type="hidden" id="matrix_rowcount" value="<?php echo$matrix_rowcount;?>" />
					</tbody>
				</table>
				<script type="text/javascript">																	
					jQuery(window).load(function(){									
						jQuery('.shipping_pro_boxes .insert').click( function() {
							var $tbody = jQuery('.shipping_pro_boxes').find('tbody');
							var size = $tbody.find('#matrix_rowcount').val();
							if(size){
								size = parseInt(size)+1;
							}
							else
								size = 0;
							
							var code = '<tr class="new row_data"><td class="check-column"><input type="checkbox" /></td>\
							<td class="<?php echo $this->wf_hidden_matrix_column('shipping_class');?>">\
							<select id="set_for_shipping_class_'+size+'" class="select singleselect" data-identifier="set_for_shipping_class" name="wf_add_on_matrix['+size+'][set_for_shipping_class]">\
							<option>Select Shipping Class</option>\
							<?php $this->wf_shipping_class_dropdown_options(); ?></select>\
							</td>\
							<td class="<?php echo $this->wf_hidden_matrix_column('shipping_class');?>" style="overflow:visible">\
							<select id="if_exists_shipping_class'+size+'" class="multiselect chosen_select enabled" data-identifier="if_exists_shipping_class" multiple="true" style="width:100%;" name="wf_add_on_matrix['+size+'][if_exists_shipping_class][]">\
							<?php $this->wf_shipping_class_dropdown_options(); ?></select>\
							</td>\
							<td class="<?php echo $this->wf_hidden_matrix_column('product_category');?>">\
							<select id="set_for_product_category_'+size+'" class="select singleselect" data-identifier="set_for_product_category" name="wf_add_on_matrix['+size+'][set_for_product_category]">\
							<option>Select Product Category</option>\
							<?php $this->wf_product_category_dropdown_options(); ?></select>\
							</td>\
							<td class="<?php echo $this->wf_hidden_matrix_column('product_category');?>" style="overflow:visible">\
							<select id="if_exists_product_category_'+size+'" class="multiselect chosen_select enabled" data-identifier="if_exists_product_category"  multiple="true" style="width:100%;" name="wf_add_on_matrix['+size+'][if_exists_product_category][]">\
							<?php $this->wf_product_category_dropdown_options(); ?></select>\
							</td>\
							<td class="<?php echo $this->wf_hidden_matrix_column('cost');?>"><input type="text" size="5" name="wf_add_on_matrix['+size+'][cost]" /></td>\
							</tr>';										
							$tbody.append( code );
							if(typeof wc_enhanced_select_params == 'undefined')
								$tbody.find('tr:last').find("select.chosen_select").chosen();
							else
								$tbody.find('tr:last').find("select.chosen_select").trigger( 'wc-enhanced-select-init' );
							
								
							$tbody.find('#matrix_rowcount').val(size);
							return false;
						} );

						jQuery('.shipping_pro_boxes .remove').click(function() {
							var $tbody = jQuery('.shipping_pro_boxes').find('tbody');

							$tbody.find('.check-column input:checked').each(function() {
								jQuery(this).closest('tr').prev('.rule_text').remove();
								jQuery(this).closest('tr').remove();
								});

							return false;
						});
						
						jQuery('.shipping_pro_boxes .duplicate').click(function() {
							var $tbody = jQuery('.shipping_pro_boxes').find('tbody');

							var new_trs = [];
							
							$tbody.find('.check-column input:checked').each(function() {
								var $tr    = jQuery(this).closest('tr');
								var $clone = $tr.clone();
								var size = jQuery('#matrix_rowcount').val();
								if(size)
									size = parseInt(size)+1;
								else
									size = 0;
								
								
								$tr.find('select.multiselect').each(function(i){
									var selecteddata;
									if(typeof wc_enhanced_select_params == 'undefined')
										selecteddata = jQuery(this).chosen().val();
									else
										selecteddata = jQuery(this).select2('data');
									
									if ( selecteddata ) {
										var arr = [];
										jQuery.each( selecteddata, function( id, text ) {
											if(typeof wc_enhanced_select_params == 'undefined')
												arr.push(text);
											else
												arr.push(text.id);											
										});
										var currentIdentifierAttr = jQuery(this).attr('data-identifier'); 
										if(currentIdentifierAttr){
											$clone.find("select[data-identifier='"+currentIdentifierAttr+"']").val(arr);
											//$clone.find('select#' + this.id).val(arr);
										}										
									}
								});
								
								$tr.find('select.singleselect').each(function(i){
									var selecteddata = jQuery(this).val();
									if ( selecteddata ) {
										var currentIdentifierAttr = jQuery(this).attr('data-identifier'); 
										if(currentIdentifierAttr){
											$clone.find("select[data-identifier='"+currentIdentifierAttr+"']").val(selecteddata);
											//$clone.find('select#' + this.id).val(selecteddata);										
										}
									}
								});
								
								
								if(typeof wc_enhanced_select_params == 'undefined')
									$clone.find('div.chosen-container, div.chzn-container').remove();									
								else
									$clone.find('div.multiselect').remove();								
								
								$clone.find('.multiselect').show();
								$clone.find('.multiselect').removeClass("enhanced chzn-done");
								// find all the inputs within your new clone and for each one of those
								$clone.find('input[type=text], select').each(function() {
									var currentNameAttr = jQuery(this).attr('name'); 
									if(currentNameAttr){
										var newNameAttr = currentNameAttr.replace(/\d+/, size);
										jQuery(this).attr('name', newNameAttr);   // set the incremented name attribute 
									}
									var currentIdAttr = jQuery(this).attr('id'); 
									if(currentIdAttr){
										var currentIdAttr = currentIdAttr.replace(/\d+/, size);
										jQuery(this).attr('id', currentIdAttr);   // set the incremented name attribute 
									}
								});
								//$tr.after($clone);
								//$clone.find('select.chosen_select').trigger( 'chosen_select-init' );
								new_trs.push($clone);
								jQuery('#matrix_rowcount').val(size);
								//jQuery("select.chosen_select").trigger( 'chosen_select-init' );							
							});
							if(new_trs)
							{
								var lst_tr    = $tbody.find('.check-column :input:checkbox:checked:last').closest('tr');
								jQuery.each( new_trs.reverse(), function( id, text ) {
										//adcd.after(text);
										lst_tr.after(text);
										if(typeof wc_enhanced_select_params == 'undefined')
											text.find('select.chosen_select').chosen();			
										else
											text.find('select.chosen_select').trigger( 'wc-enhanced-select-init' );																	
									});
							}
							$tbody.find('.check-column input:checked').removeAttr('checked');
							return false;
						});									
					});
				</script>
			</td>
		</tr>
		<?php
		ob_end_flush();
			
	}
}

endif;

return new WF_Settings_Shipping_Pro();
