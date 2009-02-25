<?php
require_once 'Payment/Process2/Result.php';

class Payment_Process2_Result_Transfirst extends Payment_Process2_Result {

    /**
     * Transfirst status codes.
     *
     * This array holds every possible status returned by the Transfirst gateway.
     *
     * See the Transfirst documentation for more details on each response.
     *
     * @see getStatusText()
     * @access private
     */
    var $_statusCodeMessages = array(
        '00' => "Approved",
        '01' => "Refer to issuer",
        '02' => "Refer to issuer - Special condition",
        '03' => "Invalid merchant ID",
        '04' => "Pick up card",
        '05' => "Declined",
        '06' => "General error",
        '07' => "Pick up card - Special condition",
        '13' => "Invalid amount",
        '14' => "Invalid card number",
        '15' => "No such issuer",
        '19' => "Re-enter transaction",
        '21' => "Unable to back out transaction",
        '28' => "File is temporarily unavailable",
        '39' => "No credit account",
        '41' => "Pick up card - Lost",
        '43' => "Pick up card - Stolen",
        '51' => "Insufficient funds",
        '54' => "Expired card",
        '57' => "Transaction not permitted - Card",
        '61' => "Amount exceeds withdrawal limit",
        '62' => "Invalid service code, restricted",
        '65' => "Activity limit exceeded",
        '76' => "Unable to locate, no match",
        '77' => "Inconsistent data, rev. or repeat",
        '78' => "No account",
        '80' => "Invalid date",
        '85' => "Card OK",
        '91' => "Issuer or switch is unavailable",
        '93' => "Violation, cannot complete",
        '96' => "System malfunction",
        '98' => "No matching transaction to void",
        '99' => "System timeout",
        'L0' => "General System Error - Contact Transfirst Account Exec.",
        'L1' => "Invalid or missing account number",
        'L2' => "Invalid or missing password",
        'L3' => "Expiration Date is not formatted correctly",
        'L4' => "Reference number not found",
        'L6' => "Order number is required but missing",
        'L7' => "Wrong transaction code",
        'L8' => "Network timeout",
        'L14' => "Invalid card number",
        'S5' => "Already settled",
        'S6' => "Not authorized",
        'S7' => "Declined",
        'V6' => "Invalid transaction type",
        'V7' => "Declined",
        'V8' => "Already voided",
        'V9' => "Already posted"
    );

    var $_avsCodeMap = array(
        'A' => "Address match",
        'E' => "Ineligible",
        'N' => "No match",
        'R' => "Retry",
        'S' => "Service unavailable",
        'U' => "Address information unavailable",
        'W' => "9-digit zip match",
        'X' => "Address and 9-digit zip match",
        'Y' => "Address and 5-digit zip match",
        'Z' => "5-digit zip match"
    );

    /**
     * Status code map
     *
     * This contains a map from the Processor-specific result codes to the generic
     * P_P codes. Anything not defined here is treated as a DECLINED result by
     * validate()
     *
     * @type array
     * @access private
     */
    var $_statusCodeMap = array(
        '00' => PAYMENT_PROCESS2_RESULT_APPROVED,
        '05' => PAYMENT_PROCESS2_RESULT_DECLINED,
        'V7' => PAYMENT_PROCESS2_RESULT_DECLINED
    );

    var $_aciCodes = array(
        'A' => "CPS Qualified",
        'E' => "CPS Qualified  -  Card Acceptor Data was submitted in the authorization  request.",
        'M' => "Reserved - The card was not present and no AVS request for International transactions",
        'N' => "Not CPS Qualified",
        'V' => "CPS Qualified ? Included an address verification request in the authorization request."
    );

    var $_authSourceCodes = array(
        ' ' => "Terminal doesn't support",
        '0' => "Exception File",
        '1' => "Stand in Processing, time-out response",
        '2' => "Loss Control System (LCS) response provided",
        '3' => "STIP, response provided, issuer suppress inquiry mode",
        '4' => "STIP, response provided, issuer is down",
        '5' => "Response provided by issuer",
        '9' => "Automated referral service (ARS) stand-in"
    );

