<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <CaptureTransaction xmlns="http://schemas.wiredge.se/payment/api/v2">
            <data xmlns:a="http://schemas.wiredge.se/payment/api/v2/objects" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <a:Checksum><?php echo $data['Checksum']; ?></a:Checksum>
                <a:ClientIpAddress><?php echo $data['ClientIpAddress']; ?></a:ClientIpAddress>
                <a:ServiceName><?php echo $data['ServiceName']; ?></a:ServiceName>
                <a:TransactionId><?php echo $data['TransactionId']; ?></a:TransactionId>
            </data>
        </CaptureTransaction>
    </s:Body>
</s:Envelope>