<?php
require_once 'Payment/Process2/Result.php';

class Payment_Process2_Result_AuthorizeNet extends Payment_Process2_Result {

    var $_statusCodeMap = array('1' => PAYMENT_PROCESS2_RESULT_APPROVED,
                                '2' => PAYMENT_PROCESS2_RESULT_DECLINED,
                                '3' => PAYMENT_PROCESS2_RESULT_OTHER,
                                '4' => PAYMENT_PROCESS2_RESULT_REVIEW
                                );

    /**
     * AuthorizeNet status codes
     *
     * This array holds many of the common response codes. There are over 200
     * response codes - so check the AuthorizeNet manual if you get a status
     * code that does not match (see "Response Reason Codes & Response
     * Reason Text" in the AIM manual).
     *
     * @see getStatusText()
     * @access private
     */
    var $_statusCodeMessages = array(
          '1' => 'This transaction has been approved.',
          '2' => 'This transaction has been declined.',
          '3' => 'This transaction has been declined.',
          '4' => 'This transaction has been declined.',
          '5' => 'A valid amount is required.',
          '6' => 'The credit card number is invalid.',
          '7' => 'The credit card expiration date is invalid.',
          '8' => 'The credit card has expired.',
          '9' => 'The ABA code is invalid.',
          '10' => 'The account number is invalid.',
          '11' => 'A duplicate transaction has been submitted.',
          '12' => 'An authorization code is required but not present.',
          '13' => 'The merchant Login ID is invalid or the account is inactive.',
          '14' => 'The Referrer or Relay Response URL is invalid.',
          '15' => 'The transaction ID is invalid.',
          '16' => 'The transaction was not found.',
          '17' => 'The merchant does not accept this type of credit card.',
          '18' => 'ACH transactions are not accepted by this merchant.',
          '19' => 'An error occurred during processing. Please try again in 5 minutes.',
          '20' => 'An error occurred during processing. Please try again in 5 minutes.',
          '21' => 'An error occurred during processing. Please try again in 5 minutes.',
          '22' => 'An error occurred during processing. Please try again in 5 minutes.',
          '23' => 'An error occurred during processing. Please try again in 5 minutes.',
          '24' => 'The Nova Bank Number or Terminal ID is incorrect. Call Merchant Service Provider.',
          '25' => 'An error occurred during processing. Please try again in 5 minutes.',
          '26' => 'An error occurred during processing. Please try again in 5 minutes.',
          '27' => 'The transaction resulted in an AVS mismatch. The address provided does not match billing address of cardholder.',
          '28' => 'The merchant does not accept this type of credit card.',
          '29' => 'The PaymentTech identification numbers are incorrect. Call Merchant Service Provider.',
          '30' => 'The configuration with the processor is invalid. Call Merchant Service Provider.',
          '31' => 'The FDC Merchant ID or Terminal ID is incorrect. Call Merchant Service Provider.',
          '32' => 'The merchant password is invalid or not present.',
          '33' => 'Missing required field',
          '34' => 'The VITAL identification numbers are incorrect. Call Merchant Service Provider.',
          '35' => 'An error occurred during processing. Call Merchant Service Provider.',
          '36' => 'The authorization was approved, but settlement failed.',
          '37' => 'The credit card number is invalid.',
          '38' => 'The Global Payment System identification numbers are incorrect. Call Merchant Service Provider.',
          '39' => 'The supplied currency code is either invalid, not supported, not allowed for this merchant or doesn\'t have an exchange rate.',
          '40' => 'This transaction must be encrypted.',
          '41' => 'FraudScreen.net fraud score is higher than threshold set by merchant',
          '42' => 'There is missing or invalid information in a required field.',
          '43' => 'The merchant was incorrectly set up at the processor. Call your Merchant Service Provider.',
          '44' => 'This transaction has been declined. Card Code filter error!',
          '45' => 'This transaction has been declined. Card Code / AVS filter error!',
          '46' => 'Your session has expired or does not exist. You must log in to continue working.',
          '47' => 'The amount requested for settlement may not be greater than the original amount authorized.',
          '48' => 'This processor does not accept partial reversals.',
          '49' => 'A transaction amount greater than $99,999 will not be accepted.',
          '50' => 'This transaction is awaiting settlement and cannot be refunded.',
          '51' => 'The sum of all credits against this transaction is greater than the original transaction amount.',
          '52' => 'The transaction was authorized, but the client could not be notified; the transaction will not be settled.',
          '53' => 'The transaction type was invalid for ACH transactions.',
          '54' => 'The referenced transaction does not meet the criteria for issuing a credit.',
          '55' => 'The sum of credits against the referenced transaction would exceed the original debit amount.',
          '56' => 'This merchant accepts ACH transactions only; no credit card transactions are accepted.',
          '57' => 'An error occurred in processing. Please try again in 5 minutes.',
          '58' => 'An error occurred in processing. Please try again in 5 minutes.',
          '59' => 'An error occurred in processing. Please try again in 5 minutes.',
          '60' => 'An error occurred in processing. Please try again in 5 minutes.',
          '61' => 'An error occurred in processing. Please try again in 5 minutes.',
          '62' => 'An error occurred in processing. Please try again in 5 minutes.',
          '63' => 'An error occurred in processing. Please try again in 5 minutes.',
          '64' => 'The referenced transaction was not approved.',
          '65' => 'This transaction has been declined.',
          '66' => 'The transaction did not meet gateway security guidelines.',
          '67' => 'The given transaction type is not supported for this merchant.',
          '68' => 'The version parameter is invalid.',
          '69' => 'The transaction type is invalid. The value submitted in x_type was invalid.',
          '70' => 'The transaction method is invalid.',
          '71' => 'The bank account type is invalid.',
          '72' => 'The authorization code is invalid.',
          '73' => 'The driver\'s license date of birth is invalid.',
          '74' => 'The duty amount is invalid.',
          '75' => 'The freight amount is invalid.',
          '76' => 'The tax amount is invalid.',
          '77' => 'The SSN or tax ID is invalid.',
          '78' => 'The Card Code (CVV2/CVC2/CID) is invalid.',
          '79' => 'The driver\'s license number is invalid.',
          '80' => 'The driver\'s license state is invalid.',
          '81' => 'The merchant requested an integration method not compatible with the AIM API.',
          '82' => 'The system no longer supports version 2.5; requests cannot be posted to scripts.',
          '83' => 'The requested script is either invalid or no longer supported.',
          '84' => 'This reason code is reserved or not applicable to this API.',
          '85' => 'This reason code is reserved or not applicable to this API.',
          '86' => 'This reason code is reserved or not applicable to this API.',
          '87' => 'This reason code is reserved or not applicable to this API.',
          '88' => 'This reason code is reserved or not applicable to this API.',
          '89' => 'This reason code is reserved or not applicable to this API.',
          '90' => 'This reason code is reserved or not applicable to this API.',
          '91' => 'Version 2.5 is no longer supported.',
          '92' => 'The gateway no longer supports the requested method of integration.',
          '93' => 'A valid country is required.',
          '94' => 'The shipping state or country is invalid.',
          '95' => 'A valid state is required.',
          '96' => 'This country is not authorized for buyers.',
          '97' => 'This transaction cannot be accepted.',
          '98' => 'This transaction cannot be accepted.',
          '99' => 'This transaction cannot be accepted.',
          '100' => 'The eCheck type is invalid.',
          '101' => 'The given name on the account and/or the account type does not match the actual account.',
          '102' => 'This request cannot be accepted.',
          '103' => 'This transaction cannot be accepted.',
          '104' => 'This transaction is currently under review.',
          '105' => 'This transaction is currently under review.',
          '106' => 'This transaction is currently under review.',
          '107' => 'This transaction is currently under review.',
          '108' => 'This transaction is currently under review.',
          '109' => 'This transaction is currently under review.',
          '110' => 'This transaction is currently under review.',
          '111' => 'A valid billing country is required.',
          '112' => 'A valid billing state/provice is required.',
          '116' => 'The authentication indicator is invalid.',
          '117' => 'The cardholder authentication value is invalid.',
          '118' => 'The combination of authentication indicator and cardholder authentication value is invalid.',
          '119' => 'Transactions having cardholder authentication values cannot be marked as recurring.',
          '120' => 'An error occurred during processing. Please try again.',
          '121' => 'An error occurred during processing. Please try again.',
          '122' => 'An error occurred during processing. Please try again.',
          '127' => 'The transaction resulted in an AVS mismatch. The address provided does not match billing address of cardholder.',
          '141' => 'This transaction has been declined.',
          '145' => 'This transaction has been declined.',
          '152' => 'The transaction was authorized, but the client could not be notified; the transaction will not be settled.',
          '165' => 'This transaction has been declined.',
          '170' => 'An error occurred during processing. Please contact the merchant.',
          '171' => 'An error occurred during processing. Please contact the merchant.',
          '172' => 'An error occurred during processing. Please contact the merchant.',
          '173' => 'An error occurred during processing. Please contact the merchant.',
          '174' => 'The transaction type is invalid. Please contact the merchant.',
          '175' => 'The processor does not allow voiding of credits.',
          '180' => 'An error occurred during processing. Please try again.',
          '181' => 'An error occurred during processing. Please try again.',
          '200' => 'This transaction has been declined.',
          '201' => 'This transaction has been declined.',
          '202' => 'This transaction has been declined.',
          '203' => 'This transaction has been declined.',
          '204' => 'This transaction has been declined.',
          '205' => 'This transaction has been declined.',
          '206' => 'This transaction has been declined.',
          '207' => 'This transaction has been declined.',
          '208' => 'This transaction has been declined.',
          '209' => 'This transaction has been declined.',
          '210' => 'This transaction has been declined.',
          '211' => 'This transaction has been declined.',
          '212' => 'This transaction has been declined.',
          '213' => 'This transaction has been declined.',
          '214' => 'This transaction has been declined.',
          '215' => 'This transaction has been declined.',
          '216' => 'This transaction has been declined.',
          '217' => 'This transaction has been declined.',
          '218' => 'This transaction has been declined.',
          '219' => 'This transaction has been declined.',
          '220' => 'This transaction has been declined.',
          '221' => 'This transaction has been declined.',
          '222' => 'This transaction has been declined.',
          '223' => 'This transaction has been declined.',
          '224' => 'This transaction has been declined.',
          '243' => 'Recurring billing is not allowed for this eCheck.Net type',
          '244' => 'This eCheck.Net type is not allowed for this Bank Account Type.',
          '245' => 'This eCheck.Net type is not allowed when using the payment gateway hosted payment form.',
          '246' => 'This eCheck.Net type is not allowed.',
          '247' => 'This eCheck.Net type is not allowed.',
          '250' => 'This transaction has been declined.',
          '251' => 'This transaction has been declined.',
          '252' => 'Your order has been received. Thank you for your business!',
          '253' => 'Your order has been received. Thank you for your business!',
          '254' => 'This transaction has been declined.',
          '261' => 'An error occurred during processing. Please try again'
    );

