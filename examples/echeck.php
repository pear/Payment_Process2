<?php

require_once 'Payment/Process2.php';

$options = array();
$options['x_delim_data'] = 'TRUE';

$process = Payment_Process::factory('AuthorizeNet',$options);

$process->_debug = true;
$process->login = 'username';
$process->password = 'password';
$process->action = Payment_Process2::ACTION_AUTHONLY;
$process->amount = 9.95;

$check = Payment_Process2_Type::factory('eCheck');

$check->invoiceNumber = 112345145;
$check->customerId = 1461264151;
$check->firstName = 'Jose';
$check->lastName = 'Perez';
$check->type = Payment_Process2::CK_CHECKING;
$check->bankName = 'Bank of USA';
$check->accountNumber = '2222222222';
$check->routingCode = '2222222222';

$process->setPayment($check);

$result = $process->process();

print_r($result);
echo "\n";
echo "---------------------- RESPONSE ------------------------\n";
echo $result->getMessage()."\n";
echo $result->getCode()."\n";
$result->validate();

echo "---------------------- RESPONSE ------------------------\n";
