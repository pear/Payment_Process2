<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * LinkPoint processor
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. The name of the authors may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHORS ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
 * EVENT SHALL THE AUTHORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Payment
 * @package   Payment_Process2
 * @author    Joe Stump <joe@joestump.net>
 * @copyright 1997-2005 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   CVS: $Revision$
 * @link      http://pear.php.net/package/Payment_Process2
 * @link      http://www.linkpoint.net/
 */


require_once 'Payment/Process2.php';
require_once 'Payment/Process2/Common.php';
require_once 'Payment/Process2/Driver.php';
require_once 'Payment/Process2/Result/LinkPoint.php';


/**
 * Payment_Process2_LinkPoint
 *
 * This is a processor for LinkPoint's merchant payment gateway.
 * (http://www.linkpoint.net/)
 *
 * *** WARNING ***
 * This is BETA code, and has not been fully tested. It is not recommended
 * that you use it in a production envorinment without further testing.
 *
 * @package Payment_Process2
 * @author Joe Stump <joe@joestump.net>
 * @version @version@
 */
class Payment_Process2_LinkPoint extends Payment_Process2_Common implements Payment_Process2_Driver
{
    /**
     * Front-end -> back-end field map.
     *
     * This array contains the mapping from front-end fields (defined in
     * the Payment_Process class) to the field names DPILink requires.
     *
     * @see _prepare()
     * @access private
     */
    var $_fieldMap = array(
        // Required
        'login'         => 'configfile',
        'action'        => 'ordertype',
        'invoiceNumber' => 'oid',
        'customerId'    => 'x_cust_id',
        'amount'        => 'chargetotal',
        'name'          => '',
        'zip'           => 'zip',
        // Optional
        'company'       => 'company',
        'address'       => 'address1',
        'city'          => 'city',
        'state'         => 'state',
        'country'       => 'country',
        'phone'         => 'phone',
        'email'         => 'email',
        'ip'            => 'ip',
    );

    /**
    * $_typeFieldMap
    *
    * @author Joe Stump <joe@joestump.net>
    * @access protected
    */
    var $_typeFieldMap = array(

           'CreditCard' => array(

                    'cardNumber' => 'cardnumber',
                    'cvv'        => 'cvm',
                    'expDate'    => 'expDate'

           ),

           'eCheck' => array(

                    'routingCode'   => 'routing',
                    'accountNumber' => 'account',
                    'type'          => 'type',
                    'bankName'      => 'bank',
                    'name'          => 'name',
                    'driversLicense'      => 'dl',
                    'driversLicenseState' => 'dlstate'

           )
    );

    /**
     * Default options for this processor.
     *
     * @see Payment_Process::setOptions()
     * @access private
     */
    var $_defaultOptions = array(
         'host'   => 'secure.linkpt.net',
         'port'   => '1129',
         'result' => 'LIVE'
    );

    /**
     * Has the transaction been processed?
     *
     * @type boolean
     * @access private
     */
    var $_processed = false;

    /**
     * Constructor.
     *
     * @param  array  $options  Class options to set.
     * @see Payment_Process::setOptions()
     * @return void
     */
    function __construct($options = array(), HTTP_Request2 $request = null)
    {
        parent::__construct($options, $request);
        $this->_driver = 'LinkPoint';
    }

    public function prepareRequestData() {
        return array();
    }

    function validate() {
        if (empty($this->_options['keyfile']) ||
            !file_exists($this->_options['keyfile'])) {
            throw new Payment_Process2_Exception('Invalid key file');
        }

        if (empty($this->_options['authorizeUri'])) {
            throw new Payment_Process2_Exception('Invalid authorizeUri');
        }

        return parent::validate();
    }

    /**
     * Process the transaction.
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @return mixed Payment_Process2_Result on success, PEAR_Error on failure
     */
    function process()
    {
        // Sanity check
        $this->validate();

        // Prepare the data
        $this->_prepare();

        $xml = $this->renderRequestDocument();

        $url = 'https://'.$this->_options['host'].':'.$this->_options['port'].
               '/LSGSXML';

        $request = clone $this->_request;
        $request->setURL($url);
        $request->addPostParameter($this->prepareRequestData());

        $request->setMethod('post');
        $request->setBody($xml);

        /** If we are empty, raise exception? */
        if (!empty($this->_options['keyfile'])) {
            $request->setConfig('ssl_local_cert', $this->_options['keyfile']);
        }

        // LinkPoint's staging server has a boned certificate. If they are
        // testing against staging we need to turn off SSL host verification.
        if ($this->_options['host'] == 'staging.linkpt.net') {
            $request->setConfig('ssl_verify_peer', false);
            $request->setConfig('ssl_verify_host', false);
        }


        $result = $request->send();

        $responseBody = trim($result->getBody());
        $this->_processed = true;

        $response = Payment_Process2_Result::factory($this->_driver,
                                                     $responseBody,
                                                     $this);


        $response->parse();


        return $response;
    }

