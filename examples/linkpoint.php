<?php

require_once 'Payment/Process2.php';

$options = array();

// If you have a test store on the staging server uncomment these
// $options['host'] = 'staging.linkpt.net';
// $options['port'] = '1129';

// Path to your keyfile (the pem file given to you by linkpiont)
$options['keyfile'] = '/path/to/your/keyfile.pem';

$process = Payment_Process::factory('LinkPoint',$options);
if (!PEAR::isError($process)) {
    $process->_debug = true;
    $process->login = 'xxxxxxxxxx'; // Your linkpoint store ID
    $process->password = '12345678'; // Your store's password
    $process->action = PAYMENT_PROCESS2_ACTION_AUTHONLY;
    $process->amount = 1.00;

    $card = Payment_Process2_Type::factory('CreditCard');
    if (!PEAR::isError($card)) {
        $card->type = PAYMENT_PROCESS2_CC_VISA;
        $card->invoiceNumber = 112345145;
        $card->customerId = 1461264151;
        $card->cardNumber = '411111111111111';
        $card->expDate = '08/2008';
        $card->zip = '98123';
        $card->cvv = '444';

        if (Payment_Process2_Type::isValid($card)) {
            if(!$process->setPayment($card)) {
                die("Unable to set payment\n");
            }

            $result = $process->process();
            if (PEAR::isError($result)) {
                echo "\n\n";
                echo $result->getMessage()."\n";
            } else {
                print_r($result);
                echo "\n";
                echo "---------------------- RESPONSE ------------------------\n";
                echo $result->getMessage()."\n";
                echo $result->getCode()."\n";
                $validate = $result->validate();
                if(!PEAR::isError($validate)) {
                    echo "All good\n";
                } else {
                    echo "ERROR: ".$validate->getMessage()."\n";
                }

                echo "---------------------- RESPONSE ------------------------\n";
            }
        } else {
            echo 'Something is wrong with your card!'."\n";
        }
    } else {
      echo $card->getMessage()."\n";
    }
} else {
    echo $payment->getMessage()."\n";
}

?>
