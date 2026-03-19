<?php
/**
 * ConvertKit Admin Product class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Registers a metabox on WooCommerce Products and saves its settings when the
 * Product is saved in the WordPress Administration interface.
 *
 * @package CKWC
 * @author ConvertKit
 */
class CKWC_Admin_Product extends CKWC_Admin_Post_Type {

	/**
	 * The Post Type to register the metabox and settings against.
	 *
	 * @since   1.5.9
	 *
	 * @var     string
	 */
	public $post_type = 'product';

	/**
	 * The Meta Key to store the settings for the Post Type.
	 *
	 * @since   2.1.0
	 *
	 * @var     string
	 */
	public $meta_key = 'ckwc_subscription';

}