    var $_fieldMap = array(
        0  => '_null',                    // TF Internal Message Format
        1  => '_acctNo',                  // TF Account number
        2  => '_transactionCode',         // The transaction code from the request message passed by the original request.
        3  => 'transactionId',            // Assigned by TF used to uniquely identify transaction.
        4  => '_mailOrder',               // Mail Order Identifier
        5  => '_ccAcctNo',                // The credit card account number passed by the original request.
        6  => '_ccExpDate',               // The Expiration Date passed by the original request. The field is formatted YYMM (Year, Month)
        7  => '_authAmount',              // An eight-digit value, which denotes the dollar amount passed to TF, without a decimal. ( DDDDDDCC )
        8  => '_authDate',                // A six-digit value, which denotes the date the authorization, was attempted.  The field is formatted YYMMDD. (Year, Month, Date)
        9  => '_authTime',                // A six-digit value, which denotes the time the authorization, was attempted.  The field is formatted HHMMSS.  (Hour, Minute, Second)
        10 => 'messageCode',              // A two-digit value, which indicates the result of the authorization request.  Used to determine if the card was authorized, declined or timed out.
        11 => 'customerId',               // The Customer Number passed by the original request
        12 => 'invoiceNumber',            // The Order Number passed by the original request.
        13 => '_urn',                     // A number that uniquely identifies an individual transaction.  Assigned by TF and can be used when referencing a specific transaction.
        14 => '_authResponse',            // A number provided by the issuing bank indicating the authorization is valid and funds have been reserved for transfer to the merchants account at a later time.
        15 => '_authSource',              // A code that defines the source where an authorization was captured.
        16 => '_authCharacteristic',      // A code that defines the qualification level for the authorized transaction.
        17 => 'approvalCode',             // Assigned by Visa or MasterCard, used to uniquely identify and link together all related information and used to authorize and clear a transaction.
        18 => '_validationCode',          // Assigned by V.I.P. System that is used to determine the accuracy of the authorization data.
        19 => '_sicCatCode',              // A merchants industry classification.  Example - Mail Order/Phone Order Merchants (Direct Market) = 5969.
        20 => '_currencyCode',            // 840 indicate US Currency to date this is the only valid value.
        21 => 'avsCode',                  // A value that indicates the level of Address Verification that was validated.
        22 => '_merchantStoreNo',         // Identifies the specific terminal used at a location  1-4 Merchant store #, 5-8 specific terminal at store.
        23 => 'cvvCode'                   // A two-digit value, indicating the result of the card verification based on the CVV2 code provided by the cardholder.
    );

    /**
     * Constructor.
     *
     * @param  string  $rawResponse  The raw response from the gateway
     * @return mixed boolean true on success, PEAR_Error on failure
     */
    function __construct($rawResponse)
    {
        $res = $this->_validateResponse($rawResponse);
        if (!$res || PEAR::isError($res)) {
            if (!$res) {
                $res = PEAR::raiseError("Unable to validate response body");
            }
            return $res;
        }

        $this->_rawResponse = $rawResponse;
        $res = $this->_parseResponse();
    }

    function getAuthSource()
    {
        return @$this->_authSourceCodes[$this->_authSource];
    }

    function getAuthCharacteristic()
    {
        return @$this->_aciCodes[$this->_authChar];
    }

    function getCode()
    {
        return $this->_statusCodeMap[$this->messageCode];
    }

    /**
     * Parse Transfirst (DPILink) R1 response string.
     *
     * This function parses the response the gateway sends back, which is in
     * pipe-delimited format.
     *
     * @return void
     */
    function _parseResponse()
    {
        $this->_mapFields(explode('|', $this->_rawResponse));
    }

    /**
     * Validate a R1 response.
     *
     * @return boolean
     */
    function _validateResponse($resp)
    {
        if (strlen($resp) > 160)
            return false;

        // FIXME - add more tests

        return true;
    }
}