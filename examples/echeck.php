<?php

  require_once 'Payment/Process2.php';

  $options = array();
  $options['x_delim_data'] = 'TRUE';

  $process = Payment_Process::factory('AuthorizeNet',$options);
  if (!PEAR::isError($process)) {
      $process->_debug = true;
      $process->login = 'username';
      $process->password = 'password';
      $process->action = PAYMENT_PROCESS2_ACTION_AUTHONLY;
      $process->amount = 9.95;

      $check = Payment_Process2_Type::factory('eCheck');
      if (!PEAR::isError($check)) {
          $check->invoiceNumber = 112345145;
          $check->customerId = 1461264151;
          $check->firstName = 'Jose';
          $check->lastName = 'Perez';
          $check->type = PAYMENT_PROCESS2_CK_CHECKING;
          $check->bankName = 'Bank of USA';
          $check->accountNumber = '2222222222';
          $check->routingCode = '2222222222';

          if (Payment_Process2_Type::isValid($check)) {
              if(!$process->setPayment($check)) {
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
