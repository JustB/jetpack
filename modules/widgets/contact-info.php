<?php

if ( ! class_exists( 'Jetpack_Contact_Info_Widget' ) ) {

	//register Contact_Info_Widget widget
	function jetpack_contact_info_widget_init() {
		register_widget( 'Jetpack_Contact_Info_Widget' );
	}

	add_action( 'widgets_init', 'jetpack_contact_info_widget_init' );

	/**
	 * Makes a custom Widget for displaying Resturant Location, Hours and Contact Info available.
	 *
	 * @package WordPress
	 */
	class Jetpack_Contact_Info_Widget extends WP_Widget {

		/**
		 * Constructor
		 */
		function __construct() {
			$widget_ops = array(
				'classname' => 'widget_contact_info',
				'description' => __( 'Display your location, hours, and contact information.', 'jetpack' ),
				'customize_selective_refresh' => true,
			);
			parent::__construct(
				'widget_contact_info',
				/** This filter is documented in modules/widgets/facebook-likebox.php */
				apply_filters( 'jetpack_widget_name', __( 'Contact Info', 'jetpack' ) ),
				$widget_ops
			);
			$this->alt_option_name = 'widget_contact_info';

			if ( is_customize_preview() ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			}
		}

		/**
		 * Enqueue scripts and styles.
		 */
		public function enqueue_scripts() {
			$google_url = 'https://maps.googleapis.com/maps/api/js';
			/**
			 * Set a Google Maps API Key.
			 *
			 * @since 4.1.0
			 *
			 * @param string $key Google Maps API Key
			 */
			$key = apply_filters( 'jetpack_google_maps_api_key', null );

			if ( ! empty( $key ) ) {
					$google_url = add_query_arg( 'key', $key, $google_url );
				}

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'google-maps', esc_url( $google_url, null, null ) );
			wp_enqueue_script( 'contact-info-map-js', plugins_url( 'contact-info/contact-info-map.js', __FILE__ ), array( 'jquery', 'google-maps' ), 20150127 );
			wp_enqueue_style( 'contact-info-map-css', plugins_url( 'contact-info/contact-info-map.css', __FILE__ ), null, 20150127 );
		}

		/**
		 * Return an associative array of default values
		 *
		 * These values are used in new widgets.
		 *
		 * @return array Array of default values for the Widget's options
		 */
		public function defaults() {
			return array(
				'title'   => __( 'Hours & Info', 'jetpack' ),
				'address' => __( "3999 Mission Boulevard,\nSan Diego CA 92109", 'jetpack' ),
				'phone'   => _x( '1-202-555-1212', 'Example of a phone number', 'jetpack' ),
				'hours'   => __( "Lunch: 11am - 2pm \nDinner: M-Th 5pm - 11pm, Fri-Sat:5pm - 1am", 'jetpack' ),
				'showmap' => 1,
				'lat'     => null,
				'lon'     => null
			);
		}

		/**
		 * Outputs the HTML for this widget.
		 *
		 * @param array $args     An array of standard parameters for widgets in this theme
		 * @param array $instance An array of settings for this widget instance
		 *
		 * @return void Echoes it's output
		 **/
		function widget( $args, $instance ) {
			$instance = wp_parse_args( $instance, $this->defaults() );

			echo $args['before_widget'];

			if ( '' != $instance['title'] ) {
				echo $args['before_title'] . $instance['title'] . $args['after_title'];
			}

			/**
			 * Fires at the beginning of the Contact Info widget, after the title.
			 *
			 * @module widgets
			 *
			 * @since 3.9.2
			 */
			do_action( 'jetpack_contact_info_widget_start' );

			if ( '' != $instance['address'] ) {

				$showmap = $instance['showmap'];

				if ( $showmap && $this->has_good_map( $instance ) ) {

					$lat = $instance['lat'];
					$lon = $instance['lon'];

					echo $this->build_map( $lat, $lon );
				}

				$map_link = $this->build_map_link( $instance['address'] );

				echo '<div class="confit-address"><a href="' . esc_url( $map_link ) . '" target="_blank">' . str_replace( "\n", "<br/>", esc_html( $instance['address'] ) ) . "</a></div>";
			}

			if ( '' != $instance['phone'] ) {
				if ( wp_is_mobile() ) {
					echo '<div class="confit-phone"><a href="' . esc_url( 'tel:' . $instance['phone'] ) . '">' . esc_html( $instance['phone'] ) . "</a></div>";
				}
				else {
					echo '<div class="confit-phone">' . esc_html( $instance['phone'] ) . '</div>';
				}
			}

			if ( '' != $instance['hours'] ) {
				echo '<div class="confit-hours">' . str_replace( "\n", "<br/>", esc_html( $instance['hours'] ) ) . "</div>";
			}

			/**
			 * Fires at the end of Contact Info widget.
			 *
			 * @module widgets
			 *
			 * @since 3.9.2
			 */
			do_action( 'jetpack_contact_info_widget_end' );

			echo $args['after_widget'];
		}


		/**
		 * Deals with the settings when they are saved by the admin. Here is
		 * where any validation should be dealt with.
		 *
		 * @param array $new_instance New configuration values
		 * @param array $old_instance Old configuration values
		 *
		 * @return array
		 */
		function update( $new_instance, $old_instance ) {
			$update_lat_lon = false;
			if (
				! isset( $old_instance['address'] ) ||
				$this->urlencode_address( $old_instance['address'] ) != $this->urlencode_address( $new_instance['address'] )
			) {
				$update_lat_lon = true;
			}

			$instance            = array();
			$instance['title']   = wp_kses( $new_instance['title'], array() );
			$instance['address'] = wp_kses( $new_instance['address'], array() );
			$instance['phone']   = wp_kses( $new_instance['phone'], array() );
			$instance['hours']   = wp_kses( $new_instance['hours'], array() );
			$instance['lat']     = isset( $old_instance['lat'] ) ? floatval( $old_instance['lat'] ) : 0;
			$instance['lon']     = isset( $old_instance['lon'] ) ? floatval( $old_instance['lon'] ) : 0;

			if ( ! $instance['lat'] || ! $instance['lon'] ) {
				$update_lat_lon = true;
			}

			if ( $instance['address'] && $update_lat_lon ) {

				// Get the lat/lon of the user specified address.
				$address = $this->urlencode_address( $instance['address'] );
				$path    = "http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=" . $address;
				/** This action is documented in modules/widgets/contact-info.php */
				$key = apply_filters( 'jetpack_google_maps_api_key', null );

				if ( ! empty( $key ) ) {
					$path = add_query_arg( 'key', $key, $path );
				}
				$json    = wp_remote_retrieve_body( wp_remote_get( esc_url( $path, null, null ) ) );

				if ( ! $json ) {
					// The read failed :(
					esc_html_e( "There was a problem getting the data to display this address on a map.  Please refresh your browser and try again.", 'jetpack' );
					die();
				}

				$json_obj = json_decode( $json );

				if ( "ZERO_RESULTS" == $json_obj->status ) {
					// The address supplied does not have a matching lat / lon.
					// No map is available.
					$instance['lat'] = "0";
					$instance['lon'] = "0";
				}
				else {

					$loc = $json_obj->results[0]->geometry->location;

					$lat = floatval( $loc->lat );
					$lon = floatval( $loc->lng );

					$instance['lat'] = "$lat";
					$instance['lon'] = "$lon";
				}
			}

			if ( ! isset( $new_instance['showmap'] ) ) {
				$instance['showmap'] = 0;
			}
			else {
				$instance['showmap'] = intval( $new_instance['showmap'] );
			}

			return $instance;
		}


		/**
		 * Displays the form for this widget on the Widgets page of the WP Admin area.
		 *
		 * @param array $instance Instance configuration.
		 *
		 * @return void
		 */
		function form( $instance ) {
			$instance = wp_parse_args( $instance, $this->defaults() );
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'jetpack' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'address' ) ); ?>"><?php esc_html_e( 'Address:', 'jetpack' ); ?></label>
				<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'address' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'address' ) ); ?>"><?php echo esc_textarea( $instance['address'] ); ?></textarea>
				<?php
				if ( $this->has_good_map( $instance ) ) {
					?>
					<input class="" id="<?php echo esc_attr( $this->get_field_id( 'showmap' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'showmap' ) ); ?>" value="1" type="checkbox" <?php checked( $instance['showmap'], 1 ); ?> />
					<label for="<?php echo esc_attr( $this->get_field_id( 'showmap' ) ); ?>"><?php esc_html_e( 'Show map', 'jetpack' ); ?></label>
					<?php
				}
				else {
					?>
					<span class="error-message"><?php _e( 'Sorry. We can not plot this address. A map will not be displayed. Is the address formatted correctly?', 'jetpack' ); ?></span>
					<input id="<?php echo esc_attr( $this->get_field_id( 'showmap' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'showmap' ) ); ?>" value="<?php echo( intval( $instance['showmap'] ) ); ?>" type="hidden" />
					<?php
				}
				?>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'phone' ) ); ?>"><?php esc_html_e( 'Phone:', 'jetpack' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'phone' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'phone' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['phone'] ); ?>" />
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hours' ) ); ?>"><?php esc_html_e( 'Hours:', 'jetpack' ); ?></label>
				<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hours' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hours' ) ); ?>"><?php echo esc_textarea( $instance['hours'] ); ?></textarea>
			</p>

			<?php
		}


		/**
		 * Generate a Google Maps link for the supplied address.
		 *
		 * @param string $address Address to link to.
		 *
		 * @return string
		 */
		function build_map_link( $address ) {
			// Google map urls have lots of available params but zoom (z) and query (q) are enough.
			return "http://maps.google.com/maps?z=16&q=" . $this->urlencode_address( $address );
		}


		/**
		 * Builds map display HTML code from the supplied latitude and longitude.
		 *
		 * @param float $lat Map Latitude
		 * @param float $lon Map Longitude
		 *
		 * @return string HTML of the map
		 */
		function build_map( $lat, $lon ) {
			$this->enqueue_scripts();

			$lat  = esc_attr( $lat );
			$lon  = esc_attr( $lon );
			$html = <<<EOT
				<div class="contact-map">
				<input type="hidden" class="contact-info-map-lat" value="$lat" />
				<input type="hidden" class="contact-info-map-lon" value="$lon" />
				<div class="contact-info-map-canvas"></div></div>
EOT;

			return $html;
		}

		/**
		 * Encode an URL
		 *
		 * @param string $address The URL to encode
		 *
		 * @return string The encoded URL
		 */
		function urlencode_address( $address ) {

			$address = strtolower( $address );
			$address = preg_replace( "/\s+/", " ", trim( $address ) ); // Get rid of any unwanted whitespace
			$address = str_ireplace( " ", "+", $address ); // Use + not %20
			urlencode( $address );

			return $address;
		}

		/**
		 * Check if the instance has a valid Map location.
		 *
		 * @param array $instance Widget instance configuration.
		 *
		 * @return bool Whether or not there is a valid map.
		 */
		function has_good_map( $instance ) {
			// The lat and lon of an address that could not be plotted will have values of 0 and 0.
			return ! ( "0" == $instance['lat'] && "0" == $instance['lon'] );
		}

	}

}
