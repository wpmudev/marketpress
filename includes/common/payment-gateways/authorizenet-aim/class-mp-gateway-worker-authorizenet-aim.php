<?php
	
class MP_Gateway_Worker_AuthorizeNet_AIM {
	var $login;
	var $transkey;
	var $params = array();
	var $results = array();
	var $line_items = array();
	var $approved = false;
	var $declined = false;
	var $held_for_review = false;
	var $plugin_name = 'authorize';
	var $error = true;
	var $method = "";
	var $fields;
	var $response;
	var $instances = 0;

	function __construct( $url, $delim_data, $delim_char, $encap_char, $gw_username, $gw_tran_key, $gw_test_mode ) {
		if ( $this->instances > 0 ) {
			return false;
		}
		
		$this->url = $url;

		$this->params['x_delim_data'] = ($delim_data == 'yes') ? 'TRUE' : 'FALSE';
		$this->params['x_delim_char'] = empty( $delim_char ) ? ',' : $delim_char;
		$this->params['x_encap_char'] = $encap_char;
		$this->params['x_relay_response'] = "FALSE";
		$this->params['x_url'] = "FALSE";
		$this->params['x_version'] = "3.1";
		$this->params['x_method'] = "CC";
		$this->params['x_type'] = "AUTH_CAPTURE";
		$this->params['x_login'] = $gw_username;
		$this->params['x_tran_key'] = $gw_tran_key;
		$this->params['x_test_request'] = $gw_test_mode;

		$this->instances ++;
	}

	function transaction( $cardnum ) {
		$this->params['x_card_num'] = preg_replace( '/[^0-9]/', '', $cardnum );
	}
	
	function cleanString( $str, $length ) {
		//replace encoded characters with their non-encoded versions
		$search = array('&#8230;', '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8226;', '&#8211;', '&#8212;');
		$replace = array('...', "'", "'", '"', '"', '•', '-', '-');
		$str = str_replace($search, $replace, $str);
		
		//remove all other entities
		$str = preg_replace("/&.{0,}?;/", '', $str);
		
		//shorten length
		$str = substr($str, 0, $length);
		
		return $str;
	}
	
	function addLineItem( $id, $name, $description, $quantity, $price, $taxable = 0 ) {
		$id = $this->cleanString($id, 31);
		$name = $this->cleanString($name, 31);
		$description = $this->cleanString($description, 255);
		$this->line_items[] = "{$id}<|>{$name}<|>{$description}<|>{$quantity}<|>{$price}<|>{$taxable}";
	}

	function process() {
		$this->_prepareParameters();
		
		/**
		 * Set the number of retries
		 *
		 * @since 3.0
		 * @param int $retries The current number of retries.
		 */
		$retries = apply_filters( 'mp_gateway/authorizenet_aim/api_retries', 1 );
		
		$count = 0;
		while ( $count < $retries ) {
			$args['user-agent'] = 'MarketPress/' . MP_VERSION . ': http://premium.wpmudev.org/project/e-commerce | Authorize.net AIM Plugin/' . MP_VERSION;
			$args['body'] = $this->fields;
			$args['sslverify'] = false;
			$args['timeout'] = mp_get_api_timeout( $this->plugin_name );

			//use built in WP http class to work with most server setups
			$response = wp_remote_post( $this->url, $args );
			
			if ( ! is_wp_error( $response ) ) {
				$this->response = $response['body'];
			} else {
				$this->response = "";
				$this->error = true;
				return;
			}

			$this->parseResults();

			switch ( $this->getResultResponseFull() ) {
				case 'Approved' :
					$this->approved = true;
					$this->declined = false;
					$this->error = false;
					$this->method = $this->getMethod();
					$count = $retries;
				break;
				
				case 'Declined' :
					$this->approved = false;
					$this->declined = true;
					$this->error = false;
					$count = $retries;
				break;
				
				case 'HeldForReview' :
					$this->approved = true;
					$this->declined = false;
					$this->error = false;
					$this->held_for_review = true;
					$count = $retries;
				break;
			}
			
			$count ++;
		}
	}

	function parseResults() {
			$this->results = explode( $this->params['x_delim_char'], $this->response );
	}

	function setParameter( $param, $value ) {
			$param = trim( $param );
			$value = trim ($value );
			$this->params[ $param ] = $value;
	}

	function setTransactionType($type) {
			$this->params['x_type'] = strtoupper(trim($type));
	}

	function _prepareParameters() {
		$this->fields = http_build_query( $this->params );
		foreach ( $this->line_items as $i => $line_item ) {
			$this->fields .= '&x_line_item=' . $line_item;
		}
	}

	function getMethod() {
		if ( isset( $this->results[51] ) ) {
			return str_replace( $this->params['x_encap_char'], '', $this->results[51] );
		}
		
		return "";
	}

	function getGatewayResponse() {
		return str_replace( $this->params['x_encap_char'], '', $this->results[0] );
	}

	function getResultResponseFull() {
		$response = array( "", "Approved", "Declined", "Error", "HeldForReview" );
		return $response[ str_replace( $this->params['x_encap_char'], '', $this->results[0]) ];
	}

	function isApproved() {
		return $this->approved;
	}

	function isDeclined() {
		return $this->declined;
	}

	function isError() {
		return $this->error;
	}
	
	function isHeldForReview() {
		return $this->held_for_review;
	}				

	function getResponseText() {
		return $this->results[3];
	}

	function getAuthCode() {
		return str_replace( $this->params['x_encap_char'], '', $this->results[4] );
	}

	function getAVSResponse() {
		return str_replace( $this->params['x_encap_char'], '', $this->results[5] );
	}

	function getTransactionID() {
		return str_replace( $this->params['x_encap_char'], '', $this->results[6] );
	}
}