<?php
/**
 * Defines WP admin pointers used by the APM plugin.
 * Use the 'apm_admin_pointers' hook to add your own pointers.
 */
class ApmPointers{

	private static $current_screen_pointers = array();

	/**
	 * Defines APM default pointers. Any pointer defined by APM core must be set here.
	 * Needs to be nested in a function so that we can translate!
	 */
	private static function get_default_pointers(){
		
		//Format : array( 'screen_id' => array( 'pointer_id' => array([options : target, content, position...]) ) ); 
		//Set the optionnal param context = "apm_fresh_install" to display pointer only if APM was never installed before :
		//use this for pointers that have to be displayed only on fresh APM installs, but not on APM updates.
		
		return array(
				'plugins' => array(
								'apm_install' => array(
										'context' => 'apm_fresh_install', //This is optional
										'target' => '#menu-pages',
										'content' => '<h3>'. __( 'Advanced Page Manager plugin activated !' ,ApmConfig::i18n_domain) .'</h3> <p>'. __( 'Click "Pages" item to discover the new Pages panel.' ,ApmConfig::i18n_domain) .'</p>',
										'position' => array( 'edge' => 'left', 'align' => 'midlle' )
										)
							 )
		);
		
	}
	
	
	/**
	 * Retrieves APM pointers for the current admin screen. Use the 'apm_admin_pointers' hook to add your own pointers.
	 * @return array Current screen APM pointers
	 */
	private static function get_current_screen_apm_pointers(){
		
		$pointers = '';
		
		$screen = get_current_screen();
		$screen_id = $screen->id;
		
		$default_pointers = self::get_default_pointers();
		if( array_key_exists($screen_id,$default_pointers) ){
			$pointers = $default_pointers[$screen_id];
		}
		
		if( !empty($pointers) ){
			foreach($pointers as $pointer_id => $pointer){
				if( !empty($pointer['context']) && $pointer['context'] == 'apm_fresh_install' ){
					//Retrieve APM user tree state meta data to know if he already used APM :
					if( ApmTreeState::current_user_has_tree_state() ){
						//APM tree state not empty >> this is not a fresh APM install >> don't display pointer :
						unset($pointers[$pointer_id]);
					}
				}
			}
		}
		
		$pointers = apply_filters( 'apm_admin_pointers', $pointers, $screen_id );
		
		return $pointers;
	}
	
	/**
	 * WP hooks used by ApmPointers
	 */
	public static function hooks(){
		add_action( 'admin_enqueue_scripts', array(__CLASS__,'admin_enqueue_scripts'), 100);
	}
	
	/**
	 * Hooked to 'admin_enqueue_scripts' WP hook, sets APM pointers : 
	 * sets self::$current_screen_pointers with valid unseened pointers.
	 */
	public static function admin_enqueue_scripts(){
		
		// Don't run on WP < 3.3
		if( get_bloginfo( 'version' ) < '3.3' ){
			return;
		}
		
		$pointers = self::get_current_screen_apm_pointers();
		if( ! $pointers || ! is_array( $pointers ) ){
			return;
		}
		
		// Get dismissed pointers. 
		// Note : dismissed pointers are stored by WP in the "dismissed_wp_pointers" user meta.
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$valid_pointers = array();
		
		// Check pointers and remove dismissed ones.
		foreach( $pointers as $pointer_id => $pointer ) {
			
			// Sanity check
			if( in_array( $pointer_id, $dismissed ) || empty( $pointer )  || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['content'] ) ){
				continue;
			}
			
			// Add the pointer to $valid_pointers array
			$valid_pointers[$pointer_id] =  $pointer;
		}
		
		// No valid pointers? Stop here.
		if( empty( $valid_pointers ) ){
			return;
		}
		
		// Set our static $current_screen_pointers :
		self::$current_screen_pointers = $valid_pointers;
		
		// Add our javascript to handle pointers :
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'admin_print_footer_scripts' ) );
		
		// Add pointers style and javascript to queue.
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
		
	}
	
	/**
	 * Finally prints the javascript that'll make our pointers alive.
	 */
	public static function admin_print_footer_scripts(){
		if( !empty(self::$current_screen_pointers) ): 
			?>
			<script type="text/javascript">// <![CDATA[
				jQuery(document).ready(function($) {
					if(typeof(jQuery().pointer) != 'undefined') {
						<?php foreach(self::$current_screen_pointers as $pointer_id => $data): ?>
							$('<?php echo $data['target'] ?>').pointer({
								content: '<?php echo $data['content'] ?>',
								position: {
									edge: '<?php echo $data['position']['edge'] ?>',
									align: '<?php echo $data['position']['align'] ?>'
								},
								close: function() {
									$.post( ajaxurl, {
										pointer: '<?php echo $pointer_id ?>',
										action: 'dismiss-wp-pointer'
									});
								}
							}).pointer('open');
						<?php endforeach ?>
					}
				});
			// ]]></script>
			<?php
		endif;
	}
	
}

ApmPointers::hooks();