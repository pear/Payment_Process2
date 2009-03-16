<?php
require_once 'Payment/Process2.php';

$options = array();
$options['x_test_request'] = 'TRUE';
$options['x_delim_data'] = 'TRUE';
$options['avsCheck'] = true;
$options['cvvCheck'] = true;

$process = Payment_Process::factory('AuthorizeNet',$options);

$process->_debug = true;
$process->login = 'username';
$process->password = 'password';
$process->action = PAYMENT_PROCESS2_ACTION_AUTHONLY;
$process->amount = 1.00;

$card = Payment_Process2_Type::factory('CreditCard');

$card->type = Payment_Process2_Type::CC_VISA;
$card->invoiceNumber = 112345145;
$card->customerId = 1461264151;
$card->cardNumber = '4111111111111111';
$card->expDate = '01/2005';
$card->zip = '48197';
$card->cvv = '768';

$result = Payment_Process2_Type::isValid($card);

$result = $process->setPayment($card);

$result = $process->process();

print_r($result);
echo "\n";
echo "---------------------- RESPONSE ------------------------\n";
echo $result->getMessage()."\n";
echo $result->getCode()."\n";
$validate = $result->validate();


echo "---------------------- RESPONSE ------------------------\n";


?>
