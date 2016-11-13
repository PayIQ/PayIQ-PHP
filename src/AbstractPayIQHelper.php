<?php

namespace PayIQ\PHP;


abstract class AbstractPayIQHelper
{
    const VERSION = '1.0.0';

    static $service_url = 'https://secure.payiq.se/api/v2/soap/PaymentService';
    static $vsdl_url = 'https://secure.payiq.se/api/v2/soap/PaymentService?wsdl';
    static $default_namespace = 'http://schemas.wiredge.se/payment/api/v2/objects';

    public $logger = null;
    protected $client = null;
    protected $order = null;

    protected $serviceName = null;
    protected $sharedSecret = null;
    protected $debug = true;

    protected $lastResponse;
    protected $lastRequest;

    function __construct()
    {

        $this->client = new PayIQSoapClient(
            self::$vsdl_url, //null,
            [
                //'soap_version'  => 'SOAP_1_2',
                //'location' => get_service_url( $endpoint ),
                'uri'           => self::$vsdl_url,
                'trace'         => 1,
                'exceptions'    => 1,
                'use'           => SOAP_LITERAL,
                'encoding'      => 'utf-8',
                'keep_alive'    => true,

                'cache_wsdl'    => WSDL_CACHE_NONE,
                'stream_context' => stream_context_create(
                    [
                        'http' => [
                            'header' => 'Content-Encoding: gzip, deflate'."\n".'Expect: 100-continue'."\n".'Connection: Keep-Alive'
                        ],
                    ]
                )
            ]
        );

    }


    public function setEnvironment($serviceName, $sharedSecret, $debug = false )
    {
        $this->serviceName  = $serviceName;
        $this->sharedSecret = $sharedSecret;
        $this->debug        = $debug;
    }

    /**
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @return mixed
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    abstract public function getOrderId();
    abstract public function getTransactionId();
    abstract public function getSubscriptionId();
    abstract public function getOrderTotal();
    abstract public function getOrderCurrency();
    abstract public function getOrderItems();
    abstract public function getCustomerId();
    abstract public function getOrderInfo();

    /*
    abstract static public function getCallbackUrl();
    abstract static public function getFailureUrl();
    abstract static public function getSuccessUrl();
    */

    function formatPrice( $price ) {

        return intval( $price * 100 );
    }
    function getOrderReference() {
        return $this->order->getOrderId();
    }
    function getCustomerReference() {

        $customer_id = $this->order->get_user_id();

        // If guest
        if ( $customer_id == 0 ) {
            return '';
        }

        return 'customer_' . $customer_id;
    }
    public function getOrderDescription()
    {
        $items = $this->order->getOrderItems();

        $order_items = [];

        foreach ( $items as $item ) {

            $order_items[] = $item['name'] . ' x ' . $item['qty'] . ' ' . $item['line_total'];
        }

        return sprintf( __( 'Order #%s.' ), $this->order->getId() ) . sprintf( 'Items: %s.', implode( ',', $order_items ) );
    }
    public function getTransactionSettings()
    {
        $siteBaseURL = 'http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['SERVER_NAME'].'/';

        return [
            'AutoCapture'       => 'true',  //( isset( $options ) ? 'true' : 'false' ),
            'CallbackUrl'       => $siteBaseURL . '/payment/payiq-callback',
            'CreateSubscription' => 'false',
            'DirectPaymentBank' => '',
            'FailureUrl'        => $siteBaseURL . '/payment/payiq-failure',
            //Allowed values: Card, Direct, NotSet
            'PaymentMethod'     => 'NotSet',
            'SuccessUrl'        => $siteBaseURL . '/payment/payiq-success',
        ];
    }


    public function getVersion()
    {
        return self::VERSION;
    }

    public function setLogger( Logger $logger )
    {
        $this->logger = $logger;
    }
    public function getLogger( )
    {
        return $this->logger;
    }
    public function setOrder( $order )
    {
        $this->order = $order;
    }

    static function getGatewayOption( $key ) {

        $options = get_option( 'woocommerce_payiq_settings', array() );

        if( key_exists( $key, $options ) ) {
            return $options[$key];
        }
        return $options;
    }

    function getRequestXML($method, $data = [] ) {

        $template_file = dirname(__FILE__).'/XML/Templates/' . $method . '.php';

        if ( ! file_exists( $template_file ) ) {
            return false;
        }

        ob_start();

        require $template_file;

        $xml = ob_get_clean();

        return $xml;
    }

    public function stripXMLTagNamespace( $tagName )
    {
        $pos = strpos($tagName, ':');

        if ($pos === false) {
            $str = $tagName;
        }
        else
        {
            $str = substr($tagName, $pos + 1);
        }

        return $str;
    }

