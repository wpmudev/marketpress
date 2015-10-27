<?php
	
if ( ! class_exists( 'MP_Gateway_Worker_Payflow' ) ) :
class MP_Gateway_Worker_Payflow
{
	var $login;
	var $transkey;
	var $params = array();
	var $results = array();
	var $line_items = array();

	var $approved = false;
	var $declined = false;
	var $held_for_review = false;
	var $error = true;
	var $method = '';
	var $status = '';

	var $fields;
	var $response;

	var $instances = 0;

	function __construct($url) {
		if ( $this->instances == 0 ) {
		 	$this->url = $url;
			$this->instances++;
		} else {
			return false;
		}
	}

	function transaction( $cardnum ) {
		$this->params['ACCT']	 = preg_replace( '/[^0-9]/', '', $cardnum );
	}

	function addLineItem( $id, $name, $description, $quantity, $price, $taxable = 0 ) {
		$this->line_items[] = "{$id}<|>{$name}<|>{$description}<|>{$quantity}<|>{$price}<|>{$taxable}";
	}

	function process( $retries = 1 ) {
		$post_string = '';
		
		foreach ( $this->params as $key => $value ) {
			$value = utf8_encode ( trim( $value ) );
			$post_string .= $key . '[' . strlen( $value ) . ']=' . $value . '&';
		}

		$post_string = rtrim( $post_string, '&' );
		$count = 0;
		
		while ( $count < $retries ) {
			$response = $this->sendTransactionToGateway( $this->url, $post_string );
			
			if ( ! is_wp_error( $response ) ) {
				break;
			}
			
			$count ++;
		}
		
		$response_array = array();
		parse_str( $response, $response_array );
		
		$this->results = $response_array;
		$this->results['METHOD'] = 'Sale';
		
		$result_code = mp_arr_get_value( 'RESULT', $response_array );
		switch ( $result_code ) {
			// Approved
			case 0 :
				$this->approved = true;
				$this->declined = false;
				$this->error = false;
				$this->method = $this->getMethod();
			break;
			
			// Held for review
			case 126 :
			case 127 :
			case 128 :
				$this->approved = true;
				$this->status = mp_arr_get_value( 'PREFPSMSG', $response_array );
				$this->held_for_review = true;
			break;
			
			// Declined
			default :
				$this->approved = false;
				$this->declined = true;
				$this->error = false;
			break;
		}
 }


	function sendTransactionToGateway( $url, $parameters ) {
		$server = parse_url( $url );
		$headers = array();

		if ( '' == mp_arr_get_value( 'port', $server, '' ) ) {
			$server['port'] = ( 'https' == mp_arr_get_value( 'scheme', $server ) ) ? 443 : 80;
		}

		if ( '' == mp_arr_get_value( 'path', $server, '' ) ) {
			$server['path'] = '/';
		}

		if ( '' != mp_arr_get_value( 'user', $server, '' ) && '' != mp_arr_get_value( 'pass', $server, '' ) ) {
			$headers[] = 'Authorization: Basic ' . base64_encode( $server['user'] . ':' . $server['pass'] );
		}
		
		$url = $server['scheme'] . '://' . $server['host'] . $server['path'] . (( isset( $server['query'] ) ) ? '?' . $server['query'] : '');
		$result = wp_remote_post( $url, array(
			'user-agent' => 'MarketPress/' . MP_VERSION . ': http://premium.wpmudev.org/project/e-commerce | PayPal Payflow Plugin/' . MP_VERSION,
			'sslverify' => false,
			'body' => $parameters,
			'timeout' => mp_get_api_timeout( 'payflow' ),
		) );
		
		if ( ! is_wp_error( $result ) ) {
			if ( $result['response']['code'] != 200 ) {
				return new WP_Error( '', __( 'There was an error connecting to PayPal. Please try again.', 'mp' ) );
			}
		}
		
		return wp_remote_retrieve_body( $result );
	}

	function parseResults() {
		$this->results = explode( '&', $this->response );
	}

	function setParameter( $param, $value ) {
		$param = trim( $param );
		$value = trim( $value );
		$this->params[ $param ] = $value;
	}

	function _prepareParameters() {
		foreach( $this->params as $key => $value ) {
			$this->fields = http_build_query( $this->params );
		}
		
		foreach ( $this->line_items as $i => $line_item ) {
			$this->fields .= '&x_line_item=' . $line_item;
		}
	}

	function getMethod() {
		return mp_arr_get_value( 'METHOD', $this->results, '' );
	}

	function getGatewayResponse() {
		return $this->results['RESULT'];
	}

	function getResultResponseFull() {
		return $this->results['RESPMSG'];
	}

	function isApproved() {
		return $this->approved;
	}

	function isDeclined() {
		return $this->declined;
	}

	function isHeldForReview() {
		return $this->held_for_review;
	}

	function isError() {
		return $this->error;
	}

	function getResponseText() {
		return $this->results['RESPMSG'];
	}

	function getAuthCode() {
		return $this->results['AUTHCODE'];
	}

	function getAVSResponse() {
		return true;
	}

	function getTransactionID() {
		return $this->results['PNREF'];
	}
}
endif;