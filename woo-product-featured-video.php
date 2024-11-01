<?php

/**
 *
 * @package   Woo Product Featured Video
 * @author    Abdelrahman Ashour < abdelrahman.ashour38@gmail.com >
 * @license   GPL-2.0+
 * @copyright 2018 Ash0ur


 * Plugin Name:  Woo Product Featured Video
 * Description:  A plugin that allows replacing the main product image with a video in shop page and single product page
 * Version:      1.0.0
 * Author:       Abdelrahman Ashour
 * Author URI:   https://profiles.wordpress.org/ashour
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * text domain:  woo-featured-video
 */

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}


if ( ! class_exists( 'Woo_product_featured_video' ) ) :

	class Woo_product_featured_video {


		public static function init() {

			$wooProductFeaturedVideo = new self();
		}

		public function __construct() {

			$this->isCheckedForShop = get_option( 'wooproductfeaturedvideo_settings_group' );

			$this->define_constants();
			$this->setup_actions();

		}



		public function define_constants() {

			   define( 'WPVF_BASE_URL', trailingslashit( plugins_url( 'woo-product-featured-video' ) ) );
			   define( 'WPVF_ASSETS_URL', trailingslashit( WPVF_BASE_URL . 'assets' ) );
			   define( 'WPVF_PATH', plugin_dir_path( __FILE__ ) );
		}

		public static function plugin_activated() {

			if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

				die( 'WooCommerce plugin must be activated' );

			}

		}

		public function admin_enqueue_global() {

			$screen = get_current_screen();

			if ( isset( $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' && $screen->post_type == 'product' ) {
				if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
					wp_enqueue_script( 'jquery' );
				}

				wp_enqueue_media();

				wp_enqueue_script( 'wpvf_actions', WPVF_ASSETS_URL . 'js/admin_actions.js', array( 'jquery' ), WC_VERSION, true );

				wp_localize_script(
					'wpvf_actions',
					'ajax_data',
					array(
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'wpvf_wp_ajax_nonce' ),
					)
				);

			}

		}

		public function frontend_enqueue_global() {

		}


		public function setup_actions() {

			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_global' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_global' ) );

			add_action( 'add_meta_boxes', array( $this, 'add_featured_video_meta_box' ) );

			add_action( 'save_post', array( $this, 'save_featured_video_url' ) );

			add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'add_featured_video_to_single_product_page' ), PHP_INT_MAX, 2 );

			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'add_featured_video_in_shop' ), 1 );

			add_action( 'admin_menu', array( $this, 'register_admin_page' ) );

			add_action( 'admin_init', array( $this, 'woofeaturedvideo_settings' ) );

		}





		public function register_admin_page() {
			if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$page = add_submenu_page( 'edit.php?post_type=product', 'Woo Featured Video', 'Woo Featured Video', 'manage_options', 'woo-product-featured-video', array( $this, 'render_admin_page' ) );

		}

		public function render_admin_page(){ ?>


		<form action="options.php" method="post">
			<?php
			settings_fields( 'wooproductfeaturedvideo-settings-group' );
			do_settings_sections( 'woo-product-featured-video' );
			submit_button();
			?>
		</form>

			<?php
		}



		public function woofeaturedvideo_settings() {

			register_setting(
				'wooproductfeaturedvideo-settings-group',
				'wooproductfeaturedvideo_settings_group',
				array(
					'type' => 'integer',
					'',
				)
			);

			add_settings_section( 'wooproductfeaturedvideo-main-section', 'Main Settings', array( $this, 'frontend_section_callback' ), 'woo-product-featured-video' );

			add_settings_field( 'wooproductfeaturedvideo-shop-page-enable', 'Enable Featured Video in shop page', array( $this, 'sop_page_enable_callback' ), 'woo-product-featured-video', 'wooproductfeaturedvideo-main-section' );

		}

		public function frontend_section_callback() {

		}


		public function sop_page_enable_callback() {
			echo '<input type="checkbox" name="wooproductfeaturedvideo_settings_group" value="1" ' . checked( 1, $this->isCheckedForShop, false ) . '/>';
		}





		public function add_featured_video_meta_box() {

			  add_meta_box( 'featured_video', __( 'Featured Video' ), array( $this, 'featured_video_callback' ), 'product', 'side', 'low' );

		}


		public function featured_video_callback( $post ) {

			global $post;
			wp_nonce_field( basename( __FILE__ ), 'woo_product_featured_video_nonce' );
			?>

			   <?php $IsFeaturedVideoEnabled = get_post_meta( $post->ID, 'woo_product_featured_video_enabled', true ); ?>

			<div class="featured-product-video-container">

				<p class="featured-product-video-enabled">
					<label for="woo_product_featured_video_enabled">Enable Featured Video</label>
					<input type="checkbox" name="woo_product_featured_video_enabled"  <?php echo ( $IsFeaturedVideoEnabled == 'on' ) ? 'checked' : ''; ?>  />
				</p>

					<?php
					$video_url = get_post_meta( $post->ID, 'woo_product_featured_video_url', true );
					if ( ! empty( $video_url ) ) :
						?>
					<video controls  >
						<source src = "<?php echo $video_url; ?>" type="video/<?php echo ltrim( strrchr( $video_url, '.' ), '.' ); ?>"  >
					</video>
					<?php endif; ?>

				<input type="hidden" name="woo_product_featured_video_url" value="<?php echo esc_url( get_post_meta( $post->ID, 'woo_product_featured_video_url', true ) ); ?>" />

			</div>

			<button class="button button-primary select_featured_video">Select Video</button>

			<?php
		}



		public function save_featured_video_url( $post_id ) {

			if ( isset( $_POST['woo_product_featured_video_nonce'] ) && wp_verify_nonce( $_POST['woo_product_featured_video_nonce'], basename( __FILE__ ) ) ) {

				if ( isset( $_POST['woo_product_featured_video_enabled'] ) && ( $_POST['woo_product_featured_video_enabled'] == 'on' ) ) {

					update_post_meta( $post_id, 'woo_product_featured_video_enabled', 'on' );

				} else {
					update_post_meta( $post_id, 'woo_product_featured_video_enabled', 'off' );
				}

				if ( isset( $_POST['woo_product_featured_video_url'] ) && ! empty( $_POST['woo_product_featured_video_url'] ) ) {

					$meta_key = 'woo_product_featured_video_url';

					$meta_value = get_post_meta( $post_id, $meta_key, true );

					$new_meta_value = esc_url_raw( $_POST['woo_product_featured_video_url'] );

					if ( $new_meta_value && '' == $meta_value ) {

						add_post_meta( $post_id, $meta_key, $new_meta_value, true );

					} elseif ( $new_meta_value && $new_meta_value != $meta_value ) {

						update_post_meta( $post_id, $meta_key, $new_meta_value );

					}
				}
			}
		}


		public function add_featured_video_in_shop() {

			global $product;

			$FeaturedVideoEnabled = get_post_meta( get_the_id(), 'woo_product_featured_video_enabled', true );

			$HasFeaturedVideo = get_post_meta( get_the_id(), 'woo_product_featured_video_url', true );

			if ( ( $this->isCheckedForShop == 1 ) && ( $FeaturedVideoEnabled == 'on' ) && ! empty( $HasFeaturedVideo ) ) {

				remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );

				$videoElement = '<video width=100% height="150" preload="metadata" controlsList="nodownload" controls ><source src="' . $HasFeaturedVideo . '#t=0.5" type="video/' . ltrim( strrchr( $HasFeaturedVideo, '.' ), '.' ) . '" /></video>';

				echo $videoElement;

			} else {
				add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
			}

		}


		public function add_featured_video_to_single_product_page( $product_main_image_html, $product_thumbnail_id ) {

			global $post,$product;
			$FeaturedVideoEnabled = get_post_meta( $product->get_id(), 'woo_product_featured_video_enabled', true );

			$HasFeaturedVideo = get_post_meta( $product->get_id(), 'woo_product_featured_video_url', true );

			if ( ( $FeaturedVideoEnabled == 'on' ) && ! empty( $HasFeaturedVideo ) && ! isset( $GLOBALS['ItsAlreadyAdded'] ) ) {

				$GLOBALS['ItsAlreadyAdded'] = 'yes';

				$videoElement = '<video width=100%  controls controlsList="nodownload" ><source src="' . $HasFeaturedVideo . '" type="video/' . ltrim( strrchr( $HasFeaturedVideo, '.' ), '.' ) . '" ></video>';

				$product_main_image_html = preg_replace( '/<img (.*?)>/', $videoElement, $product_main_image_html );

			}

			return $product_main_image_html;

		}




	}



	add_action( 'plugins_loaded', array( 'Woo_product_featured_video', 'init' ), 10 );

	register_activation_hook( __FILE__, array( 'Woo_product_featured_video', 'plugin_activated' ) );

endif;
