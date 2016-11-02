<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetTransactionDetails xmlns="http://schemas.wiredge.se/payment/api/v2">
            <data xmlns:a="http://schemas.wiredge.se/payment/api/v2/objects" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <a:Checksum><?php echo $data['Checksum']; ?></a:Checksum>
                <a:ServiceName><?php echo $data['ServiceName']; ?></a:ServiceName>
                <a:Timestamp><?php echo $data['Timestamp']; ?></a:Timestamp>
                <a:TransactionId><?php echo $data['TransactionId']; ?></a:TransactionId>
            </data>
        </GetTransactionDetails>
    </s:Body>
</s:Envelope>
