<?php
/**
 * Plugin Name: Easy Digital Downloads - Terms Per Product
 * Plugin URI: http://easydigitaldownloads.com/extension/terms-per-product
 * Description: Allow terms of use to be specified on a per-product basis
 * Author: Easy Digital Downloads 
 * Author URI: https://easydigitaldownloads.com
 * Version: 1.0.5
 * Text Domain: edd-terms-per-product
 * Domain Path: languages
*/

class EDD_Terms_Per_Product {

	function __construct() {

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'edd_meta_box_settings_fields', array( $this, 'metabox_field' ), 200 );
		add_action( 'edd_purchase_form_before_submit', array( $this, 'product_terms' ) );
		add_action( 'edd_checkout_error_checks', array( $this, 'error_checks' ), 10, 2 );

		add_filter( 'edd_metabox_fields_save', array( $this, 'fields_to_save' ) );
		add_filter( 'edd_metabox_save__edd_download_terms', array( $this, 'sanitize_terms_save' ) );

	}


	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$lang_dir = apply_filters( 'edd_terms_per_product_lang_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'edd-terms-per-product' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'edd-terms-per-product', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/edd-terms-per-product/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/edd folder
			load_textdomain( 'edd-terms-per-product', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/easy-digital-downloads/languages/ folder
			load_textdomain( 'edd-terms-per-product', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'edd-terms-per-product', false, $lang_dir );
		}
	}


	public function metabox_field( $post_id = 0 ) {
		$terms = get_post_meta( $post_id, '_edd_download_terms', true );
		?>
		<p>
			<strong><?php _e( 'Download Terms of Use', 'edd-terms-per-product' ); ?></strong>
		</p>
		<p>
			<textarea name="_edd_download_terms" id="edd_download_terms" rows="10" cols="50" class="large-text"><?php echo $terms; ?></textarea>
			<label for="edd_download_terms"><?php _e( 'Enter the terms of use for this product.', 'edd-terms-per-product' ); ?></label>
		</p>
		<?php
	}

	public function fields_to_save( $fields = array() ) {
		$fields[] = '_edd_download_terms';
		return $fields;
	}

	public function sanitize_terms_save( $data ) {
		return wp_kses( $data, array(
			'a' => array(
				'href' => array(),
				'title' => array()
			),
			'br' => array(),
			'em' => array(),
			'strong' => array()
			)
		);
	}


	public function product_terms() {
		$cart_items = edd_get_cart_contents();
		$displayed  = array();
		echo '<script type="text/javascript">jQuery(document).ready(function($){$(".edd_per_product_terms_links").unbind("click").bind("click", function(e) { e.preventDefault();e.stopPropagation();var terms = $(this).attr("href");var parent = $(this).parent();$(terms).slideToggle();parent.find("a").toggle();});});</script>';
		echo '<fieldset id="edd_terms_agreement">';
		foreach ( $cart_items as $key => $item ) {

			if( in_array( $item['id'], $displayed ) )
				continue; // ensure only unique items are shown

			$terms = get_post_meta( $item['id'], '_edd_download_terms', true );
			if( ! empty( $terms ) ) { ?>
				<div id="edd-<?php echo $item['id']; ?>-terms-wrap">
					<div id="edd_<?php echo $item['id']; ?>_terms" style="display:none;">
						<?php echo wpautop( $terms ); ?>
					</div>
					<div id="edd_show_<?php echo $item['id']; ?>_terms">
						<a href="#edd_<?php echo $item['id']; ?>_terms" class="edd_per_product_terms_links"><?php printf( __( 'Show Terms For %s', 'edd' ), get_post_field( 'post_title', $item['id'] ) ); ?></a>
						<a href="#edd_<?php echo $item['id']; ?>_terms" class="edd_per_product_terms_links" style="display:none;"><?php _e( 'Hide Terms', 'edd' ); ?></a>
					</div>
					<input name="edd_agree_to_terms_<?php echo $item['id']; ?>" class="required" type="checkbox" id="edd_agree_to_terms_<?php echo $item['id']; ?>" value="1"/>
					<label for="edd_agree_to_terms_<?php echo $item['id']; ?>">Agree to Terms</label>
				</div>
				<?php
				$displayed[] = $item['id'];
			}
		}
		echo '</fieldset>';
	}

	public function error_checks( $valid_data = array(), $post_data = array() ) {
		$cart_items = edd_get_cart_contents();
		foreach ( $cart_items as $key => $item ) {
			$terms = get_post_meta( $item['id'], '_edd_download_terms', true );
			if( ! isset( $post_data['edd_agree_to_terms_' . $item['id'] ] ) && ! empty( $terms ) ) {
				edd_set_error( 'agree_to_product_terms', __( 'You must agree to the terms of use for all products.', 'edd-terms-per-product' ) );
			}
		}
	}

}

// Instantiate the class
$edd_terms_per_product = new EDD_Terms_Per_Product;