    var $_avsCodeMap = array(
        'A' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'B' => PAYMENT_PROCESS2_AVS_ERROR,
        'E' => PAYMENT_PROCESS2_AVS_ERROR,
        'G' => PAYMENT_PROCESS2_AVS_NOAPPLY,
        'N' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'P' => PAYMENT_PROCESS2_AVS_NOAPPLY,
        'R' => PAYMENT_PROCESS2_AVS_ERROR,
        'S' => PAYMENT_PROCESS2_AVS_ERROR,
        'U' => PAYMENT_PROCESS2_AVS_ERROR,
        'W' => PAYMENT_PROCESS2_AVS_MISMATCH,
        'X' => PAYMENT_PROCESS2_AVS_MATCH,
        'Y' => PAYMENT_PROCESS2_AVS_MATCH,
        'Z' => PAYMENT_PROCESS2_AVS_MISMATCH
    );

    var $_avsCodeMessages = array(
        'A' => 'Address matches, postal code does not',
        'B' => 'Address information not provided',
        'E' => 'Address Verification System Error',
        'G' => 'Non-U.S. Card Issuing Bank',
        'N' => 'No match on street address nor postal code',
        'P' => 'Address Verification System not applicable',
        'R' => 'Retry - System unavailable or timeout',
        'S' => 'Service not supported by issuer',
        'U' => 'Address information unavailable',
        'W' => '9-digit postal code matches, street address does not',
        'X' => 'Address and 9-digit postal code match',
        'Y' => 'Address and 5-digit postal code match',
        'Z' => '5-digit postal code matches, street address does not'
    );