    /**
     * Prepare the POST query string.
     *
     * @access private
     * @return string The query string
     */
    function renderRequestDocument()
    {

        $data = array_merge($this->_options, $this->_data);

        $xml  = '<!-- Payment_Process order -->'."\n";
        $xml .= '<order>'."\n";
        $xml .= '<merchantinfo>'."\n";
        $xml .= '  <configfile>'.$data['configfile'].'</configfile>'."\n";
        $xml .= '  <keyfile>'.$data['keyfile'].'</keyfile>'."\n";
        $xml .= '  <host>'.$data['authorizeUri'].'</host>'."\n";
        $xml .= '  <appname>PEAR Payment_Process</appname>'."\n";
        $xml .= '</merchantinfo>'."\n";
        $xml .= '<orderoptions>'."\n";
        $xml .= '  <ordertype>'.$data['ordertype'].'</ordertype>'."\n";
        $xml .= '  <result>'.$data['result'].'</result>'."\n";
        $xml .= '</orderoptions>'."\n";
        $xml .= '<payment>'."\n";
        $xml .= '  <subtotal>'.$data['chargetotal'].'</subtotal>'."\n";
        $xml .= '  <tax>0.00</tax>'."\n";
        $xml .= '  <shipping>0.00</shipping>'."\n";
        $xml .= '  <chargetotal>'.$data['chargetotal'].'</chargetotal>'."\n";
        $xml .= '</payment>'."\n";

        // Set payment method to eCheck if our payment type is eCheck.
        // Default is Credit Card.
        $data['x_method'] = 'CC';

        if ($this->_payment instanceof Payment_Process2_Type_eCheck) {
            throw new Payment_Process2_Exception('eCheck not currently supported',
                                    Payment_Process2::ERROR_NOTIMPLEMENTED);

            /*
            $xml .= '<telecheck>'."\n";
            $xml .= '  <routing></routing>'."\n";
            $xml .= '  <account></account>'."\n";
            $xml .= '  <checknumber></checknumber>'."\n";
            $xml .= '  <bankname></bankname>'."\n";
            $xml .= '  <bankstate></bankstate>'."\n";
            $xml .= '  <dl></dl>'."\n";
            $xml .= '  <dlstate></dlstate>'."\n";
            $xml .= '  <accounttype>pc|ps|bc|bs</accounttype>'."\n";
            $xml .= '<telecheck>'."\n";
            */
        }

        if ($this->_payment instanceof Payment_Process2_Type_CreditCard) {
            $xml .= '<creditcard>'."\n";
            $xml .= '  <cardnumber>'.$data['cardnumber'].'</cardnumber>'."\n";
            list($month,$year) = explode('/',$data['expDate']);
            if (strlen($year) == 4) {
                $year = substr($year,2);
            }

            $month = sprintf('%02d',$month);

            $xml .= '  <cardexpmonth>'.$month.'</cardexpmonth>'."\n";
            $xml .= '  <cardexpyear>'.$year.'</cardexpyear>'."\n";
            if (strlen($data['cvm'])) {
                $xml .= '  <cvmvalue>'.$data['cvm'].'</cvmvalue>'."\n";
                $xml .= '  <cvmindicator>provided</cvmindicator>'."\n";
            }
            $xml .= '</creditcard>'."\n";
        }

        if (isset($this->_payment->firstName) &&
            isset($this->_payment->lastName)) {
            $xml .= '<billing>'."\n";
            $xml .= '  <userid>'.$this->_payment->customerId.'</userid>'."\n";
            $xml .= '  <name>'.$this->_payment->firstName.' '.$this->_payment->lastName.'</name>'."\n";
            $xml .= '  <company>'.$this->_payment->company.'</company>'."\n";
            $xml .= '  <address1>'.$this->_payment->address.'</address1>'."\n";
            $xml .= '  <city>'.$this->_payment->city.'</city>'."\n";
            $xml .= '  <state>'.$this->_payment->state.'</state>'."\n";
            $xml .= '  <zip>'.$this->_payment->zip.'</zip>'."\n";
            $xml .= '  <country>'.$this->_payment->country.'</country>'."\n";
            $xml .= '  <phone>'.$this->_payment->phone.'</phone>'."\n";
            $xml .= '  <email>'.$this->_payment->email.'</email>'."\n";
            $xml .= '  <addrnum>'.$this->_payment->address.'</addrnum>'."\n";
            $xml .= '</billing>'."\n";
        }

        $xml .= '</order>'."\n";

        return $xml;
    }

    public function translateAction($action) {
        switch ($action) {
            case Payment_Process2::ACTION_NORMAL:
                return 'SALE';

            case Payment_Process2::ACTION_AUTHONLY:
                return 'PREAUTH';

            case Payment_Process2::ACTION_POSTAUTH:
                return 'POSTAUTH';
        }

        return false;
    }

    public function getStatus() {
        return false;
    }
}


?>
