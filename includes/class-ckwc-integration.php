<?php
/**
 * Class CKWC_Integration
 */
class CKWC_Integration extends WC_Integration {

	/**
	 * Constructor
	 * 
	 * @since 	1.0.0
	 */
	public function __construct() {

		// Define the ID, Title and Description of this Integration.
		$this->id                 = 'ckwc';
		$this->method_title       = __( 'ConvertKit', 'woocommerce-convertkit' );
		$this->method_description = __( 'Enter your ConvertKit settings below to control how WooCommerce integrates with your ConvertKit account.', 'woocommerce-convertkit' );

		// Initialize form fields and settings.
		$this->init_form_fields();
		$this->init_settings();

		// Load Admin screens, save settings.
		if ( is_admin() ) {
			add_action( "woocommerce_update_options_integration_{$this->id}", array( $this, 'process_admin_options' ) );
			add_filter( "woocommerce_settings_api_sanitized_fields_{$this->id}", array( $this, 'sanitize_settings' ) );
		}

	}

	/**
	 * Defines the fields to display on this integration's screen at WooCommerce > Settings > Integration > ConvertKit.
	 * 
	 * Also loads JS for conditionally showing UI settings, based on the value of other settings.
	 * 
	 * @since 	1.4.2
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			// Enable/Disable entire integration.
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-convertkit' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable ConvertKit integration', 'woocommerce-convertkit' ),
				'default'     => 'no',
			),

			// API Key and Secret.
			'api_key' => array(
				'title'       => __( 'API Key', 'woocommerce-convertkit' ),
				'type'        => 'text',
				'default'     => '',
				// translators: this is a url to the ConvertKit site.
				'description' => sprintf( __( 'If you already have an account, <a href="%1$s" target="_blank">click here to retrieve your API Key</a>.<br />If you don\'t have a ConvertKit account, you can <a href="%2$s" target="_blank">sign up for one here</a>.', 'woocommerce-convertkit' ), esc_attr( esc_html( 'https://app.convertkit.com/account/edit' ) ), esc_attr( esc_url( 'http://convertkit.com/pricing/' ) ) ),
				'desc_tip'    => false,

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled',
			),
			'api_secret' => array(
				'title'       => __( 'API Secret', 'woocommerce-convertkit' ),
				'type'        => 'text',
				'default'     => '',
				// translators: this is a url to the ConvertKit site.
				'description' => sprintf( __( 'If you already have an account, <a href="%1$s" target="_blank">click here to retrieve your API Secret</a>.<br />If you don\'t have a ConvertKit account, you can <a href="%2$s" target="_blank">sign up for one here</a>', 'woocommerce-convertkit.' ), esc_attr( esc_html( 'https://app.convertkit.com/account/edit' ) ), esc_attr( esc_url( 'http://convertkit.com/pricing/' ) ) ),
				'desc_tip'    => false,

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled',
			),

			// Subscribe.
			'event' => array(
				'title'       => __( 'Subscribe Event', 'woocommerce-convertkit' ),
				'type'        => 'select',
				'default'     => 'pending',
				'description' => __( 'When should customers be subscribed?', 'woocommerce-convertkit' ),
				'desc_tip'    => false,
				'options'     => array(
					'pending'    => __( 'Order Created', 'woocommerce-convertkit' ),
					'processing' => __( 'Order Processing', 'woocommerce-convertkit' ),
					'completed'  => __( 'Order Completed', 'woocommerce-convertkit' ),
				),

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled subscribe',
			),
			'subscription' => array(
				'title'       => __( 'Form / Tag', 'woocommerce-convertkit' ),
				'type'        => 'subscription',
				'default'     => '',
				'description' => __( 'The ConvertKit Form or Tag to subscribe Customers to.', 'woocommerce-convertkit' ),

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled subscribe',
			),
			'name_format' => array(
				'title'       => __( 'Name Format', 'woocommerce-convertkit' ),
				'type'        => 'select',
				'default'     => 'first',
				'description' => __( 'How should the customer name be sent to ConvertKit?', 'woocommerce-convertkit' ),
				'desc_tip'    => false,
				'options'     => array(
					'first'   => __( 'Billing First Name', 'woocommerce-convertkit' ),
					'last'    => __( 'Billing Last Name', 'woocommerce-convertkit' ),
					'both'    => __( 'Billing First Name + Billing Last Name', 'woocommerce-convertkit' ),
				),

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled subscribe',
			),

			// Subscribe: Display Opt In Checkbox Settings.
			'display_opt_in' => array(
				'title'       => __( 'Opt-In Checkbox', 'woocommerce-convertkit' ),
				'label'       => __( 'Display an Opt-In checkbox on checkout', 'woocommerce-convertkit' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'If enabled, customers will <strong>only</strong> be subscribed if they check the "Opt-In" checkbox at checkout.<br />
									  If disabled, customers will <strong>always</strong> be subscribed at checkout.', 'woocommerce-convertkit' ),
				'desc_tip'    => false,

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled subscribe',
			),
			'opt_in_label' => array(
				'title'       => __( 'Opt-In Checkbox: Label', 'woocommerce-convertkit' ),
				'type'        => 'text',
				'default'     => __( 'I want to subscribe to the newsletter', 'woocommerce-convertkit' ),
				'description' => __( 'Optional (only used if the above field is checked): Customize the label next to the opt-in checkbox.', 'woocommerce-convertkit' ),
				'desc_tip'    => false,

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled subscribe display_opt_in',
			),
			'opt_in_status' => array(
				'title'       => __( 'Opt-In Checkbox: Default Status', 'woocommerce-convertkit' ),
				'type'        => 'select',
				'default'     => 'checked',
				'description' => __( 'The default state of the opt-in checkbox.', 'woocommerce-convertkit' ),
				'desc_tip'    => false,
				'options'     => array(
					'checked'   => __( 'Checked', 'woocommerce-convertkit' ),
					'unchecked' => __( 'Unchecked', 'woocommerce-convertkit' ),
				),

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled subscribe display_opt_in',
			),
			'opt_in_location' => array(
				'title'       => __( 'Opt-In Checkbox: Display Location', 'woocommerce-convertkit' ),
				'type'        => 'select',
				'default'     => 'billing',
				'description' => __( 'Where to display the opt-in checkbox on the checkout page (under Billing Info or Order Info).', 'woocommerce-convertkit' ),
				'desc_tip'    => false,
				'options'     => array(
					'billing' => __( 'Billing', 'woocommerce-convertkit' ),
					'order'   => __( 'Order', 'woocommerce-convertkit' ),
				),

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled subscribe display_opt_in',
			),

			// Purchase Data
			'send_purchases' => array(
				'title'       => __( 'Purchase Data', 'woocommerce-convertkit' ),
				'label'       => __( 'Send purchase data to ConvertKit.', 'woocommerce-convertkit' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'If enabled, the customer\'s order data <strong>will additionally</strong> be sent to ConvertKit <strong>if the customer opts in</strong>.<br />
									  If disabled, the customer\'s order data <strong>will not</strong> be sent to ConvertKit.', 'woocommerce-convertkit' ),
				'desc_tip'    => false,

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled subscribe',
			),
			
			// Debugging
			'debug' => array(
				'title'       => __( 'Debug', 'woocommerce-convertkit' ),
				'type'        => 'checkbox',
				'label'       => __( 'Write data to a log file', 'woocommerce-convertkit'),
				'description' => sprintf(
					/* translators: %1$s: URL to Log File, %2$s: View Log File text */
					'<a href="%1$s" target="_blank">%2$s</a>',
					admin_url( 'admin.php?page=wc-status&tab=logs&log_file=test-log-log' ),
					__( 'View Log File', 'woocommerce-convertkit' )
				),
				'default'     => 'no',

				// The setting name that needs to be checked/enabled for this setting to display. Used by JS to toggle visibility.
				'class'		  => 'enabled',
			),
		);

		// Load JS.
		ob_start();
		include( CKWC_PLUGIN_PATH . '/resources/backend/js/integration.js' );
		$code = ob_get_clean();

		wc_enqueue_js( $code );

	}

	/**
	 * Output HTML for the Form / Tag setting.
	 * 
	 * @since 	1.0.0
	 * 
	 * @param 	string 	$key 	Setting Field Key.
	 * @param 	array 	$data 	Setting Field Configuration.
	 */
	public function generate_subscription_html( $key, $data ) {

		$field    = $this->get_field_key( $key );
		$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'options'           => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		// Get Forms and Tags.
		$api = new ConvertKit_API( $this->get_option( 'api_key' ) );
		$forms = $api->get_forms();
		$tags = $api->get_tags();

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<select class="select <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?>>
						<option <?php selected( '', $this->get_option( $key ) ); ?> value=""><?php _e( 'Select a subscription option...', 'woocommerce-convertkit' ); ?></option>
						<optgroup label="Forms">
							<?php 
							foreach ( $forms as $form ) {
								?>
								<option value="form:<?php echo esc_attr( $form['id'] ); ?>"<?php selected( 'form:' . esc_attr( $form['id'] ), $this->get_option( $key ) ); ?>><?php echo $form['name']; ?></option>
								<?php
							}
							?>
						</optgroup>
						<optgroup label="Tags">
							<?php 
							foreach ( $tags as $tag ) {
								?>
								<option value="tag:<?php echo esc_attr( $tag['id'] ); ?>"<?php selected( 'tag:' . esc_attr( $tag['id'] ), $this->get_option( $key ) ); ?>><?php echo $tag['name']; ?></option>
								<?php
							}
							?>
						</optgroup>
					</select>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();

	}

	/**
	 * Sanitize settings before saving.
	 * 
	 * @since 	1.0.0
	 * 
	 * @param 	array 	$settings 	Plugin Settings.
	 * @return 	array 				Plugin Settings, sanitized
	 */
	public function sanitize_settings( $settings ) {

		$settings['api_key'] = trim( $settings['api_key'] );
		$settings['api_secret'] = trim( $settings['api_secret'] );
		return $settings;

	}

	/**
	 * Validate that the API Key is valid when saving settings.
	 * 
	 * @since 	1.0.0
	 * 
	 * @param 	string 	$key 		Setting Key.
	 * @param 	string 	$api_key 	API Key.
	 * @return 	string 				API Key
	 */
	public function validate_api_key_field( $key, $api_key ) {

		// Bail if the API Key has not been specified.
		if ( empty( $api_key ) ) {
			$this->errors[ $key ] = __( 'Please provide your ConvertKit API Key.', 'woocommerce-convertkit' );
			return $api_key;
		}

		// Get Forms to test that the API Key is valid.
		$api = new ConvertKit_API( $api_key );
		$forms = $api->get_forms();

		// Bail if an error occured.
		if ( is_wp_error( $forms ) ) {
			$this->errors[ $key ] = __( 'Your ConvertKit API Key appears to be invalid. Please double check the value.', 'woocommerce-convertkit' );
		}

		// Return API Key.
		return $api_key;

	}

	/**
	 * Whether the ConvertKit integration is enabled, meaning:
	 * - the 'Enable/Disable' option is checked,
	 * - an API Key and Secret are specified.
	 * 
	 * @since 	1.4.2
	 * 
	 * @return 	bool 	Integration Enabled.
	 */
	public function is_enabled() {

		return ( $this->get_option_bool( 'enabled' ) && $this->option_exists( 'api_key' ) && $this->option_exists( 'api_secret' ) );

	}

	/**
	 * Returns the given integration setting value, converting 'yes' to true
	 * and any other value to false.
	 * 
	 * @since 	1.4.2
	 * 
	 * @param 	string 	$name 	Setting Name
	 * @return 	bool
	 */
	public function get_option_bool( $name ) {

		$value = $this->get_option( $name );

		if ( $value === 'yes' ) {
			return true;
		}

		return false;

	}

	/**
	 * Returns whether the given setting value has a value, and isn't empty.
	 * 
	 * @since 	1.4.2
	 * 
	 * @param 	string 	$name 	Setting Name
	 * @return 	bool
	 */
	public function option_exists( $name ) {

		return ! empty( $this->get_option( $name ) );

	}

}