    var $_cvvCodeMap = array('M' => PAYMENT_PROCESS2_CVV_MATCH,
                             'N' => PAYMENT_PROCESS2_CVV_MISMATCH,
                             'P' => PAYMENT_PROCESS2_CVV_ERROR,
                             'S' => PAYMENT_PROCESS2_CVV_ERROR,
                             'U' => PAYMENT_PROCESS2_CVV_ERROR
    );

    var $_cvvCodeMessages = array(
        'M' => 'CVV code matches',
        'N' => 'CVV code does not match',
        'P' => 'CVV code was not processed',
        'S' => 'CVV code should have been present',
        'U' => 'Issuer unable to process request',
    );

    var $_fieldMap = array('0'  => 'code',
                           '2'  => 'messageCode',
                           '3'  => 'message',
                           '4'  => 'approvalCode',
                           '5'  => 'avsCode',
                           '6'  => 'transactionId',
                           '7'  => 'invoiceNumber',
                           '8'  => 'description',
                           '9'  => 'amount',
                           '12' => 'customerId',
                           '37' => 'md5Hash',
                           '38' => 'cvvCode'
    );

    /**
     * To hold the MD5 hash returned
     *
     * @var string
     * @access private
     */
    var $_md5Hash;

    /**
     * Parses the data received from the payment gateway
     *
     * @access public
     */
    function parse()
    {
        if (empty($this->_rawResponse)) {
            return array();
        }

        $delim = $this->_request->getOption('x_delim_char');
        $encap = $this->_request->getOption('x_encap_char');

        $responseArray = explode($encap . $delim . $encap, $this->_rawResponse);
        if ($responseArray === false) {
            return array();
        }

        $count = count($responseArray) - 1;
        if ($responseArray[0]{0} == $encap) {
            $responseArray[0] = substr($responseArray[0], 1);
        }
        if (substr($responseArray[$count], -1) == $encap) {
            $responseArray[$count] = substr($responseArray[$count], 0, -1);
        }

        // Save some fields in private members
        $map = array_flip($this->_fieldMap);
        $this->_md5Hash = $responseArray[$map['md5Hash']];
        $this->_amount  = $responseArray[$map['amount']];

        $this->_mapFields($responseArray);

        // Adjust result code/message if needed based on raw code
        switch ($this->messageCode) {
            case 33:
                // Something is missing so we send the raw message back
                $this->_statusCodeMessages[33] = $this->message;
                break;
            case 11:
                // Duplicate transactions
                $this->code = PAYMENT_PROCESS2_RESULT_DUPLICATE;
                break;
            case 4:
            case 41:
            case 250:
            case 251:
                // Fraud detected
                $this->code = PAYMENT_PROCESS2_RESULT_FRAUD;
                break;
        }
    }

