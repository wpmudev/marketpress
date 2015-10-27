<?php

class MP_Mailer {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Mailer();
		}
		return self::$_instance;
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
	}

	/**
	 * Send an email
	 *
	 * @since 3.0
	 * @access public
	 * @param string $email The email address to send to.
	 * @param string $subject The subject of the email.
	 * @param string $msg The email message.
	 * @param array $attachments
	 * @return bool
	 */
	public function send( $email, $subject, $msg, $attachments = array() ) {
		//remove any other filters
		remove_all_filters( 'wp_mail_from' );
		remove_all_filters( 'wp_mail_from_name' );
		remove_all_filters( 'wp_mail_content_type' );
		remove_all_filters( 'wp_mail_charset' );

		// add filters
		add_filter( 'wp_mail_from', array( &$this, 'set_mail_from' ) );
		add_filter( 'wp_mail_from_name', array( &$this, 'set_mail_from_name' ) );
		add_filter( 'wp_mail_content_type', array( &$this, 'set_mail_content_type' ) );
		add_filter( 'wp_mail_charset', array( &$this, 'set_mail_charset' ) );

		//convert all tabs to their approriate html markup
		$msg = str_replace( array( "\t" ), array( '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' ), $msg );
		$headers = apply_filters('mp_mailer_headers',array());
		$attachments = apply_filters('mp_mailer_attachments',$attachments);
		return wp_mail( $email, $subject, $msg,$headers,$attachments );
	}

	/**
	 * Set the charset
	 *
	 * @since 3.0
	 * @access public
	 * @filter wp_mail_charset
	 */
	public function set_mail_charset( $charset ) {
		return 'UTF-8';
	}

	/**
	 * Set the content type
	 *
	 * @since 3.0
	 * @access public
	 * @filter wp_mail_content_type
	 */
	public function set_mail_content_type( $content_type ) {
		return 'text/html';
	}

	/**
	 * Set the from email
	 *
	 * @since 3.0
	 * @access public
	 * @filter wp_mail_from
	 */
	public function set_mail_from( $email ) {
		return mp_get_store_email();
	}

	/**
	 * Set the from name
	 *
	 * @since 3.0
	 * @access public
	 * @filter wp_mail_from_name
	 */
	public function set_mail_from_name( $name ) {
		return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	}

}