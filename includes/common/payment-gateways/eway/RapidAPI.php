<?php
/**
 * A PHP eWAY Rapid API library implementation.
 * This class is an example of how to connect to eWAY's Rapid API.
 *
 * Requires PHP 5.3 or greater with the cURL extension
 *
 * @see http://api-portal.anypoint.mulesoft.com/eway/api/eway-rapid-31-api/docs/
 * @version 1.0
 * @package eWAY
 * @author eWAY
 * @copyright (c) 2014, Web Active Corporation Pty Ltd
 */

/**
 * eWAY Rapid 3.1 Library
 *
 * Check examples for usage
 *
 * @package eWAY
 */
class RapidAPI {

    /**
     * @var string the eWAY endpoint
     */
    private $_url;

    /**
     * @var bool true if using eWAY sandbox
     */
    private $sandbox;

    /**
     * @var string the eWAY API key
     */
    private $username;

    /**
     * @var string the eWAY API password
     */
    private $password;

    /**
     * RapidAPI constructor
     *
     * @param string $username your eWAY API Key
     * @param string $password your eWAY API Password
     * @param string $params set $params['sandbox'] to true to use the sandbox for testing
     */
    function __construct($username, $password, $params=array()) {
        if (strlen($username) === 0 || strlen($password) === 0) {
            die("Username and Password are required");
        }

        $this->username = $username;
        $this->password = $password;

        if (count($params) && isset($params['sandbox']) && $params['sandbox']) {
            $this->_url = 'https://api.sandbox.ewaypayments.com/';
            $this->sandbox = true;
        } else {
            $this->_url = 'https://api.ewaypayments.com/';
            $this->sandbox = false;
        }
    }

    /**
     * Create a request for a Transparent Redirect Access Code
     *
     * @param eWAY\CreateAccessCodeRequest $request
     * @return object
     */
    public function CreateAccessCode($request) {
        if ( isset($request->Options) && count($request->Options->Option) ) {
            $i = 0;
            $tempClass = new \stdClass();
            foreach ($request->Options->Option as $Option) {
                $tempClass->Options[$i] = $Option;
                $i++;
            }
            $request->Options = $tempClass->Options;
        }
        if ( isset($request->Items) && count($request->Items->LineItem) ) {
            $i = 0;
            $tempClass = new \stdClass();
            foreach ($request->Items->LineItem as $LineItem) {
                // must be strings
                $LineItem->Quantity = strval($LineItem->Quantity);
                $LineItem->UnitCost = strval($LineItem->UnitCost);
                $LineItem->Tax = strval($LineItem->Tax);
                $LineItem->Total = strval($LineItem->Total);
                $tempClass->Items[$i] = $LineItem;
                $i++;
            }
            $request->Items = $tempClass->Items;
        }

        $request = json_encode($request);
        $response = $this->PostToRapidAPI("AccessCodes", $request);
        return json_decode($response);
    }

    /**
     * Get the result from an AccessCode after a customer has completed
     * a payment
     *
     * @param eWAY\GetAccessCodeResultRequest $request
     * @return object
     */
    public function GetAccessCodeResult($request) {
        $request = json_encode($request);
        $response = $this->PostToRapidAPI("AccessCode/" . $_GET['AccessCode'], $request, false);
        return json_decode($response);
    }

    /**
     * Create an AccessCode for a Responsive Shared Page payment
     *
     * @param eWAY\CreateAccessCodesSharedRequest $request
     * @return object type
     */
    public function CreateAccessCodesShared($request) {
        if ( isset($request->Options) && count($request->Options->Option) ) {
            $i = 0;
            $tempClass = new \stdClass();
            foreach ($request->Options->Option as $Option) {
                $tempClass->Options[$i] = $Option;
                $i++;
            }
            $request->Options = $tempClass->Options;
        }
        if ( isset($request->Items) && count($request->Items->LineItem) ) {
            $i = 0;
            $tempClass = new \stdClass();
            foreach ($request->Items->LineItem as $LineItem) {
                // must be strings
                $LineItem->Quantity = strval($LineItem->Quantity);
                $LineItem->UnitCost = strval($LineItem->UnitCost);
                $LineItem->Tax = strval($LineItem->Tax);
                $LineItem->Total = strval($LineItem->Total);
                $tempClass->Items[$i] = $LineItem;
                $i++;
            }
            $request->Items = $tempClass->Items;
        }

        $request = json_encode($request);
        $response = $this->PostToRapidAPI("AccessCodesShared", $request);
        return json_decode($response);
    }