    /**
     * Parses the data received from the payment gateway callback
     *
     * @access public
     * @author Philippe Jausions <Philippe.Jausions@11abacus.com>
     */
    function parseCallback()
    {
        $this->code          = $this->_rawResponse['x_response_code'];
        $this->messageCode   = $this->_rawResponse['x_response_reason_code'];
        $this->message       = $this->_rawResponse['x_response_reason_text'];
        $this->approvalCode  = $this->_rawResponse['x_auth_code'];
        $this->avsCode       = $this->_rawResponse['x_avs_code'];
        $this->transactionId = $this->_rawResponse['x_trans_id'];
        $this->invoiceNumber = $this->_rawResponse['x_invoice_num'];
        $this->description   = $this->_rawResponse['x_description'];
        $this->_amount       = $this->_rawResponse['x_amount'];
        $this->customerId    = $this->_rawResponse['x_cust_id'];
        $this->_md5Hash      = $this->_rawResponse['x_MD5_Hash'];
        $this->cvvCode       = $this->_rawResponse['x_cvv2_resp_code'];

        /** @todo Fix this! */
        $map = array_flip($GLOBALS['_Payment_Process2_AuthorizeNet']);
        $this->action        = $map[strtoupper($this->_rawResponse['x_type'])];
    }

    /**
     * Validates the legitimacy of the response
     *
     * To be able to validate the response, the md5Value option
     * must have been set in the processor. If the md5Value is not set this
     * function will fail gracefully, but this MAY CHANGE IN THE FUTURE!
     *
     * Check if the response is legitimate by matching MD5 hashes.
     * To avoid MD5 mismatch while the key is being renewed
     * the md5Value can be an array with 2 indexes: "new" and "old"
     * respectively holding the new and old MD5 values.
     *
     * Note: If you're having problem passing this check: be aware that
     * the login name is CASE-SENSITIVE!!! (even though you can log in
     * using it all lowered case...)
     *
     * @return mixed TRUE if response is legitimate, FALSE if not, PEAR_Error on error
     * @access public
     * @author Philippe Jausions <Philippe.Jausions@11abacus.com>
     */
    function isLegitimate()
    {
        $md5Value = $this->_request->getOption('md5Value');
        if (!$md5Value) {
            // For now fail gracefully if it is not set.
            return true;
        }

        $fields = $this->_request->login . $this->transactionId
                    . $this->_amount;
        if (is_array($md5Value)) {
            if (strcasecmp($this->_md5Hash, md5($md5Value['new'] . $fields)) == 0 ||
                strcasecmp($this->_md5Hash, md5($md5Value['old'] . $fields)) == 0) {

                return true;
            }
        } elseif (strcasecmp($this->_md5Hash, md5($md5Value . $fields)) == 0) {
            return true;
        }
        return false;
    }
}
