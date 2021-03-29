<?php
/**
 * Plugin Name: Easy Digital Downloads - Terms Per Product
 * Plugin URI: https://easydigitaldownloads.com/downloads/terms-per-product
 * Description: Allow terms of use to be specified on a per-product basis
 * Author: Sandhills Development, LLC
 * Author URI: https://sandhillsdev.com
 * Version: 1.0.7
 * Text Domain: edd-terms-per-product
 * Domain Path: languages
 */

class EDD_Terms_Per_Product {

	/**
	 * EDD_Terms_Per_Product constructor.
	 */
	function __construct() {

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'edd_meta_box_settings_fields', array( $this, 'metabox_field' ), 200 );
		add_action( 'edd_purchase_form_before_submit', array( $this, 'product_terms' ) );
		add_action( 'edd_checkout_error_checks', array( $this, 'error_checks' ), 10, 2 );

		add_filter( 'edd_metabox_fields_save', array( $this, 'fields_to_save' ) );
		add_filter( 'edd_metabox_save__edd_download_terms', array( $this, 'sanitize_terms_save' ) );

	}

	/**
	 * Loads the text domain
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$lang_dir = apply_filters( 'edd_terms_per_product_lang_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'edd-terms-per-product' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'edd-terms-per-product', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/edd-terms-per-product/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/edd-terms-per-product folder
			load_textdomain( 'edd-terms-per-product', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/edd-terms-per-product/languages/ folder
			load_textdomain( 'edd-terms-per-product', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'edd-terms-per-product', false, $lang_dir );
		}
	}

	/**
	 * Renders the download meta box.
	 *
	 * @param int $post_id
	 */
	public function metabox_field( $post_id = 0 ) {
		$terms = get_post_meta( $post_id, '_edd_download_terms', true );
		?>
		<p>
			<strong><?php printf( esc_html__( '%s Terms of Use:', 'edd-terms-per-product' ), esc_html( edd_get_label_singular() ) ); ?></strong>
		</p>
		<p>
			<textarea name="_edd_download_terms" id="edd_download_terms" rows="10" cols="50" class="large-text"><?php echo esc_textarea( $terms ); ?></textarea>
			<label for="edd_download_terms"><?php printf( esc_html__( 'Enter the terms of use for this %s:', 'edd-terms-per-product' ), edd_get_label_singular() ); ?></label>
		</p>
		<?php
	}

	/**
	 * Adds our meta key to the list of fields to save.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function fields_to_save( $fields = array() ) {
		$fields[] = '_edd_download_terms';

		return $fields;
	}

	/**
	 * Sanitizes the terms of use before saving.
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	public function sanitize_terms_save( $data ) {
		return wp_kses(
			$data,
			array(
				'a'      => array(
					'href'   => array(),
					'title'  => array(),
					'target' => array()
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array()
			)
		);
	}

	/**
	 * Displays the product terms on checkout.
	 */
	public function product_terms() {
		$has_terms = $this->has_terms();

		if ( $has_terms ) {
			echo '<script type="text/javascript">jQuery(document).ready(function($){$(".edd_per_product_terms_links").unbind("click").bind("click", function(e) { e.preventDefault();e.stopPropagation();var terms = $(this).attr("href");var parent = $(this).parent();$(terms).slideToggle();parent.find("a").toggle();});});</script>';
			echo '<fieldset id="edd_terms_agreement">' . $has_terms . '</fieldset>';
		}
	}

	/**
	 * Builds the terms markup based on items in the cart.
	 *
	 * @return string
	 */
	public function has_terms() {
		$cart_items = edd_get_cart_contents();
		$displayed  = array();

		ob_start();

		foreach ( $cart_items as $key => $item ) {
			if ( in_array( $item['id'], $displayed ) ) {
				// Ensure only unique items are shown.
				continue;
			}

			$terms = get_post_meta( $item['id'], '_edd_download_terms', true );
			if ( ! empty( $terms ) ) {
				?>
				<div id="edd-<?php echo esc_attr( $item['id'] ); ?>-terms-wrap">
					<div id="edd_<?php echo esc_attr( $item['id'] ); ?>_terms" style="display:none;">
						<?php echo wpautop( $terms ); ?>
					</div>
					<div id="edd_show_<?php echo esc_attr( $item['id'] ); ?>_terms">
						<a href="#edd_<?php echo esc_attr( $item['id'] ); ?>_terms" class="edd_per_product_terms_links">
							<?php printf( esc_html__( 'Show Terms For %s', 'edd-terms-per-product' ), esc_html( get_post_field( 'post_title', $item['id'] ) ) ); ?>
						</a>
						<a href="#edd_<?php echo esc_attr( $item['id'] ); ?>_terms" class="edd_per_product_terms_links" style="display:none;">
							<?php esc_html_e( 'Hide Terms', 'edd-terms-per-product' ); ?>
						</a>
					</div>
					<input name="edd_agree_to_terms_<?php echo esc_attr( $item['id'] ); ?>" class="required" type="checkbox" id="edd_agree_to_terms_<?php echo esc_attr( $item['id'] ); ?>" value="1"/>
					<label for="edd_agree_to_terms_<?php echo esc_attr( $item['id'] ); ?>"><?php esc_html_e( 'Agree to Terms', 'edd-terms-per-product' ); ?></label>
				</div>
				<?php
				$displayed[] = $item['id'];
			}
		}

		$terms = ob_get_clean();
		return $terms;
	}

	/**
	 * Checks for errors when the purchase form is submitted.
	 *
	 * @param array $valid_data
	 * @param array $post_data
	 */
	public function error_checks( $valid_data = array(), $post_data = array() ) {
		$cart_items = edd_get_cart_contents();
		foreach ( $cart_items as $key => $item ) {
			$terms = get_post_meta( $item['id'], '_edd_download_terms', true );
			if ( ! isset( $post_data[ 'edd_agree_to_terms_' . $item['id'] ] ) && ! empty( $terms ) ) {
				edd_set_error( 'agree_to_product_terms', __( 'You must agree to the terms of use for all products.', 'edd-terms-per-product' ) );
			}
		}
	}

}

// Instantiate the class
$edd_terms_per_product = new EDD_Terms_Per_Product;