    /**
     * Perform a Direct Payment
     *
     * Note: Before being able to send credit card data via the direct API, eWAY
     * must enable it on the account. To be enabled on a live account eWAY must
     * receive proof that the environment is PCI-DSS compliant.
     *
     * @param eWAY\CreateDirectPaymentRequest $request
     * @return object
     */
    public function DirectPayment($request) {
        if ( isset($request->Options) && count($request->Options->Option) ) {
            $i = 0;
            $tempClass = new \stdClass();
            foreach ($request->Options->Option as $Option) {
                $tempClass->Options[$i] = $Option;
                $i++;
            }
            $request->Options = $tempClass->Options;
        }
        if ( isset($request->Items) && count($request->Items->LineItem) ) {
            $i = 0;
            $tempClass = new \stdClass();
            foreach ($request->Items->LineItem as $LineItem) {
                $tempClass->Items[$i] = $LineItem;
                $i++;
            }
            $request->Items = $tempClass->Items;
        }

        $request = json_encode($request);
        $response = $this->PostToRapidAPI("Transaction", $request);
        return json_decode($response);
    }

    /**
     * Fetches the message associated with a Response Code
     *
     * @param string $code
     * @return string
     */
    public function getMessage($code) {
        return ResponseCode::getMessage($code);
    }

    /**
     * A Function for doing a Curl GET/POST
     *
     * @param string $url the path for this request
     * @param Request $request
     * @param boolean $IsPost set to false to perform a GET
     * @return string
     */
    private function PostToRapidAPI( $url, $request, $IsPost = true ) {
        $url = $this->_url . $url;
        
        $args = array(
	        'timeout' => mp_get_api_timeout( 'eway30' ),
	        'sslverify' => false,
	        'redirection' => 1,
	        'headers' => array(
		        'Content-Type' => 'application/json',
		        'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ),
		      ),
		    );

        if ($IsPost)
            $response = wp_remote_post( $url, $args );
        else
            $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            echo "<h2>POST Error: " . $response->get_error_message() . " URL: $url</h2><pre>";
            die();
        } else {
            if ( $response['code'] == 401 || $response['code'] == 404 ) {
                $__is_in_sandbox = $this->sandbox ? ' (Sandbox)' : ' (Live)';
                echo "<h2>Please check the API Key and Password $__is_in_sandbox</h2><pre>";
                die();
            }

            return $response['body'];
        }
    }
}

/**
 * Class containing translations of Response Codes
 */