    function getXMLFields($xml, $fields = [], $namespace = null ) {

        if( !$namespace )
        {
            $namespace = self::$default_namespace;
        }

        $xmldoc = new \DOMDocument();
        $xmldoc->loadXML( $xml );

        $data = [];

        foreach ( $fields as $key => $value ) {


            if( is_int($key)) {
                $field = $value;
                $subFields = '';
            }
            else
            {
                $field = $key;
                $subFields = $value;
            }

            if ( $xmldoc->getElementsByTagNameNS( $namespace, $field )->length > 0 ) {

                $childNodes = $xmldoc->getElementsByTagNameNS( $namespace, $field )->item( 0 )->childNodes;

/*
                if( !empty( $subFields ) && $xmldoc->getElementsByTagNameNS( $namespace, $field )->item( 0 )->childNodes->length > 0)
                {

                    /*
                    foreach ($xmldoc->getElementsByTagNameNS( $namespace, $field )->item( 0 )->childNodes as $node) {

                        if( in_array( $node->nodeName, $subFields ) )
                        {
                            $data[$node->nodeName] = $node->nodeValue;
                        }

                    }
                    * /
                }
                else
                */
                if( $childNodes->length > 1 )
                {
                    $data[$field] = $this->getXMLChildFields( $childNodes, $field );
                }
                else
                {

                    $data[$field] = $xmldoc->getElementsByTagNameNS( $namespace, $field )->item( 0 )->nodeValue;
                }

            }
            else {
                $data[$field] = '';
            }

        }

        return $data;
    }

    public function getXMLChildFields( $childNodes, $field )
    {
        $data = [];

        foreach ($childNodes as $childNode)
        {
            $fieldName = $this->stripXMLTagNamespace($childNode->nodeName);

            if($childNode->childNodes->length > 1)
            {
                $data[$fieldName][] = $this->getXMLChildFields( $childNode->childNodes, $this->stripXMLTagNamespace($childNode->nodeName) );
            }
            else{
                $data[$fieldName] = $childNode->nodeValue;
            }
        }
        return $data;
    }

    function getTimestamp(){
        $timestamp = gmdate('Y-m-d') . 'T' . gmdate('H:i:s') . 'Z' ;
        return $timestamp;
    }

    function getServiceURL($endpoint ) {


        return self::$service_url . '/' . $endpoint;
    }


    //function getChecksum( $type = 'PrepareSession', $data ) {
    function getChecksum( $type, $data = [] ) {

        if ( ! $this->order ) {
            return false;
        }

        switch ( $type ) {

            case 'CaptureTransaction':

                //ServiceName, TransactionId, Timestamp,  SharedSecret
                $raw_sting = $this->serviceName .
                    $this->getTransactionId() .
                    $this->getTimestamp();

                break;

            case 'ReverseTransaction':
            case 'GetTransactionLog':
            case 'GetTransactionDetails':

                //ServiceName, TransactionId, Timestamp,  SharedSecret
                $raw_sting = $this->serviceName .
                    $data['transactionId'] .
                    //$this->getTransactionId() .
                    $this->getTimestamp();

                break;

            case 'CreditInvoice':
            case 'ActivateInvoice':

                //ServiceName, TransactionId, Timestamp,  SharedSecret
                $raw_sting = $this->serviceName .
                    $this->getTransactionId() .
                    $this->getTimestamp();

                break;

            case 'RefundTransaction':
            case 'AuthorizeSubscription':

                //ServiceName, SubscriptionId, Amount, CurrencyCode, OrderReference, Timestamp, SharedSecret
                $raw_sting = $this->serviceName .
                    $this->getSubscriptionId() .
                    $this->getOrderTotal()  .
                    $this->getOrderCurrency() .
                    $this->getOrderReference() .
                    $this->getTimestamp();

                break;

            case 'GetSavedCards':

                //ServiceName, CustomerId, Timestamp,  SharedSecret
                $raw_sting = $this->serviceName .
                    $this->getCustomerReference() .
                    $this->getTimestamp();

                break;

            case 'DeleteSavedCard':
            case 'AuthorizeRecurring':

                //ServiceName, CardId, Amount, CurrencyCode, OrderReference, Timestamp,  SharedSecret
                return false;

            case 'CreateInvoice':
            case 'CheckSsn':

                return false;

            case 'PrepareSession':
            default:

                //ServiceName, Amount, CurrencyCode, OrderReference, Timestamp, SharedSecret
                //ServiceName, Amount, CurrencyCode, OrderReference, Timestamp, SharedSecret
                $raw_sting = $this->serviceName .
                    $this->formatPrice($this->getOrderTotal()) .
                    $this->getOrderCurrency() .
                    $this->getOrderReference() .
                    $this->getTimestamp();

                break;
        }

        /**
         * Example data:
         * ServiceName = “TestService”
         * Amount = “15099”
         * CurrencyCode = “SEK”
         * OrderReference = “abc123”
         * SharedSecret = “ncVFrw1H”
         */

        $str = strtolower( $raw_sting ) .  $this->sharedSecret;

        return hash('sha512',  $str );
    }

