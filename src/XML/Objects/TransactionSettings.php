<?php

namespace PayIQ\XML\Objects;

class TransactionSettings
{
    public $autoCapture         = true;
    public $callbackUrl         = '';
    public $CreateSubscription  = false;
    public $directPaymentBank   = '';
    public $FailureUrl          = '';
    public $PaymentMethod       = 'NotSet';
    public $SuccessUrl          = '';

    public function __construct()
    {

        /*
        $data = [
            'AutoCapture'       => 'true',  //( isset( $options ) ? 'true' : 'false' ),
            'CallbackUrl'       => trailingslashit( site_url( '/woocommerce/payiq-callback' ) ),
            'CreateSubscription' => 'false',
            'DirectPaymentBank' => '',
            'FailureUrl'        => trailingslashit( site_url( '/woocommerce/payiq-failure' ) ),
            //Allowed values: Card, Direct, NotSet
            'PaymentMethod'     => 'NotSet',
            'SuccessUrl'        => trailingslashit( site_url( '/woocommerce/payiq-success' ) ),
        ];
        */
    }

}
