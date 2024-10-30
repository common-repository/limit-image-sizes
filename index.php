<?php
/*
Plugin Name: Limit Image Sizes
Plugin URI: https://github.com/DNACode-ca/limit-image-sizes
Description: Limit the amount of image sizes being registered on your website (and save disk space!)
Version: 1.0.0
Author: DNA Code
Author URI: https://dnacode.ca
Text Domain: limit-images
Domain Path: /languages
*/

use Carbon_Fields\Container;
use Carbon_Fields\Field;

if ( ! class_exists( "Limit_Images" ) ) {
	class Limit_Images {

		public $page_slug = '';
		public $page_file = 'limit-options';
		public $limited_images_option = 'limit_disabled_images';

		public function __construct() {
			add_action( 'after_setup_theme', array( $this, 'carbon_limit_load' ) );
			add_action( 'carbon_fields_register_fields', array( $this, 'limit_images_options' ) );

			add_action( 'init', array( $this, 'limit_alter_registered_images' ) );
			add_action( 'init', array( $this, 'limit_options_saved' ) );

			add_action( 'admin_head', array( $this, 'limit_page_styling' ) );

			add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
		}

		/**
		 * Boot up Carbon Fields <3
		 */
		public function carbon_limit_load() {
			require_once( 'vendor/autoload.php' );
			\Carbon_Fields\Carbon_Fields::boot();
		}

		/**
		 * Register the options
		 */
		public function limit_images_options() {
			global $_wp_additional_image_sizes;

			$image_sizes  = $_wp_additional_image_sizes;
			$image_fields = array();

			if ( ! empty( $image_sizes ) ) {
				foreach ( $image_sizes as $image_size => $image_info ) {
					$safe_image_size_title = $this->sanitize_image_name( $image_size );
					$safe_html_image_size  = esc_html( $image_size );

					$image_width  = ( $image_info['width'] ? $image_info['width'] : '&infin;' );
					$image_height = ( $image_info['height'] ? $image_info['height'] : '&infin;' );

					$image_fields[] = Field::make( 'checkbox', $safe_image_size_title, esc_html__( 'Disable', 'limit-images' ) . ': ' . $safe_html_image_size )
					                       ->set_help_text( esc_html__( 'width', 'limit-images' ) . ': ' . $image_width . ', ' .
					                                        esc_html__( 'height', 'limit-images' ) . ': ' . $image_height . ', ' .
					                                        esc_html__( 'cropping', 'limit-images' ) . ': ' .
					                                        ( isset( $image_info['crop'] ) && $image_info['crop'] ? '<span class="checkmark">&#x2713;</span>' : '<span class="crossmark">&#x2717;</span>' ) )
					;
				}
			}

			/**
			 * Add hidden field so we can save all options into single option
			 */
			$image_fields[] = Field::make( 'hidden', 'limit_page' )
			                       ->set_default_value( 'true' )
			;

			$this->page_slug = Container::make( 'theme_options', esc_html__( 'Limit Image Sizes', 'limit-images' ) )
			                            ->set_page_parent( 'upload.php' )
			                            ->set_page_file( $this->page_file )
			                            ->add_fields( $image_fields )
			;
		}

		/**
		 * Filter image sizes
		 */
		public function limit_alter_registered_images() {
			global $_wp_additional_image_sizes;

			// alter the image sizes
			$disabled_images = get_option( $this->limited_images_option );

			if ( ! empty( $disabled_images ) ) {
				foreach ( $disabled_images as $disabled_image ) {
					if ( isset( $_wp_additional_image_sizes[ $disabled_image ] ) ) {
						unset( $_wp_additional_image_sizes[ $disabled_image ] );
					}
				}
			}
		}

		/**
		 * Store the single options into a single array for easier usage when we go to limit the sizes
		 */
		public function limit_options_saved() {

			if ( isset( $_POST['_limit_page'] ) && $_POST['_limit_page'] ) {
				global $_wp_additional_image_sizes;

				$image_sizes     = $_wp_additional_image_sizes;
				$disabled_images = array();

				if ( ! empty( $image_sizes ) ) {
					foreach ( $image_sizes as $image_size => $image_info ) {
						$safe_image_size_title = $this->sanitize_image_name( $image_size );

						if ( isset( $_POST[ '_' . $safe_image_size_title ] ) && $_POST[ '_' . $safe_image_size_title ] == 'yes' ) {
							$disabled_images[] = $image_size;
						}
					}
				}

				update_option( $this->limited_images_option, $disabled_images );
			}
		}

		/**
		 * Boiled for safety
		 *
		 * @param $image_name
		 *
		 * @return string
		 */
		public function sanitize_image_name( $image_name ) {
			return sanitize_title_for_query( $image_name );
		}

		/**
		 * Get the image sizes
		 *
		 * @return mixed
		 */
		public function get_registered_images() {
			global $_wp_additional_image_sizes;

			return $_wp_additional_image_sizes;
		}

		public function add_settings_link( $links ) {
			$settings_link = '<a href="' . admin_url( 'upload.php?page=' . $this->page_file ) . '">' . __( 'Settings' ) . '</a>';
			array_push( $links, $settings_link );

			return $links;
		}

		/**
		 * Some quick and simple styling
		 */
		public function limit_page_styling() {
			if ( isset( $_GET['page'] ) && $_GET['page'] == $this->page_file ) {
				echo '<style>';
				echo '.checkmark { color: #5BC859; }';
				echo '.crossmark { color: #C84F45; }';
				echo '</style>';
			}
		}

	}
}

$Limit_Images = new Limit_Images();