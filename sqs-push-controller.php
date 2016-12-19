<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST')
    $formData = $_POST;
elseif ($_SERVER['REQUEST_METHOD'] == 'GET')
    $formData = $_GET;

function pushToSQS($formData)
{
    if (isset($formData))
    {
        $postBody = file_get_contents('php://input');
        $xmlBody = new SimpleXMLElement($postBody);
        $orgId = "";
        $actionId = "";
        $sessionId = "";
        $enterpriseUrl = "";
        $partnerUrl = "";
        $region = $_GET['region'];
        $accountNumber = $_GET['accountNumber'];
        $queue = $_GET['queue'];
        $messageBody = $_GET['messageBody'];

        $successResponse = 'true';

        $baseUrl = "https://sqs." . $region . ".amazonaws.com/" . $accountNumber . "/" . $queue . "?Action=SendMessage&MessageBody=" . $messageBody;

        foreach ($xmlBody->xpath('//soapenv:Body') as $body)
        {
            $notifications = $body->notifications;

            // notification level fields
            $orgId = $notifications->OrganizationId;
            $actionId = $notifications->ActionId;
            $sessionId = $notifications->SessionId;
            $enterpriseUrl = $notifications->EnterpriseUrl;
            $partnerUrl = $notifications->PartnerUrl;

            $orgParams .= "&MessageAttribute.1.Name=OrganizationId"
                        . "&MessageAttribute.1.Value.StringValue=" . $orgId
                        . "&MessageAttribute.1.Value.DataType=String";

            $notificationParamCounter = 2;
            if (!empty($actionId) && $actionId != "")
            {
                $orgParams .= "&MessageAttribute." . $notificationParamCounter . ".Name=ActionId"
                            . "&MessageAttribute." . $notificationParamCounter . ".Value.StringValue=" . $actionId
                            . "&MessageAttribute." . $notificationParamCounter . ".Value.DataType=String";

                $notificationParamCounter++;
            }

            if (!empty($sessionId) && $sessionId != "")
            {
                $orgParams .= "&MessageAttribute." . $notificationParamCounter . ".Name=SessionId"
                            . "&MessageAttribute." . $notificationParamCounter . ".Value.StringValue=" . $sessionId
                            . "&MessageAttribute." . $notificationParamCounter . ".Value.DataType=String";

                $notificationParamCounter++;
            }

            if (!empty($enterpriseUrl) && $enterpriseUrl != "")
            {
                $orgParams .= "&MessageAttribute." . $notificationParamCounter . ".Name=EnterpriseUrl"
                            . "&MessageAttribute." . $notificationParamCounter . ".Value.StringValue=" . $enterpriseUrl
                            . "&MessageAttribute." . $notificationParamCounter . ".Value.DataType=String";

                $notificationParamCounter++;
            }

            if (!empty($partnerUrl) && $partnerUrl != "")
            {
                $orgParams .= "&MessageAttribute." . $notificationParamCounter . ".Name=PartnerUrl"
                            . "&MessageAttribute." . $notificationParamCounter . ".Value.StringValue=" . $partnerUrl
                            . "&MessageAttribute." . $notificationParamCounter . ".Value.DataType=String";

                $notificationParamCounter++;
            }

            foreach ($notifications->Notification as $notificationMessage)
            {
                $sObject = $notificationMessage->sObject;
                $sObjectType = $sObject->attributes('xsi', TRUE)->type;

                $paramCounter = $notificationParamCounter;
                $otherParams = "";
                foreach ($sObject->children('sf', TRUE) as $field=>$value)
                {
                    $otherParams .= "&MessageAttribute." . $paramCounter . ".Name=" . $field
                                  . "&MessageAttribute." . $paramCounter . ".Value.StringValue=" . urlencode($value)
                                  . "&MessageAttribute." . $paramCounter . ".Value.DataType=String";

                    $paramCounter++;
                }

                $sendUrl = $baseUrl . $orgParams . $otherParams;

                $submitGetToSQS = file_get_contents($sendUrl);

                if (strpos($submitGetToSQS, 'MessageId') === false && strpos($submitGetToSQS, 'RequestId') === false) 
                {
                    $successResponse = 'false';
                }
            }
        }

        echo '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:out="http://soap.sforce.com/2005/09/outbound"><soapenv:Header/><soapenv:Body><out:notificationsResponse><out:Ack>'. $successResponse . '</out:Ack></out:notificationsResponse></soapenv:Body></soapenv:Envelope>';
    }
}