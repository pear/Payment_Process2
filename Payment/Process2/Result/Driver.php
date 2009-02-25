<?php
/**
 * A driver interface
 */
interface Payment_Process2_Result_Driver {

    /**
     * Constructor
     *
     * @param string $rawResponse     The raw response data returned by the http client typically during Payment_Process2_Driver::process()
     * @param Payment_Process2_Driver A payment process driver of some description, typically of the same class.
     */
    public function __construct($rawResponse,  Payment_Process2_Common $request);

    /**
     * Validate a result
     */
    public function validate();

    /**
     * Parse the result and populate the appropriate fields in this object
     *
     * @return mixed
     * @abstract
     * @author Joe Stump <joe@joestump.net>
     * @access public
     */
    public function parse();

    /**
     * Return a code constant.
     *
     * Must be called after parse()
     *
     * @return PAYMENT_PROCESS2_RESULT_*
     */
    public function getCode();

    /**
     * Return a human readable message.
     *
     * Must be called after parse()
     *
     * @return string
     */
    public function getMessage();


    public function getAVSCode();

    public function getAVSMessage();

    public function getCvvCode();

    public function getCvvMessage();

    /**
     * Maps an array of data parsed from the response
     * and populates internal fields.
     *
     * @todo Destroy this method in favor of a per-child, simpler way
     */
    function _mapFields($responseArray);
}