class ResponseCode {
    private static $_codes = array(
        'F7000' => "Undefined Fraud",
        'V5000' => "Undefined System",
        'A0000' => "Undefined Approved",
        'A2000' => "Transaction Approved",
        'A2008' => "Honour With Identification",
        'A2010' => "Approved For Partial Amount",
        'A2011' => "Approved VIP",
        'A2016' => "Approved Update Track 3",
        'V6000' => "Undefined Validation",
        'V6001' => "Invalid Request CustomerIP",
        'V6002' => "Invalid Request DeviceID",
        'V6011' => "Invalid Payment Amount",
        'V6012' => "Invalid Payment InvoiceDescription",
        'V6013' => "Invalid Payment InvoiceNumber",
        'V6014' => "Invalid Payment InvoiceReference",
        'V6015' => "Invalid Payment CurrencyCode",
        'V6016' => "Payment Required",
        'V6017' => "Payment CurrencyCode Required",
        'V6018' => "Unknown Payment CurrencyCode",
        'V6021' => "Cardholder Name Required",
        'V6022' => "Card Number Required",
        'V6023' => "CVN Required",
        'V6031' => "Invalid Card Number",
        'V6032' => "Invalid CVN",
        'V6033' => "Invalid Expiry Date",
        'V6034' => "Invalid Issue Number",
        'V6035' => "Invalid Start Date",
        'V6036' => "Invalid Month",
        'V6037' => "Invalid Year",
        'V6040' => "Invalid Token Customer Id",
        'V6041' => "Customer Required",
        'V6042' => "Customer First Name Required",
        'V6043' => "Customer Last Name Required",
        'V6044' => "Customer Country Code Required",
        'V6045' => "Customer Title Required",
        'V6046' => "Token Customer ID Required",
        'V6047' => "RedirectURL Required",
        'V6051' => "Invalid Customer First Name",
        'V6052' => "Invalid Customer Last Name",
        'V6053' => "Invalid Customer Country Code",
        'V6054' => "Invalid Customer Email",
        'V6055' => "Invalid Customer Phone",
        'V6056' => "Invalid Customer Mobile",
        'V6057' => "Invalid Customer Fax",
        'V6058' => "Invalid Customer Title",
        'V6059' => "Redirect URL Invalid",
        'V6060' => "Redirect URL Invalid",
        'V6061' => "Invalid Customer Reference",
        'V6062' => "Invalid Customer CompanyName",
        'V6063' => "Invalid Customer JobDescription",
        'V6064' => "Invalid Customer Street1",
        'V6065' => "Invalid Customer Street2",
        'V6066' => "Invalid Customer City",
        'V6067' => "Invalid Customer State",
        'V6068' => "Invalid Customer Postalcode",
        'V6069' => "Invalid Customer Email",
        'V6070' => "Invalid Customer Phone",
        'V6071' => "Invalid Customer Mobile",
        'V6072' => "Invalid Customer Comments",
        'V6073' => "Invalid Customer Fax",
        'V6074' => "Invalid Customer Url",
        'V6075' => "Invalid ShippingAddress FirstName",
        'V6076' => "Invalid ShippingAddress LastName",
        'V6077' => "Invalid ShippingAddress Street1",
        'V6078' => "Invalid ShippingAddress Street2",
        'V6079' => "Invalid ShippingAddress City",
        'V6080' => "Invalid ShippingAddress State",
        'V6081' => "Invalid ShippingAddress PostalCode",
        'V6082' => "Invalid ShippingAddress Email",
        'V6083' => "Invalid ShippingAddress Phone",
        'V6084' => "Invalid ShippingAddress Country",
        'V6091' => "Unknown Country Code",
        'V6100' => "Invalid ProcessRequest name",
        'V6101' => "Invalid ProcessRequest ExpiryMonth",
        'V6102' => "Invalid ProcessRequest ExpiryYear",
        'V6103' => "Invalid ProcessRequest StartMonth",
        'V6104' => "Invalid ProcessRequest StartYear",
        'V6105' => "Invalid ProcessRequest IssueNumber",
        'V6106' => "Invalid ProcessRequest CVN",
        'V6107' => "Invalid ProcessRequest AccessCode",
        'V6108' => "Invalid ProcessRequest CustomerHostAddress",
        'V6109' => "Invalid ProcessRequest UserAgent",
        'V6110' => "Invalid ProcessRequest Number",
        'V6111' => "Unauthorised API Access, Account Not PCI Certified",
        'V6112' => "Redundant card details other than expiry year and month",
        'V6113' => "Invalid transaction for refund",
        'V6114' => "Gateway validation error",
        'V6115' => "Invalid DirectRefundRequest, Transaction ID",
        'V6116' => "Invalid card data on original TransactionID",
        'V6117' => "Invalid CreateAccessCodeSharedRequest, FooterText",
        'V6118' => "Invalid CreateAccessCodeSharedRequest, HeaderText",
        'V6119' => "Invalid CreateAccessCodeSharedRequest, Language",
        'V6120' => "Invalid CreateAccessCodeSharedRequest, LogoUrl",
        'V6121' => "Invalid TransactionSearch, Filter Match Type",
        'V6122' => "Invalid TransactionSearch, Non numeric Transaction ID",
        'V6123' => "Invalid TransactionSearch,no TransactionID or AccessCode specified",
        'V6124' => "Invalid Line Items. The line items have been provided however the totals do not match the TotalAmount field",
        'V6125' => "Selected Payment Type not enabled",
        'V6126' => "Invalid encrypted card number, decryption failed",
        'V6127' => "Invalid encrypted cvn, decryption failed",
        'D4401' => "Refer to Issuer",
        'D4402' => "Refer to Issuer, special",
        'D4403' => "No Merchant",
        'D4404' => "Pick Up Card",
        'D4405' => "Do Not Honour",
        'D4406' => "Error",
        'D4407' => "Pick Up Card, Special",
        'D4409' => "Request In Progress",
        'D4412' => "Invalid Transaction",
        'D4413' => "Invalid Amount",
        'D4414' => "Invalid Card Number",
        'D4415' => "No Issuer",
        'D4419' => "Re-enter Last Transaction",
        'D4421' => "No Method Taken",
        'D4422' => "Suspected Malfunction",
        'D4423' => "Unacceptable Transaction Fee",
        'D4425' => "Unable to Locate Record On File",
        'D4430' => "Format Error",
        'D4431' => "Bank Not Supported By Switch",
        'D4433' => "Expired Card, Capture",
        'D4434' => "Suspected Fraud, Retain Card",
        'D4435' => "Card Acceptor, Contact Acquirer, Retain Card",
        'D4436' => "Restricted Card, Retain Card",
        'D4437' => "Contact Acquirer Security Department, Retain Card",
        'D4438' => "PIN Tries Exceeded, Capture",
        'D4439' => "No Credit Account",
        'D4440' => "Function Not Supported",
        'D4441' => "Lost Card",
        'D4442' => "No Universal Account",
        'D4443' => "Stolen Card",
        'D4444' => "No Investment Account",
        'D4451' => "Insufficient Funds",
        'D4452' => "No Cheque Account",
        'D4453' => "No Savings Account",
        'D4454' => "Expired Card",
        'D4455' => "Incorrect PIN",
        'D4456' => "No Card Record",
        'D4457' => "Function Not Permitted to Cardholder",
        'D4458' => "Function Not Permitted to Terminal",
        'D4460' => "Acceptor Contact Acquirer",
        'D4461' => "Exceeds Withdrawal Limit",
        'D4462' => "Restricted Card",
        'D4463' => "Security Violation",
        'D4464' => "Original Amount Incorrect",
        'D4466' => "Acceptor Contact Acquirer, Security",
        'D4467' => "Capture Card",
        'D4475' => "PIN Tries Exceeded",
        'D4482' => "CVV Validation Error",
        'D4490' => "Cutoff In Progress",
        'D4491' => "Card Issuer Unavailable",
        'D4492' => "Unable To Route Transaction",
        'D4493' => "Cannot Complete, Violation Of The Law",
        'D4494' => "Duplicate Transaction",
        'D4496' => "System Error",
    );

