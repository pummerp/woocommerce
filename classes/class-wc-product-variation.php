<?php
/**
 * Product Variation Class
 *
 * The WooCommerce product variation class handles product variation data.
 *
 * @class 		WC_Product_Variation
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Product_Variation extends WC_Product {

	/** @var array Stores variation data (attributes) for the current variation. */
	var $variation_data;

	/** @var int ID of the variable product. */
	var $variation_id;

	/** @var bool True if the variation has a length. */
	var $variation_has_length;

	/** @var bool True if the variation has a width. */
	var $variation_has_width;

	/** @var bool True if the variation has a height. */
	var $variation_has_height;

	/** @var bool True if the variation has a weight. */
	var $variation_has_weight;

	/** @var bool True if the variation has a price. */
	var $variation_has_price;
	
	/** @var bool True if the variation has a regular price. */
	var $variation_has_regular_price;

	/** @var bool True if the variation has a sale price. */
	var $variation_has_sale_price;

	/** @var bool True if the variation has stock and is managing stock. */
	var $variation_has_stock;

	/** @var bool True if the variation has a sku. */
	var $variation_has_sku;

	/** @var string Stores the shipping class of the variation. */
	var $variation_shipping_class;

	/** @var int Stores the shipping class ID of the variation. */
	var $variation_shipping_class_id;

	/** @var bool True if the variation has a tax class. */
	var $variation_has_tax_class;

	/**
	 * Loads all product data from custom fields
	 *
	 * @access public
	 * @param int $variation_id ID of the variation to load
	 * @param int $parent_id (default: '') ID of the parent product
	 * @param array $parent_custom_fields (default: '') Array of the parent products meta data
	 * @return void
	 */
	function __construct( $variation_id, $parent_id = '', $parent_custom_fields = '' ) {

		$this->variation_id = intval( $variation_id );

		$product_custom_fields = get_post_custom( $this->variation_id );

		$this->variation_data = array();

		foreach ( $product_custom_fields as $name => $value ) {

			if ( ! strstr( $name, 'attribute_' ) ) continue;

			$this->variation_data[ $name ] = $value[0];

		}

		/* Get main product data from parent */
		$this->id = ( $parent_id > 0 ) ? intval( $parent_id ) : wp_get_post_parent_id( $this->variation_id );
		
		if ( ! $parent_custom_fields ) $parent_custom_fields = get_post_custom( $this->id );

		// Define the data we're going to load from the parent: Key => Default value
		$load_data = array(
			'sku'			=> '',
			'price' 		=> 0,
			'visibility'	=> 'hidden',
			'stock'			=> 0,
			'stock_status'	=> 'instock',
			'backorders'	=> 'no',
			'manage_stock'	=> 'no',
			'sale_price'	=> '',
			'regular_price' => '',
			'weight'		=> '',
			'length'		=> '',
			'width'			=> '',
			'height'		=> '',
			'tax_status'	=> 'taxable',
			'tax_class'		=> '',
			'upsell_ids'	=> array(),
			'crosssell_ids' => array()
		);

		// Load the data from the custom fields
		foreach ( $load_data as $key => $default )
			$this->$key = ( isset( $parent_custom_fields['_' . $key ][0] ) && $parent_custom_fields['_' . $key ][0] !== '' ) ? $parent_custom_fields['_' . $key ][0] : $default;

		$this->product_type = 'variable';

		$this->variation_has_sku = $this->variation_has_stock = $this->variation_has_weight = $this->variation_has_length = $this->variation_has_width = $this->variation_has_height = $this->variation_has_price = $this->variation_has_regular_price = $this->variation_has_sale_price = false;

		/* Override parent data with variation */
		if ( isset( $product_custom_fields['_sku'][0] ) && ! empty( $product_custom_fields['_sku'][0] ) ) {
			$this->variation_has_sku = true;
			$this->sku = $product_custom_fields['_sku'][0];
		}

		if ( isset( $product_custom_fields['_stock'][0] ) && $product_custom_fields['_stock'][0] !== '' ) {
			$this->variation_has_stock = true;
			$this->manage_stock = 'yes';
			$this->stock = $product_custom_fields['_stock'][0];
		}

		if ( isset( $product_custom_fields['_weight'][0] ) && $product_custom_fields['_weight'][0] !== '' ) {
			$this->variation_has_weight = true;
			$this->weight = $product_custom_fields['_weight'][0];
		}

		if ( isset( $product_custom_fields['_length'][0] ) && $product_custom_fields['_length'][0] !== '' ) {
			$this->variation_has_length = true;
			$this->length = $product_custom_fields['_length'][0];
		}

		if ( isset( $product_custom_fields['_width'][0] ) && $product_custom_fields['_width'][0] !== '' ) {
			$this->variation_has_width = true;
			$this->width = $product_custom_fields['_width'][0];
		}

		if ( isset( $product_custom_fields['_height'][0] ) && $product_custom_fields['_height'][0] !== '' ) {
			$this->variation_has_height = true;
			$this->height = $product_custom_fields['_height'][0];
		}
		
		if ( isset( $product_custom_fields['_downloadable'][0] ) && $product_custom_fields['_downloadable'][0] == 'yes' ) {
			$this->downloadable = 'yes';
		} else {
			$this->downloadable = 'no';
		}

		if ( isset( $product_custom_fields['_virtual'][0] ) && $product_custom_fields['_virtual'][0] == 'yes' ) {
			$this->virtual = 'yes';
		} else {
			$this->virtual = 'no';
		}

		if ( isset( $product_custom_fields['_tax_class'][0] ) ) {
			$this->variation_has_tax_class = true;
			$this->tax_class = $product_custom_fields['_tax_class'][0];
		}
		
		if ( isset( $product_custom_fields['_price'][0] ) && $product_custom_fields['_price'][0] !== '' ) {
			$this->variation_has_price = true;
			$this->price = $product_custom_fields['_price'][0];
		}
		
		if ( isset( $product_custom_fields['_regular_price'][0] ) && $product_custom_fields['_regular_price'][0] !== '' ) {
			$this->variation_has_regular_price = true;
			$this->regular_price = $product_custom_fields['_regular_price'][0];
		}
		
		if ( isset( $product_custom_fields['_sale_price'][0] ) && $product_custom_fields['_sale_price'][0] !== '' ) {
			$this->variation_has_sale_price = true;
			$this->sale_price = $product_custom_fields['_sale_price'][0];
		}
		
		// Backwards compat for prices
		if ( $this->variation_has_price && ! $this->variation_has_regular_price ) {
			update_post_meta( $this->variation_id, '_regular_price', $this->price );
			$this->variation_has_regular_price = true;
			$this->regular_price = $this->price;
			
			if ( $this->variation_has_sale_price && $this->sale_price < $this->regular_price ) {
				update_post_meta( $this->variation_id, '_price', $this->sale_price );
				$this->price = $this->sale_price;
			}
		}
		
		if ( isset( $product_custom_fields['_sale_price_dates_from'][0] ) )
			$this->sale_price_dates_from = $product_custom_fields['_sale_price_dates_from'][0];
			
		if ( isset( $product_custom_fields['_sale_price_dates_to'][0] ) )
			$this->sale_price_dates_from = $product_custom_fields['_sale_price_dates_to'][0];
		
		$this->total_stock = $this->stock;
		
		// Check sale dates
		$this->check_sale_price();
	}


	/**
	 * Returns whether or not the variation is visible.
	 *
	 * @access public
	 * @return bool
	 */
	function is_visible() {

		$visible = true;

		// Out of stock visibility
		if ( get_option('woocommerce_hide_out_of_stock_items') == 'yes' && ! $this->is_in_stock() )
			$visible = false;

		return apply_filters( 'woocommerce_product_is_visible', $visible, $this->id );
	}


	/**
	 * Returns whether or not the variations parent is visible.
	 *
	 * @access public
	 * @return bool
	 */
	function parent_is_visible() {
		return parent::is_visible();
	}

	/**
     * Get variation ID
     *
     * @return int
     */
    function get_variation_id() {
        return absint( $this->variation_id );
    }

    /**
     * Get variation attribute values
     *
     * @return array of attributes and their values for this variation
     */
    function get_variation_attributes() {
        return $this->variation_data;
    }

	/**
     * Get variation attribute values
     *
     * @return string containing the formatted price
     */
	function get_price_html() {
		if ( $this->variation_has_price ) {
			$price = '';

			if ( $this->price !== '' ) {
				if ( $this->price == $this->sale_price ) {
					$price .= '<del>' . woocommerce_price( $this->regular_price ) . '</del> <ins>' . woocommerce_price( $this->sale_price ) . '</ins>';
					$price = apply_filters( 'woocommerce_variation_sale_price_html', $price, $this );
				} else {
					$price .= woocommerce_price( $this->price );
					$price = apply_filters( 'woocommerce_variation_price_html', $price, $this );
				}
			}

			return $price;
		} else {
			return woocommerce_price( parent::get_price() );
		}
	}


    /**
     * Gets the main product image.
     *
     * @access public
     * @param string $size (default: 'shop_thumbnail')
     * @return string
     */
    function get_image( $size = 'shop_thumbnail' ) {
    	global $woocommerce;

    	$image = '';

    	if ( $this->variation_id && has_post_thumbnail( $this->variation_id ) ) {
			$image = get_the_post_thumbnail( $this->variation_id, $size );
		} elseif ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size );
		} elseif ( $parent_id = wp_get_post_parent_id( $this->id ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size );
		} else {
			$image = '<img src="' . woocommerce_placeholder_img_src() . '" alt="Placeholder" width="' . $woocommerce->get_image_size( 'shop_thumbnail_image_width' ) . '" height="' . $woocommerce->get_image_size( 'shop_thumbnail_image_height' ) . '" />';
		}

		return $image;
    }


	/**
	 * Reduce stock level of the product.
	 *
	 * @access public
	 * @param int $by (default: 1) Amount to reduce by
	 * @return int stock level
	 */
	function reduce_stock( $by = 1 ) {
		global $woocommerce;

		if ( $this->variation_has_stock ) {
			if ( $this->managing_stock() ) {

				$this->stock 		= $this->stock - $by;
				$this->total_stock 	= $this->total_stock - $by;
				update_post_meta( $this->variation_id, '_stock', $this->stock );
				$woocommerce->clear_product_transients( $this->id ); // Clear transient

				// Check parents out of stock attribute
				if ( ! $this->is_in_stock() ) {

					// Check parent
					$parent_product = new WC_Product( $this->id );

					// Only continue if the parent has backorders off
					if ( ! $parent_product->backorders_allowed() && $parent_product->get_total_stock() <= 0 ) {

						update_post_meta( $this->id, '_stock_status', 'outofstock' );

					}

				}

				return apply_filters( 'woocommerce_stock_amount', $this->stock );
			}
		} else {
			return parent::reduce_stock( $by );
		}
	}


	/**
	 * Increase stock level of the product.
	 *
	 * @access public
	 * @param int $by (default: 1) Amount to increase by
	 * @return int stock level
	 */
	function increase_stock( $by = 1 ) {
		global $woocommerce;

		if ($this->variation_has_stock) :
			if ($this->managing_stock()) :

				$this->stock 		= $this->stock + $by;
				$this->total_stock 	= $this->total_stock + $by;
				update_post_meta( $this->variation_id, '_stock', $this->stock );
				$woocommerce->clear_product_transients( $this->id ); // Clear transient

				// Parents out of stock attribute
				if ( $this->is_in_stock() )
					update_post_meta( $this->id, '_stock_status', 'instock' );

				return apply_filters( 'woocommerce_stock_amount', $this->stock );
			endif;
		else :
			return parent::increase_stock( $by );
		endif;
	}


	/**
	 * Get the shipping class, and if not set, get the shipping class of the parent.
	 *
	 * @access public
	 * @return string
	 */
	function get_shipping_class() {
		if ( ! $this->variation_shipping_class ) {
			$classes = get_the_terms( $this->variation_id, 'product_shipping_class' );
			
			if ( $classes && ! is_wp_error( $classes ) ) {
				$this->variation_shipping_class = esc_attr( current( $classes )->slug );
			} else {
				$this->variation_shipping_class = parent::get_shipping_class();
			}
		}

		return $this->variation_shipping_class;
	}


	/**
	 * Returns the product shipping class ID.
	 *
	 * @access public
	 * @return int
	 */
	function get_shipping_class_id() {
		if ( ! $this->variation_shipping_class_id ) {

			$classes = get_the_terms( $this->variation_id, 'product_shipping_class' );

			if ( $classes && ! is_wp_error( $classes ) )
				$this->variation_shipping_class_id = current( $classes )->term_id;
			else
				$this->variation_shipping_class_id = parent::get_shipping_class_id();

		}
		return absint( $this->variation_shipping_class_id );
	}

	/**
	 * Get file download path identified by $download_id
	 * 
	 * @access public
	 * @param string $download_id file identifier
	 * @return array
	 */
	function get_file_download_path( $download_id ) {
		
		$file_path = '';
		$file_paths = apply_filters( 'woocommerce_file_download_paths', get_post_meta( $this->variation_id, '_file_paths', true ), $this->variation_id, null, null );

		if ( ! $download_id && count( $file_paths ) == 1 ) {
			// backwards compatibility for old-style download URLs and template files
			$file_path = array_shift( $file_paths );
		} elseif ( isset( $file_paths[ $download_id ] ) ) {
			$file_path = $file_paths[ $download_id ];
		}

		// allow overriding based on the particular file being requested
		return apply_filters( 'woocommerce_file_download_path', $file_path, $this->variation_id, $download_id );
	}
	
    /**
     * Checks sale data to see if the product is due to go on sale/sale has expired, and updates the main price.
     *
     * @access public
     * @return void
     */
    function check_sale_price() {

    	if ( $this->sale_price_dates_from && $this->sale_price_dates_from < current_time('timestamp') ) {

    		if ( $this->sale_price && $this->price !== $this->sale_price ) {

    			// Update price
    			$this->price = $this->sale_price;
    			update_post_meta( $this->variation_id, '_price', $this->price );

    			// Variable product prices and sale status are affected by children
    			$this->variable_product_sync();
    		}

    	}

    	if ( $this->sale_price_dates_to && $this->sale_price_dates_to < current_time('timestamp') ) {

    		if ( $this->regular_price && $this->price !== $this->regular_price ) {

    			$this->price = $this->regular_price;
    			update_post_meta( $this->variation_id, '_price', $this->price );

				// Sale has expired - clear the schedule boxes
				update_post_meta( $this->variation_id, '_sale_price', '' );
				update_post_meta( $this->variation_id, '_sale_price_dates_from', '' );
				update_post_meta( $this->variation_id, '_sale_price_dates_to', '' );

				// Variable product prices and sale status are affected by children
    			$this->variable_product_sync();
			}

    	}
    }
}