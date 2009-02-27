<?php

  if(!function_exists('is_a')) {
      function is_a($object,$string) {
          if(stristr(get_class($object),$string) ||
             stristr(get_parent_class($object),$string)) {
              return TRUE;
          } else {
              return FALSE;
          }
      }
  }

  require_once 'Payment/Process2.php';

  $options = array();
  $options['debug'] = TRUE;

  $process = Payment_Process::factory('TrustCommerce',$options);
  $process->_debug = true;
  $process->login = 'TestMerchant';
  $process->password = 'password';
  $process->action = PAYMENT_PROCESS2_ACTION_NORMAL;
  $process->amount = 99.99;

  $card = Payment_Process2_Type::factory('CreditCard');
  $card->type = PAYMENT_PROCESS2_CC_VISA;
  $card->cardNumber = '4111111111111111';
  $card->expDate = '01/2005';
  if(!$process->setPayment($card)) {
  	die("Unable to set payment\n");
  }
  $result = $process->process();
  echo "---------------------- RESPONSE ------------------------\n";
  echo 'Processor result: ';
  echo $result->getCode()." - ";
  echo $result->getMessage();
  echo "---------------------- RESPONSE ------------------------\n";
?>