    /**
     * Fetches the message associated with a Response Code
     *
     * @param string $code
     * @return string
     * @static
     */
    public static function getMessage($code) {
        if (isset(ResponseCode::$_codes[$code])) {
            return ResponseCode::$_codes[$code];
        } else {
            return $code;
        }
    }
}

/**
 * Contains details to create a Transparent Redirect Access Code
 */
class CreateAccessCodeRequest {
    public $Customer;

    public $ShippingAddress;
    public $Items;
    public $Options;

    public $Payment;
    public $RedirectUrl;
    public $Method;
    public $TransactionType;
    public $CustomerIP;
    public $DeviceID;

    function __construct() {
        $this->Customer = new Customer();
        $this->ShippingAddress = new ShippingAddress();
        $this->Payment = new Payment();
        $this->CustomerIP = $_SERVER["REMOTE_ADDR"];
    }
}

/**
 * Contains details to create a Shared Page Redirect
 */
class CreateAccessCodesSharedRequest extends CreateAccessCodeRequest {
    public $CancelUrl;
    public $LogoUrl;
    public $HeaderText;
    public $CustomerReadOnly;
}

/**
 * Contains details to complete a Direct Payment
 */
class CreateDirectPaymentRequest {
    public $Customer;

    public $ShippingAddress;
    public $Items;
    public $Options;

    public $Payment;
    public $CustomerIP;
    public $DeviceID;
    public $TransactionType;
    public $PartnerID;

    function __construct() {
        $this->Customer = new CardCustomer();
        $this->ShippingAddress = new ShippingAddress();
        $this->Payment = new Payment();
        $this->CustomerIP = $_SERVER["REMOTE_ADDR"];
    }
}

/**
 * Contains details of a Customer
 */
class Customer {
    public $TokenCustomerID;
    public $Reference;
    public $Title;
    public $FirstName;
    public $LastName;
    public $CompanyName;
    public $JobDescription;
    public $Street1;
    public $Street2;
    public $City;
    public $State;
    public $PostalCode;
    public $Country;
    public $Email;
    public $Phone;
    public $Mobile;
    public $Comments;
    public $Fax;
    public $Url;
}

/**
 * Contains details of a Customer with card details
 */
class CardCustomer extends Customer {
    function __construct() {
        $this->CardDetails = new CardDetails();
    }
}

/**
 * Contains details of Shipping Address
 */
class ShippingAddress {
    public $FirstName;
    public $LastName;
    public $Street1;
    public $Street2;
    public $City;
    public $State;
    public $Country;
    public $PostalCode;
    public $Email;
    public $Phone;
    public $ShippingMethod;
}

/**
 * Contains details of Items
 */
class Items {
    public $LineItem = array();
}

/**
 * Contains details of a Line Item
 */
class LineItem {
    public $SKU;
    public $Description;
    public $Quantity;
    public $UnitCost;
    public $Tax;
    public $Total;
}

/**
 * Contains details of Options
 */
class Options {
    public $Option = array();
}

/**
 * Contains details of an Option
 */
class Option {
    public $Value;
}

/**
 * Contains details of a Payment
 */
class Payment {
    public $TotalAmount;
    /// <summary>The merchant's invoice number</summary>
    public $InvoiceNumber;
    /// <summary>merchants invoice description</summary>
    public $InvoiceDescription;
    /// <summary>The merchant's invoice reference</summary>
    public $InvoiceReference;
    /// <summary>The merchant's currency</summary>
    public $CurrencyCode;
}

/**
 * Contains details to request the result of an Access Code
 */
class GetAccessCodeResultRequest {
    public $AccessCode;
}

/**
 * Contains details of a credit card
 */
class CardDetails {
    public $Name;
    public $Number;
    public $ExpiryMonth;
    public $ExpiryYear;
    public $StartMonth;
    public $StartYear;
    public $IssueNumber;
    public $CVN;
}
