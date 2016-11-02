<?php

namespace PayIQ\PHP;

class PayIQSoapClient extends \SoapClient {

    protected $debug = false;

    function __construct( $wsdl, $options, $debug = false ) {
        parent::__construct( $wsdl, $options );
        $this->server = new \SoapServer( $wsdl, $options );

        $this->debug = $debug;
    }
    public function __doRequest( $request, $location, $action, $version, $one_way = null ) {
        $result = parent::__doRequest( $request, $location, $action, $version );
        return $result;
    }
    function doPayIQRequest($array, $op ) {
        $request = $array;
        //$location = 'http://xxxxx:xxxx/TransactionServices/TransactionServices6.asmx';
        //$action = 'http://www.micros.com/pos/les/TransactionServices/'.$op;

        $location = 'https://secure.payiq.se/api/v2/soap/PaymentService';
        $action = 'http://schemas.wiredge.se/payment/api/v2/IPaymentService/'.$op;
        $version = '1';

        $result = $this->__doRequest( $request, $location, $action, $version );

        /*
        echo "\n\n\n";
        echo 'ACTION: ' . $action . PHP_EOL . "\n\n\n";
        echo 'REQUEST:' . PHP_EOL . print_r( $request, true ) . PHP_EOL . "\n\n\n";
        echo 'RESPONSE:' . PHP_EOL . print_r( $result, true ) . PHP_EOL.PHP_EOL . "\n\n\n";
        */

        if($this->isDebug())
        {
            /*
            $logger = new WC_Logger();
            $logger->add( 'payiq', 'API Call: ' . PHP_EOL .
                'REQUEST:' . PHP_EOL . print_r( $request, true ) . PHP_EOL .
                'RESPONSE:' . PHP_EOL . print_r( $result, true ) . PHP_EOL.PHP_EOL );
            */
        }

        return $result;
    }

    /**
     * @return boolean
     */
    public function isDebug() {

        return $this->debug;
    }

    /**
     * @param boolean $debug
     */
    public function setDebug( $debug ) {

        $this->debug = (bool) $debug;
    }
}
