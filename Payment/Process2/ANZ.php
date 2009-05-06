<?php
require_once 'Payment/Process2.php';
require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'HTTP/Request2.php';

class Payment_Process2_ANZ extends Payment_Process2_Common implements Payment_Process2_Driver {

    var $transactionReference = '';

    /**
     * Front-end -> back-end field map.
     *
     * This array contains the mapping from front-end fields (defined in
     * the Payment_Process class) to the field names TrustCommerce requires.
     *
     * @see _prepare()
     * @access private
     * @todo Swap to protected
     */
    var $_fieldMap = array(
        // Required
        'login' => 'vpc_Merchant',
        'password' => 'vpc_AccessCode',
        'action' => 'vpc_Command',
        'amount' => 'vpc_Amount',
        'invoiceNumber' => 'vpc_OrderInfo',
        'transactionReference' => 'vpc_MerchTxnRef',
    );

    /**
    * $_typeFieldMap
    *
    * @access protected
     * @todo Swap to protected
    */
    var $_typeFieldMap = array(
        'CreditCard' => array(
            'cardNumber' => 'vpc_CardNum',
            'cvv' => 'vpc_CardSecurityCode',
            'expDate' => 'vpc_CardExp',
        ),
    );

    /**
     * @todo Swap to protected
     */
    var $_defaultOptions = array(
        'url' => 'https://migs.mastercard.com.au/vpcdps',
    );

    /**
     * Constructor.
     *
     * @param  array  $options  Class options to set.
     * @see Payment_Process::setOptions()
     * @return void
     */
    public function __construct($options = array(), HTTP_Request2 $request = null)
    {
        parent::__construct($options, $request);
        $this->_driver = 'ANZ';

        $this->_makeRequired('login', 'password', 'action', 'amount', 'invoiceNumber', 'transactionReference');
    }

    /**
     * @return Payment_Process2_Result_ANZ
     */
    public function process()
    {
        // Sanity check
        $this->validate();

        // Prepare the data
        $this->_prepare();

        $request = clone $this->_request;
        $request->setUrl($this->getOption('url'));
        $request->setMethod(HTTP_Request2::METHOD_POST);

        $request->addPostParameter($this->prepareRequestData());

        $response = $request->send();
        if ($response->getStatus() != 200) {
            throw new Payment_Process2_Exception("Payment Gateway HTTP Error {$response->getStatus()} {$response->getReasonPhrase()}");
        }

        $result = Payment_Process2_Result::factory($this->_driver,
                                         $response->getBody(),
                                         $this);

        $result->parse();

        return $result;
    }

    public function prepareRequestData() {
        $data = array();
        $data['vpc_Version'] = '1';

        foreach ($this->_data as $name => &$value) {
            if (!substr($name, 0, 4) == 'vpc_') {
                continue;
            }
            if ($name == 'vpc_CardExp') {
                list($month, $year) = explode('/', $value);
                $value = substr($year, -2) . $month;
            }
            if ($name == 'vpc_Amount') {
                $value = floor($value*100);
            }
            $data[$name] = $value;
        }

        return $data;
    }

    public function translateAction($action) {
        switch ($action) {
            case Payment_Process2::ACTION_NORMAL:
                return 'pay';
        }

        return false;
    }

    public function getStatus() {
        return false;
    }

    public function _validateTransactionReference() {
        if (empty($this->transactionReference)) {
            throw new Payment_Process2_Exception('Missing transaction reference');
        }

        return true;
    }

    public function _validateAmount() {
        if (empty($this->amount) || $this->amount < 0) {
            throw new Payment_Process2_Exception('Invalid amount');
        }

        return true;
    }
}
