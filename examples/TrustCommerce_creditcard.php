<?php
require_once 'Payment/Process2.php';
require_once 'HTTP/Request2.php';

$options = array();
$options['debug'] = TRUE;

$request = new HTTP_Request2();
$request->setConfig('ssl_verify_peer', false); // Remove this line if using in production, install the certificate

$process = Payment_Process2::factory('TrustCommerce', $options);
$process->setRequest($request);

$process->_debug = true;
$process->login = 'TestMerchant';
$process->password = 'password';
$process->action = PAYMENT_PROCESS2_ACTION_NORMAL;
$process->amount = 99.99;

$card = Payment_Process2_Type::factory('CreditCard');
$card->setDate(strtotime('2004-01-01'));
$card->type = Payment_Process2_Type::CC_VISA;
$card->cardNumber = '4111111111111111';
$card->expDate = '01/2005';

$process->setPayment($card);
$result = $process->process();

echo 'Processor result: ' . "\n";
echo $result->getCode()." - ";
echo $result->getMessage() . "\n";


