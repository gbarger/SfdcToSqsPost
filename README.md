These php pages were created to be able to submit data from Salesforce outbound messages into Amazon AWS SQS Queues. Just put these pages up on a server, then point the URL for your oubound message at the sqs-push.php page with the required params.

For example, your call should look something like this:
https://website.com/sqs-push.php?region=us-west-2&queue=sfdc-queue&accountNumber=555555555555&messageBody=AccountOutboundMessage

The region, queue, and account number can be located in the full queue url. The message body is whatever message you want to use. For example, you could put an identifier there to let you know the outbound message rule you used.