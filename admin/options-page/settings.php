<?php

/**
 * This class contains all the fields used for the plugin settings 
 *
 * @version 3.0 
 */
 
if(!defined('ABSPATH')){
    exit; // Exit if accessed directly
}

if( !class_exists( 'CspmNearbyMapSettings' ) ){
	
	class CspmNearbyMapSettings{
		
		private $plugin_path;
		private $plugin_url;
		
		private static $_this;	
		
		public $object_type;
		protected $options_key;
		protected $plugin_version;
		
		public $plugin_settings = array();
				
		public $toggle_before_row;
		public $toggle_after_row;
						
		function __construct($atts = array()){
			
			extract( wp_parse_args( $atts, array(
				'plugin_path' => '', 
				'plugin_url' => '',
				'object_type' => '',
				'option_key' => '',
				'version' => '',
				'plugin_settings' => array(),
			)));
             
			self::$_this = $this;       
				           
			$this->plugin_path = $plugin_path;
			$this->plugin_url = $plugin_url;
			$this->object_type = $object_type;
			$this->options_key = $option_key;
			$this->plugin_version = $version;
			$this->plugin_settings = $plugin_settings;

			if (!class_exists('CSProgressMap'))
				return; 
			
			$CSProgressMap = CSProgressMap::this();
			
			/**
			 * Include all required Libraries for this metabox (included in "Progress Map") */
			 
			$libs_path = array(
				'cmb2' => 'admin/libs/metabox/init.php',
				'cmb2-tabs' => 'admin/libs/metabox-tabs/cmb2-tabs.class.php',
				'cmb2-radio-image' => 'admin/libs/metabox-radio-image/metabox-radio-image.php',
				'cmb2-field-select2' => 'admin/libs/metabox-field-select2/cmb-field-select2.php',	
			);
				
				foreach($libs_path as $lib_file_path){
					if(file_exists($CSProgressMap->cspm_plugin_path . $lib_file_path))
						require_once $CSProgressMap->cspm_plugin_path . $lib_file_path;
				}
				
			/**
			 * Other required Libraries for this metabox included inside this plugin */
			 
			$libs_path = array(
				'cmb2-field-unit' => 'admin/libs/metabox-field-unit/cmb2-field-unit.php',
			);
				
				foreach($libs_path as $lib_file_path){
					if(file_exists($this->plugin_path . $lib_file_path))
						require_once $this->plugin_path . $lib_file_path;
				}
						
			add_action('cmb2_admin_init', array($this, 'cspmnm_settings_page'));
			
			/**
			 * Call .js and .css files */
			 
			add_filter( 'cmb2_enqueue_js', array($this, 'cspmnm_scripts') );				
			
			$this->toggle_before_row = '<div class="postbox cmb-row cmb-grouping-organizer closed">
									 	<div class="cmbhandle" title="Click to toggle"><br></div>               
									 	<h3 class="cmb-group-organizer-title cmbhandle-title" style="padding: 11px 15px !important;">[title]</h3>
										<div class="inside">';
													
			$this->toggle_after_row = '</div></div>';

		}
	

		static function this() {
			
			return self::$_this;
		
		}

	
		function cspmnm_scripts(){

			if (!class_exists('CSProgressMap'))
				return; 
			
			$CSProgressMap = CSProgressMap::this();
			
			if(isset($_GET['page']) && $_GET['page'] == $this->options_key){
				
				/**
				 * Our custom metaboxes CSS */
				
				wp_register_style('cspm-metabox-css', $CSProgressMap->cspm_plugin_url . 'admin/options-page/css/options-page-style.css');
				wp_enqueue_style('cspm-metabox-css');

			}
			
		}
	
		
		/**
		 * "Nearby Places" settings page.
		 * This will contain all the default settings needed for this plugin
		 *
		 * @since 3.0
		 */
		function cspmnm_settings_page(){
			
			$cspmnm_settings_page_options = array(
				'id' => 'cspmnm_settings_page',
				'title' => esc_html__('Nearby Places', 'cs_nearby_map'),
				'description' => '',
				'object_types' => array('options-page'),
				
				/*
				 * The following parameters are specific to the options-page box
				 * Several of these parameters are passed along to add_menu_page()/add_submenu_page(). */
				
				'option_key' => $this->options_key,
				'menu_title' => esc_html__('Nearby Places', 'cs_nearby_map'), // Falls back to 'title' (above).
				'icon_url' => '',				
				'parent_slug' => 'cspm_default_settings', // Make options page a submenu item of the themes menu.
				'capability' => 'manage_options', // Cap required to view options-page.
				//'position' => '99.2', // Menu position. Only applicable if 'parent_slug' is left empty.
				//'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
				'display_cb'   => array($this, 'cspmnm_settings_page_output'), // Override the options-page form output (CMB2_Hookup::options_page_output()).
				//'save_button' => esc_html__( 'Save Theme Options', 'cs_nearby_map' ), // The text for the options-page save button. Defaults to 'Save'.
				//'disable_settings_errors' => true, // On settings pages (not options-general.php sub-pages), allows disabling.
				//'message_cb' => 'yourprefix_options_page_message_callback',				
			);
		
			/**
			 * Create Progress Map settings page */
				 
			$cspmnm_settings_page = new_cmb2_box($cspmnm_settings_page_options);

			/**
			 * Display Progress Map settings fields */
		
			$this->cspmnm_settings_tabs($cspmnm_settings_page, $cspmnm_settings_page_options);
			
		}
		
		/** 
		 * This contains the HTML structre of the plugin settings page
		 *
		 * @since 3.0
		 */
		function cspmnm_settings_page_output($settings_page_obj){
        	
			echo '<div class="wrap cmb2-options-page option-'.$settings_page_obj->option_key.'">';
				
				/**
				 * Header */
				 
				echo $this->cspmnm_settings_page_header();
				
				echo '<div class="cspm_body">';
				
					echo '<form class="cspm-settings-form" action="'.esc_url(admin_url('admin-post.php')).'" method="POST" id="'.$settings_page_obj->cmb->cmb_id.'" enctype="multipart/form-data" encoding="multipart/form-data">';
						
						echo '<input type="hidden" name="action" value="'.esc_attr($settings_page_obj->option_key).'">';
						
						submit_button(esc_attr($settings_page_obj->cmb->prop('save_button')), 'primary', 'submit-cmb');
					
						$settings_page_obj->options_page_metabox();
						
						submit_button(esc_attr($settings_page_obj->cmb->prop('save_button')), 'primary', 'submit-cmb');
					
					echo '</form>';
								
				echo '</div>';
					
				/**
				 * Sidebar */
				 
				echo $this->cspmnm_settings_page_widget();
				
				/**
				 * Footer */
				 
				echo $this->cspmnm_settings_page_footer();
				
            echo '</div>';
				
			echo '<div style="clear:both;"></div>';
			
		}
		
		
		/**
		 * Plugin settings page header
		 *
		 * @since 3.0
		 */
		function cspmnm_settings_page_header(){
			
			$output = '<div class="cspm_header"><img src="'.$this->plugin_url.'/img/admin/logo.png" /></div>';
			
			return $output;
			
		}
		
		
		/**
		 * Plugin settings page sidebar
		 *
		 * @since 3.0
		 */
		function cspmnm_settings_page_widget(){						
			
            $output = '';
            
			return $output;
			
		}
		
				
		/**
		 * Plugin settings page footer
		 *
		 * @since 3.0
		 */
		function cspmnm_settings_page_footer(){
			
			$output = '<div class="cspm_footer">';
								
				$output .= '<div style="clear:both;"></div>';
				
				$output .= '<div class="cspm_copyright">&copy; All rights reserved <a target="_blank" class="cspm_blank_link" href="https://codespacing.com">CodeSpacing</a>. Nearby Places '.$this->plugin_version.'</div>';
			
			$output .= '</div>';
			
			return $output;
			
		}
		
		
		/**
		 * Buill all the tabs that contains "Progress Map" settings
		 *
		 * @since 3.0
		 */
		function cspmnm_settings_tabs($metabox_object, $metabox_options){
			
			/**
			 * Setting tabs */
			 
			$tabs_setting = array(
				'args' => $metabox_options,
				'tabs' => array()
			);
				
				/**
				 * Tabs array */
				 
				$cspm_tabs = array(
					
					/**
				 	 * Plugin General Settings */
					 					
					array(
						'id' => 'plugin_settings', 
						'title' => 'General settings', 
						'callback' => 'cspmnm_plugin_general_settings_fields'
					),
					
					/**
				 	 * Customize Settings */
					 					
					array(
						'id' => 'customize', 
						'title' => 'Customize', 
						'callback' => 'cspmnm_customize_fields'
					),										
					
				);
				
				foreach($cspm_tabs as $tab_data){
				 
					$tabs_setting['tabs'][] = array(
						'id'     => 'cspm_' . $tab_data['id'],
						'title'  => '<span class="cspm_tabs_menu_image"><img src="'.$this->plugin_url.'img/admin/'.str_replace('_', '-', $tab_data['id']).'.png" style="width:20px;" /></span> <span class="cspm_tabs_menu_item">'.esc_attr__( $tab_data['title'], 'cs_nearby_map' ).'</span>',						
						'fields' => call_user_func(array($this, $tab_data['callback'])),
					);
		
				}
			
			/**
			 * Set tabs */
			 
			$metabox_object->add_field( array(
				'id'   => 'cspmnm_settings_tabs',
				'type' => 'tabs',
				'tabs' => $tabs_setting,
				'options_page' => true,
			) );
			
			return $metabox_object;
			
		}
		
		
        function cspmnm_plugin_general_settings_fields(){
			
			$fields = array();
			
			$fields[] = array(
				'name' => 'General Settings',
				'desc' => '',
				'type' => 'title',
				'id'   => 'general_settings',
				'attributes' => array(
					'style' => 'font-size:20px; color:#008fed; font-weight:400;'
				),
			);
			
				$fields[] = array(
					'id' => 'distance_unit',
					'name' => __('Unit System', 'cs_nearby_map'),
					'desc' => __('Choose the unit system to use when displaying distance.', 'cs_nearby_map').'<br>'.
                        __('You can override this option in a shortcode by using the attribute <code>distance_unit</code>. 
                        <br />Possible values are <code>METRIC</code> and <code>IMPERIAL</code>. 
                        <br />Default to <code>METRIC</code>. 
                        <br />Usage example: <code>[cs_nearby_map distance_unit="IMPERIAL"]</code>', 'cs_nearby_map'),
					'type' => 'radio',
					'default' => 'METRIC',
					'options' => array(
						'METRIC' => 'Metric (Km)', 
                        'IMPERIAL' => 'Imperial (Miles)',
					)
				);		

                $fields[] = array(
                    'id' => 'radius',
                    'name' => __('Radius', 'cs_nearby_map'), 
                    'desc' => __('Choose the distance from the given location within which to search for Places, in meters. The maximum allowed value is 50000.','cs_nearby_map').'<br>'.
                        __('You can override this option in a shortcode by using the attribute <code>radius</code>.
                        <br />Possible value is a number from <code>50</code> to <code>50000</code> maximum.
                        <br />Default to <code>50000</code>.
                        <br />Usage example: <code>[cs_nearby_map radius="1000"]</code>', 'cs_nearby_map'),
                    'type' => 'text',
                    'default' => '50000',
                    'attributes' => array(
                        'type' => 'number',
                        'pattern' => '\d*',
                        'min' => '50',
                        'step' => '50',
                        'max' => '50000',
                    ),
                );		
			
				$fields[] = array(
					'id' => 'rankBy',
					'name' => __('Display Order', 'cs_nearby_map'),
					'desc' => __('Specifies the order in which results are listed.', 'cs_nearby_map').'<br>'.
                        __('Possible values are <code>prominence</code> and <code>distance</code>. <br>                        
                        * <code>PROMINENCE</code> (default). This option sorts results based on their importance. Ranking will favor prominent places within the set radius over nearby places that match but that are less prominent. Prominence can be affected by a place\'s ranking in Google\'s index, global popularity, and other factors. When used, the radius parameter is required.<br>
                        * <code>DISTANCE</code>. This option sorts results in ascending order by their distance from the specified location. Note that you cannot specify a custom radius if you use this option. Using this option may also result in displaying less results.<br>					                        
				        Default to <code>prominence</code>.
                        Usage example: <code>[cs_nearby_map rankby="distance"]</code>', 'cs_nearby_map'),
					'type' => 'radio',
					'default' => 'prominence',
					'options' => array(
				        'prominence' => 'Prominence (Importance)', 
				        'distance' => 'Distance', 
					)
				);		
            
                // Build list of place types from the custom JSON locations.
                $custom_types = function_exists( 'cspmnm_get_json_types' ) ? cspmnm_get_json_types() : array();

                // Default Google place types list
                $default_types = array(
                        'accounting' => 'Accounting',
                        'airport' => 'Airport',
                        'amusement_park' => 'Amusement park',
                        'aquarium' => 'Aquarium',
                        'art_gallery' => 'Art gallery',
                        'atm' => 'ATM',
                        'bakery' => 'Bakery',
                        'bank' => 'Bank',
                        'bar' => 'Bar',
                        'beauty_salon' => 'Beauty salon',
                        'bicycle_store' => 'Bicycle store',
                        'book_store' => 'Book store',
                        'bowling_alley' => 'Bowling alley',
                        'bus_station' => 'Bus station',
                        'cafe' => 'Cafe',
                        'campground' => 'Campground',
                        'car_dealer' => 'Car dealer',
                        'car_rental' => 'Car rental',
                        'car_repair' => 'Car repair',
                        'car_wash' => 'Car wash',
                        'casino' => 'Casino',
                        'cemetery' => 'Cemetery',
                        'church' => 'Church',
                        'city_hall' => 'City hall',
                        'clothing_store' => 'Clothing store',
                        'convenience_store' => 'Convenience store',
                        'courthouse' => 'Courthouse',
                        'dentist' => 'Dentist',
                        'department_store' => 'Department store',
                        'doctor' => 'Doctor',
                        'electrician' => 'Electrician',
                        'electronics_store' => 'Electronics store',
                        'embassy' => 'Embassy',
                        //'establishment' => 'Establishment',
                        //'finance' => 'Finance',
                        'fire_station' => 'Fire station',
                        'florist' => 'Florist',
                        //'food' => 'Food',
                        'funeral_home' => 'Funeral home',
                        'furniture_store' => 'Furniture store',
                        'gas_station' => 'Gas station',
                        //'general_contractor' => 'General contractor',
                        //'grocery_or_supermarket' => 'Grocery or supermarket',
                        'gym' => 'GYM',
                        'hair_care' => 'Hair care',
                        'hardware_store' => 'Hardware store',
                        //'health' => 'Health',
                        'hindu_temple' => 'Hindu temple',
                        'home_goods_store' => 'Home goods store',
                        'hospital' => 'Hospital',
                        'insurance_agency' => 'Insurance agency',
                        'jewelry_store' => 'Jewelry store',
                        'laundry' => 'Laundry',
                        'lawyer' => 'Lawyer',
                        'library' => 'Library',
                        'liquor_store' => 'Liquor store',
                        'local_government_office' => 'Local government office',
                        'locksmith' => 'Locksmith',
                        'lodging' => 'Lodging',
                        'meal_delivery' => 'Meal delivery',
                        'meal_takeaway' => 'Meal takeaway',
                        'mosque' => 'Mosque',
                        'movie_rental' => 'Movie rental',
                        'movie_theater' => 'Movie theater',
                        'moving_company' => 'Moving company',
                        'museum' => 'Museum',
                        'night_club' => 'Night club',
                        'painter' => 'Painter',
                        'park' => 'Park',
                        'parking' => 'Parking',
                        'pet_store' => 'Pet store',
                        'pharmacy' => 'Pharmacy',
                        'physiotherapist' => 'Physiotherapist',
                        //'place_of_worship' => 'Place of worship',
                        'plumber' => 'Plumber',
                        'police' => 'Police',
                        'post_office' => 'Post office',
                        'real_estate_agency' => 'Real estate agency',
                        'restaurant' => 'Restaurant',
                        'roofing_contractor' => 'Roofing contractor',
                        'rv_park' => 'RV park',
                        'school' => 'School',
                        'shoe_store' => 'Shoe store',
                        'shopping_mall' => 'Shopping mall',
                        'spa' => 'Spa',
                        'stadium' => 'Stadium',
                        'storage' => 'Storage',
                        'store' => 'Store',
                        'subway_station' => 'Subway station',
                        'synagogue' => 'Synagogue',
                        'taxi_stand' => 'Taxi stand',
                        'train_station' => 'Train station',
                        'travel_agency' => 'Travel agency',
                        'university' => 'University',
                        'veterinary_care' => 'Veterinary care',
                        'zoo' => 'Zoo',
                );

                // Use custom types if available
                $proximity_options = ! empty( $custom_types ) ? $custom_types : $default_types;
                $proximity_default = array_keys( $proximity_options );

                $fields[] = array(
                    'id' => 'proximity',
                    'name' => esc_attr__('Place Types', 'cs_nearby_map'),
                    'type' => 'pw_multiselect',
                    'desc' => __('Choose the place types & sort their order.<br>The plugin supports all the types in the Google Places API.', 'cs_nearby_map'),
                    'options' => $proximity_options,
                    'default' => $proximity_default,
                    'attributes' => array(
                        'placeholder' => 'Select the points of interest',
                    )
                );
                            
			return $fields;
			
        }
        
		
        function cspmnm_customize_fields(){
			
			$fields = array();
			
			$fields[] = array(
				'name' => 'Customize',
				'desc' => '',
				'type' => 'title',
				'id'   => 'customize',
				'attributes' => array(
					'style' => 'font-size:20px; color:#008fed; font-weight:400;'
				),
			);
				
                $fields[] = array(
					'id' => 'main_color',
					'name' => __('Main Color', 'cs_nearby_map'), 
					'desc' => __('Pick a color for the plugin.<br />Default to <code>#008fed</code>.', 'cs_nearby_map'),
					'type' => 'colorpicker',
					'default' => '#008fed',
				);
				
				$fields[] = array(
					'id' => 'hover_color',
                    'name'    => __('Hover Color', 'cs_nearby_map'), 
                    'desc' => __('Pick the hover color for the plugin.<br />Default to <code>#00aeff</code>.', 'cs_nearby_map'),                    
					'type' => 'colorpicker',
					'default' => '#00aeff',
				);
            
                $fields[] = array(
                    'id' => 'main_layout',
                    'name'    => __('Main Layout', 'cs_nearby_map'), 
                    'desc' => __('Select the map and the places alignment.', 'cs_nearby_map').'<br>'.
                        __('You can override the main layout in a shortcode using the attribute <code>layout</code><br>
                        Possible values are, <code>pl-mr</code>, <code>pr-ml</code> and <code>pt-mb</code>.<br>
                        Default to <code>pl-mr</code>.<br>
                        <code>pl-mr</code> = Places Left, Map Right<br>
                        <code>pr-ml</code> = Places Right, Map Left<br>
                        <code>pt-mb</code> = Places Top, Map Bottom<br>
                        Usage example: <code>[cs_nearby_map layout="pr-ml"]</code>', 'cs_nearby_map'),
                    'type' => 'radio_image',
                    'default' => 'pl-mr',
                    'options' => array(
                        'pl-mr' => 'Places Left, Map Right',
                        'pr-ml' => 'Places Right, Map Left',
                        'pt-mb' => 'Places Top, Map Bottom',
                    ),
                    'images_path' => $this->plugin_url,
                    'images' => array(
                        'pl-mr' => '/img/admin/2cl.png',
                        'pr-ml' => '/img/admin/2cr.png',				
                        'pt-mb' => '/img/admin/1col.png',				
                    )	
                );
                
                $fields[] = array(
					'id' => 'map_width',			
					'name' => __('Map width', 'cs_nearby_map'), 
					'desc' => __('Choose the width of the map', 'cs_nearby_map').'<br>'.
                        __('By default, the width of the map is 100%, which means that it will fit to the container\'s width. You can change the width to another value. Leave the width empty to simulate the value "100%".<br>
                        You can override the width in a shortcode by using the attributes <code>width</code>.
                        Usage example: <code>[cs_nearby_map width="100%"]</code><br>', 'cs_nearby_map'),				
					'type' => 'unit',
					'units' => array(
						'px' => 'px',
						'em' => 'em',
						'rem' => 'rem',
						'%' => '%',
					)
				);
            
                $fields[] = array(
					'id' => 'map_height',			
					'name' => __('Map height', 'cs_nearby_map'), 
					'desc' => __('Choose the height of the map', 'cs_nearby_map').'<br>'.
                        __('By default, the height of the map is "420px". For the sake of displaying all the elements properly, the height cannot be adjusted to something lower than "420px".<br />                        
                        You can override the height in a shortcode by using the attributes <code>height</code>.<br>
                        Usage example: <code>[cs_nearby_map height="400px"]</code><br>', 'cs_nearby_map'),				
					'type' => 'unit',
					'units' => array(
						'px' => 'px'
					)
				);
            
                $fields[] = array(
                    'id' => 'places_grid',
                    'name'    => __('Place types Layout', 'cs_nearby_map'), 
                    'desc' => __('Select the number of columns (place types) to display in each row.', 'cs_nearby_map').'<br>'.
                        __('By default, the grid system is set to display 3 columns on each row.<br>
                        You can override this option in a shortcode using the attribute <code>grid</code>. 
                        Possible values are <code>2cols</code>, <code>3cols</code>, <code>4cols</code> and <code>6cols</code>.<br> 
                        Default to <code>3cols</code>.<br>
                        Usage example: <code>[cs_nearby_map grid="4cols"]</code>', 'cs_nearby_map'),
                    'type' => 'radio_image',
                    'default' => '3cols',
                    'options' => array(
                        '2cols' => '2 Columns',
                        '3cols' => '3 Columns',
                        '4cols' => '4 Columns',
                        '6cols' => '6 Columns',
                    ),
                    'images_path' => $this->plugin_url,
                    'images' => array(
                        '2cols' => '/img/admin/2-col-portfolio.png',
                        '3cols' => '/img/admin/3-col-portfolio.png',				
                        '4cols' => '/img/admin/4-col-portfolio.png',				
                        '6cols' => '/img/admin/6-col-portfolio.png',				
                    )	
                );
            
			return $fields;
			
        }        
		
	}
	
}


/** Custom admin page to input JSON-defined nearby places */
add_action('admin_menu', function() {
    add_menu_page(
        'Saját Lokációk',
        'Saját Lokációk',
        'manage_options',
        'custom_nearby_locations',
        'render_custom_nearby_locations_page',
        'dashicons-location',
        81
    );
});

function render_custom_nearby_locations_page() {
    if ( isset( $_POST['save_custom_locations'] ) ) {
        $raw_json = wp_unslash( $_POST['custom_locations_json'] );
        $decoded  = json_decode( $raw_json, true );

        if ( is_array( $decoded ) ) {
            // Preserve UTF-8 characters so accents don't get converted to
            // \uXXXX sequences when saving the JSON string.
            $encoded = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE );
            update_option( 'custom_nearby_locations', $encoded );
            echo '<div class="updated"><p>Helyek elmentve!</p></div>';
        } else {
            echo '<div class="error"><p>Hibás JSON formátum!</p></div>';
        }
    }

    $saved_locations = get_option('custom_nearby_locations', '[]');
    ?>
    <div class="wrap">
        <h1>Saját Nearby Lokációk</h1>
        <p>JSON helyek megadása:</p>
        <form method="post">
            <textarea name="custom_locations_json" rows="15" cols="100"><?php echo esc_textarea($saved_locations); ?></textarea>
            <br><br>
            <input type="submit" name="save_custom_locations" class="button-primary" value="Mentés">
        </form>
        <hr>
        <p><strong>JSON példa:</strong></p>
        <pre>[
  {
    "name": "Koffein Service",
    "lat": 47.466,
    "lng": 19.032,
    "type": "bowling"
  },
  {
    "name": "Revital Gym",
    "lat": 47.500,
    "lng": 19.050,
    "type": "gym"
  }
]</pre>
    </div>
    <?php
}
