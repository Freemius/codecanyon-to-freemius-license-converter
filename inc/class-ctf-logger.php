<?php
/**
 * Logs important actions of our plugin
 *
 * It is important that we save some events that take place on the plugin, this class
 * handles the addition of messages to our log file
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Logger
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {
	exit;
} // end if;

class CTF_Logger {

	/**
	 * Stores open file _handles.
	 *
	 * @var array
	 * @access private
	 */
	static $_handles;

	/**
	 * Constructor for the logger.
	 */
	public function __construct() {
		self::$_handles = array();
	} // end __construct;


	/**
	 * Destructor.
	 */
	public function __destruct() {
		foreach (self::$_handles as $handle) {
			@fclose($handle);
		} // end foreach;
	} // end __destruct;


	/**
	 * Returns the uplaods directory
     *
	 * @return string
	 */
	public static function get_uploads_folder() {

		$uploads = wp_upload_dir(null, false);

		return isset($uploads['basedir']) && $uploads['basedir'] ? $uploads['basedir'] : '';

	}  // end get_uploads_folder;

	/**
	 * Returns the logs folder
     *
	 * @return string
	 */
	public static function get_logs_folder() {

		is_multisite() && switch_to_blog( get_current_site()->blog_id );

		$path = apply_filters('ctf_get_logs_folder', self::get_uploads_folder() . '/ctf-logs' . '/');

		is_multisite() && restore_current_blog();

		return $path;

	} // end get_logs_folder;

	/**
	 * Creates Logs folder
     *
	 * @return
	 */
	public static function create_logs_folder() {

		// Creates the Folder
		wp_mkdir_p(self::get_logs_folder());

		// Creates htaccess
		$htaccess = self::get_logs_folder() . '.htaccess';

		if (!file_exists($htaccess)) {

			$fp = @fopen($htaccess, 'w');

			@fputs ($fp, 'deny from all');

			@fclose ($fp);

		} // end if;

		// Creates index
		$index = self::get_logs_folder() . 'index.html';

		if (!file_exists($index)) {

			$fp = @fopen($index, 'w');

			@fputs ($fp, '');

			@fclose ($fp);

		} // end if;

	} // end create_logs_folder;

	/**
	 * Get the log contents
	 *
	 * @since  1.6.0
	 * @param  string  $handle
	 * @param  integer $lines
	 * @return array
	 */
	public static function read_lines($handle, $lines = 10) {

		$results = array();

		// Open the file for reading
		if (self::open( $handle, 'r' ) && is_resource( self::$_handles[ $handle ] ) ) {

			while (!feof( self::$_handles[ $handle ] )) {

				$line = fgets(self::$_handles[ $handle ], 4096);

				array_push($results, $line);

				if (count($results) > $lines + 1) {

					array_shift($results);

				}// end if;

			} // end while;

			if (@fclose(self::$_handles[ $handle ]) === false) {

				// return false;
			} // end if;

		} // end if;

		// Close the file handle; when you are done using a
		// resource you should always close it immediately
		return array_filter($results);

	} // end read_lines;

	/**
	 * Open log file for writing.
     *
	 * @since  1.2.0 Checks if the directory exists
	 * @since  0.0.1
	 *
	 * @access private
	 * @param mixed $handle
	 * @return bool success
	 */
	private static function open($handle, $permission = 'a') {

		// Get the path for our logs
		$path = self::get_logs_folder();

		if (!is_dir($path)) {
			self::create_logs_folder();
			return false;
		} // end if;

		// if (isset( self::$_handles[ $handle ])) {
		// return true;
		// }
		if (self::$_handles[ $handle ] = @fopen($path . $handle . '.log', $permission)) {
			return true;
		} // end if;

		return false;

	} // end open;



	/**
	 * Add a log entry to chosen file.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public static function add($handle, $message) {
		if ( self::open( $handle ) && is_resource( self::$_handles[ $handle ] ) ) {
			$datetime = new DateTime(current_time('mysql'));
			$time     = $datetime->format( 'm-d-Y @ H:i:s -' ); // Grab Time
			$result   = @fwrite( self::$_handles[ $handle ], $time . ' ' . $message . "\n" );
			@fclose (self::$_handles[ $handle ]);
		} // end if;

		do_action('ctf_log_add', $handle, $message);
	} // end add;



	/**
	 * Clear entries from chosen file.
	 *
	 * @param mixed $handle
	 */
	public function clear($handle ) {
		if (self::open($handle) && is_resource(self::$_handles[$handle])) {
			@ftruncate(self::$_handles[$handle], 0);
		} // end if;

		do_action('ctf_log_clear', $handle);
	} // end clear;


} // end class CTF_Logger;