    function validateChecksum( $post_data, $checksum ) {

        $raw_sting = $this->serviceName .
            $post_data['orderreference'] .
            $post_data['transactionid'] .
            $post_data['operationtype'] .
            $post_data['authorizedamount'] .
            $post_data['settledamount'] .
            $post_data['currency'].
            $this->sharedSecret;

        $generated_checksum = hash('md5',  strtolower( $raw_sting ));

        if ( $generated_checksum == $checksum ) {
            return true;
        }

        return [
            'generated' => $generated_checksum,
            'raw_sting' => $raw_sting
        ];
    }

    public function validateCallback( $post_data ) {

        $required_fields = [
            'servicename',
            'transactionid',
            'orderreference',
            'authorizedamount',
            'operationtype',
            //'currency',
            'operationamount',
            'settledamount',
            'message',
            'customername',
            'paymentmethod',
            'directbank',
            'subscriptionid',
            'checksum',
        ];

        foreach ( $required_fields as $required_field ) {

            if ( ! isset( $post_data[$required_field] ) ) {

                echo  'payiq', 'Missing fields: ' . print_r( array_diff( $required_fields, array_keys( $_GET ) ), true );

                die();
                //$this->logger->add( 'payiq', 'Missing fields: ' . print_r( array_diff( $required_fields, array_keys( $_GET ) ), true ) );

                return false;
            }
        }

        $checksum_valid = $this->validateChecksum( $post_data, $post_data['checksum'] );

        if ( $checksum_valid !== true ) {


            if ( ! isset( $_GET[$required_field] ) ) {

                //$this->logger->add( 'payiq', 'Raw string: ' . $checksum_valid['raw_sting'] );
                //$this->logger->add( 'payiq', 'Checksums: Generated: ' . $checksum_valid['generated'] . '  - Sent: ' . $post_data['checksum'] );


                return false;
            }
        }

        return true;
    }

    private function APICall( $endpoint, $params, $outputParams = [] )
    {
        $xml = $this->getRequestXML( $endpoint, $params );

        $response = $this->client->doPayIQRequest( $xml, $endpoint );

        $data = $this->getXmlFields( $response, $outputParams );

        if( empty(array_values($data)[0]) )
        {
            $this->lastResponse = $response;
            $this->lastRequest = $xml;

            return $this->errorResponse( $response );
        }

        return [
            'status' => 'ok',
            'data'   => $data
        ];
    }

    public function errorResponse( $response )
    {
        $data = $this->getXmlFields( $response, [
            'Fault' => [ 'faultstring' ]
        ], 'http://schemas.xmlsoap.org/soap/envelope/' );

        return [
            'status' => 'error',
            'error'  => $data
        ];
    }




    //function prepareSession( $order, $options = [] ) {
    function prepareSession( $options = [] ) {

        $params = [
            'Checksum'          => $this->getChecksum( 'PrepareSession' ),
            'CustomerReference' => $this->getCustomerReference(),
            'Language'          => 'sv',
            'OrderInfo'         => $this->getOrderInfo(),
            'ServiceName'       => $this->serviceName,
            'Timestamp'         => $this->getTimestamp(),
            'TransactionSettings' => $this->getTransactionSettings(),
        ];


        return $this->APICall( 'PrepareSession', $params, [
            'RedirectUrl'
        ]);

        /*
        return $data['RedirectUrl'];

        $xml = $this->getRequestXML( 'PrepareSession', $params );

        $response = $this->client->doPayIQRequest( $xml, 'PrepareSession' );

        $data = $this->getXmlFields( $response, [
            'RedirectUrl'
        ]);

        if(isset($data['RedirectUrl']))
        {
            return $data['RedirectUrl'];
        }

        return $this->errorResponse( $response );
        */
    }

    function AuthorizeRecurring( $data ) {

        /*
        $data = [
            'ServiceName' => $this->serviceName,
            'Checksum' => $this->getChecksum( 'AuthorizeRecurring' ),
            'CardId' => '',
            'Currency' => $this->order->getOrderCurrency(),
            'Amount' => '',
            'OrderReference' => $this->getOrderReference(),
            'CustomerReference' => $this->getCustomerReference(),
            //'TransactionSettings' => new TransactionSettings(),
            'ClientIpAddress' =>  self::getClientIP(),
            'Timestamp' => $this->getTimestamp(),
        ];
        */

        $xml = $this->getRequestXML( 'AuthorizeRecurring', $data );

        $response = $this->client->doPayIQRequest( $xml, 'AuthorizeRecurring' );

        $data = $this->getXMLFields( $response, [
            'Succeeded', 'ErrorCode', 'TransactionId', 'SubscriptionId'
        ]);

        return $data;
    }

