<?php
/**
 * A driver interface
 */
interface Payment_Process2_Driver {

    /**
     * Class constructor
     *
     * Initializes the driver's default state
     */
    public function __construct($options = array(), HTTP_Request2 $request = null);


    /**
     * Translates an action into a localised string
     *
     * @param string $action One of the Payment_Process2::ACTION_* constants
     *
     * @return string|false Returns false when it cannot translate
     */
    public function translateAction($action);

    public function process();
    public function getStatus();

    /**
     * Create a hash suitable for use with HTTP_Request2::addPostParameter();
     *
     * array('key' => 'value')
     *
     * @return mixed[]
     */
    public function prepareRequestData();
}