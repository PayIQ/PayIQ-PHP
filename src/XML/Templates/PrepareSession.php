<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <PrepareSession xmlns="http://schemas.wiredge.se/payment/api/v2">
            <data xmlns:a="http://schemas.wiredge.se/payment/api/v2/objects" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <a:Checksum><?php echo $data['Checksum']; ?></a:Checksum>
                <a:CustomerReference i:nil="true"/>
                <a:Language><?php echo $data['Language']; ?></a:Language>
                <a:OrderInfo>
                    <a:Currency><?php echo $data['OrderInfo']['Currency']; ?></a:Currency>
                    <a:Description><?php echo $data['OrderInfo']['OrderDescription']; ?></a:Description>
                    <a:Items>
                        <?php foreach( $data['OrderInfo']['OrderItems'] AS $orderItem ) : ?><a:OrderItem>
                            <a:Description><?php echo $orderItem['Description']; ?></a:Description>
                            <a:Quantity><?php echo $orderItem['Quantity']; ?></a:Quantity>
                            <a:SKU><?php echo $orderItem['SKU']; ?></a:SKU>
                            <a:UnitPrice><?php echo $orderItem['UnitPrice']; ?></a:UnitPrice>
                        </a:OrderItem><?php endforeach; echo "\n"; ?>
                    </a:Items>
                    <a:OrderCategory i:nil="true"/>
                    <a:OrderReference><?php echo $data['OrderInfo']['OrderReference']; ?></a:OrderReference>
                </a:OrderInfo>
                <a:ServiceName><?php echo $data['ServiceName']; ?></a:ServiceName>
                <a:Timestamp><?php echo $data['Timestamp']; ?></a:Timestamp>
                <a:TransactionSettings>
                    <a:AutoCapture><?php echo $data['TransactionSettings']['AutoCapture']; ?></a:AutoCapture>
                    <a:CallbackUrl><?php echo $data['TransactionSettings']['CallbackUrl']; ?></a:CallbackUrl>
                    <a:CreateSubscription><?php echo $data['TransactionSettings']['CreateSubscription']; ?></a:CreateSubscription>
                    <a:DirectPaymentBank i:nil="true"/>
                    <a:FailureUrl><?php echo $data['TransactionSettings']['FailureUrl']; ?></a:FailureUrl>
                    <a:PaymentMethod><?php echo $data['TransactionSettings']['PaymentMethod']; ?></a:PaymentMethod>
                    <a:SuccessUrl><?php echo $data['TransactionSettings']['SuccessUrl']; ?></a:SuccessUrl>
                </a:TransactionSettings>
            </data>
        </PrepareSession>
    </s:Body>
</s:Envelope>