    function AuthorizeSubscription( $data ) {

        /*
        $order_id = $this->order->id;
        $subscription_id = get_post_meta($order_id, '_payiq_subscription_id', true);
        $currency = get_post_meta( $order_id, '_order_currency', true );
        $amount_to_charge = $this->order->get_total();
        $amount_to_charge = $amount_to_charge * 100;

        $data = [
            'ServiceName' => $this->serviceName,
            'Checksum' => $this->getChecksum( 'AuthorizeSubscription' ),
            'SubscriptionId' => $subscription_id,
            'Amount' => $amount_to_charge,
            'Currency' => $currency,
            'OrderReference' => $this->getOrderReference(),
            'ClientIpAddress' =>  self::getClientIP(),
            'Timestamp' => $this->getTimestamp(),
        ];
        */


        $xml = $this->getRequestXML( 'AuthorizeSubscription', $data );

        $response = $this->client->doPayIQRequest( $xml, 'AuthorizeSubscription' );

        $data = $this->getXMLFields( $response, [
            'TransactionId'
        ]);

        return $data;
    }

    function GetSavedCards() {

        $data = [
            'ServiceName' 		=> $this->serviceName,
            'Checksum'			=> $this->getChecksum( 'GetSavedCards' ),
            'CustomerReference' => $this->getCustomerReference(),
            'Timestamp' 		=> $this->getTimestamp(),
        ];

        $xml = $this->getRequestXML( 'AuthorizeSubscription', $data );

        $response = $this->client->doPayIQRequest( $xml, 'AuthorizeSubscription' );

        $data = $this->getXMLFields( $response, [
            'Cards'
        ]);

        return $data;
    }


    function GetTransactionDetails( $transactionId ) {


        $params = [
            'Checksum'          => $this->getChecksum( 'GetTransactionDetails', [
                'transactionId'     => $transactionId
            ] ),
            'ServiceName'       => $this->serviceName,
            'TransactionId'     => $transactionId,
            'Timestamp'         => $this->getTimestamp(),
        ];

        $transactionDetails = $this->APICall( 'GetTransactionDetails', $params, [
            'Operations', 'AuthorizedAmount', 'CreationDate', 'Currency', 'CustomerMessage',

            'SubscriptionId',
        ]);

        $currentDate = 0;
        $currentStatus = '';

        foreach($transactionDetails['data']['Operations']['TransactionOperation'] AS $operation)
        {
            if($operation['Successful'] == 'true' && $currentDate <= (int) $operation['Date'])
            {
                $currentDate = (int) $operation['Date'];
                $currentStatus = $operation['Type'];
            }

        }

        $transactionDetails['data']['Status'] = $currentStatus;
        $transactionDetails['data']['Updated'] = $currentDate;

        return $transactionDetails;


        print_r($transactionDetails);

        die();


        $captured = (bool) array_search('Capture', array_column($transactionDetails['data']['Operations']['TransactionOperation'], 'Type'));




        /*
        $data = [
            'Checksum'          => $this->getChecksum( 'GetTransactionDetails' ),
            'ServiceName'       => $this->serviceName,
            'TransactionId'     => $TransactionId,
            'Timestamp' 		=> $this->getTimestamp(),
        ];
        */


        $data = [
            'Checksum'          => $this->getChecksum( 'GetTransactionDetails' ),
            'ServiceName'       => $this->serviceName,
            'TransactionId'     => $transactionId,
            'Timestamp' 		=> $this->getTimestamp(),
        ];


        $xml = $this->getRequestXML( 'GetTransactionDetails', $data );

        $response = $this->client->doPayIQRequest( $xml, 'GetTransactionDetails' );

        $data = $this->getXMLFields( $response, [
            'SubscriptionId'
        ]);

        return $data;
    }

    function CaptureTransaction( $data, $transactionId ) {

        /*
        $data = [
            'Checksum'          => $this->getChecksum( 'CaptureTransaction' ),
            'ClientIpAddress'   => self::getClientIP(),
            'ServiceName'       => $this->serviceName,
            'TransactionId'     => $TransactionId,
            'Timestamp' 		=> $this->getTimestamp(),
        ];
        */

        $xml = $this->getRequestXML( 'CaptureTransaction', $data );


        $response = $this->client->doPayIQRequest( $xml, 'CaptureTransaction' );

        $data = $this->getXMLFields( $response, [
            'Succeeded', 'ErrorCode', 'AuthorizedAmount', 'SettledAmount'
        ]);

        return $data;

    }








}
