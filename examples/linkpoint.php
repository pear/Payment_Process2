<?php

require_once 'Payment/Process2.php';

$options = array();

// If you have a test store on the staging server uncomment these
// $options['host'] = 'staging.linkpt.net';
// $options['port'] = '1129';

// Path to your keyfile (the pem file given to you by linkpiont)
$options['keyfile'] = '/path/to/your/keyfile.pem';

$process = Payment_Process::factory('LinkPoint',$options);

$process->_debug = true;
$process->login = 'xxxxxxxxxx'; // Your linkpoint store ID
$process->password = '12345678'; // Your store's password
$process->action = Payment_Process2::ACTION_AUTHONLY;
$process->amount = 1.00;

$card = Payment_Process2_Type::factory('CreditCard');
$card->type = Payment_Process2_Type::CC_VISA;
$card->invoiceNumber = 112345145;
$card->customerId = 1461264151;
$card->cardNumber = '411111111111111';
$card->expDate = '08/2008';
$card->zip = '98123';
$card->cvv = '444';


$process->setPayment($card);

$result = $process->process();

print_r($result);
echo "\n";
echo "---------------------- RESPONSE ------------------------\n";
echo $result->getMessage()."\n";
echo $result->getCode()."\n";
$validate = $result->validate();

echo "---------------------- RESPONSE ------------------------\n";

?>
