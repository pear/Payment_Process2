<?php
require_once 'PHPUnit/Framework/TestCase.php';

require_once 'Payment/Process2.php';

class Payment_Process2_Result_ANZTest extends PHPUnit_Framework_TestCase
{

    public function fetchData($file)
    {
        $path = dirname(__FILE__) . '/data/ANZ/' . $file . '.html';
        return file_get_contents($path);
    }

    public function aResult($case)
    {
        $result = Payment_Process2_Result::factory('ANZ',
                                                   $this->fetchData($case),
                                                   new Payment_Process2_Common());

        $result->parse();

        return $result;
    }

    public function testShouldCorrectlyUnderstandSuccessResponse()
    {
        $result = $this->aResult('success');

        $this->assertSame(Payment_Process2::RESULT_APPROVED, $result->getCode());
        $this->assertSame("Approved", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandCouldNotBeProcessedResponse()
    {
        $result = $this->aResult('not_processed');

        $this->assertSame(Payment_Process2::RESULT_OTHER, $result->getCode());
        $this->assertSame("Transaction could not be processed", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandDeclinedResponse()
    {
        $result = $this->aResult('declined');

        $this->assertSame(Payment_Process2::RESULT_DECLINED, $result->getCode());
        $this->assertSame("Transaction declined - contact issuing bank", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandNoReplyFromProcessingHostResponse()
    {
        $result = $this->aResult('no_reply');

        $this->assertSame(Payment_Process2::RESULT_OTHER, $result->getCode());
        $this->assertSame("No reply from processing host", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandCardHasExpiredResponse()
    {
        $result = $this->aResult('expired');

        $this->assertSame(Payment_Process2::RESULT_DECLINED, $result->getCode());
        $this->assertSame("Card has expired", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandInsufficientCreditResponse()
    {
        $result = $this->aResult('insufficient_credit');

        $this->assertSame(Payment_Process2::RESULT_DECLINED, $result->getCode());
        $this->assertSame("Insufficient credit", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandCommunicationErrorResponse()
    {
        $result = $this->aResult('communication');

        $this->assertSame(Payment_Process2::RESULT_OTHER, $result->getCode());
        $this->assertSame("Error communicating with bank", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandInvalidPanResponse()
    {
        $result = $this->aResult('invalid_pan');

        $this->assertSame(Payment_Process2::RESULT_DECLINED, $result->getCode());
        $this->assertSame("Message detail error", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandInvalidExpiryResponse()
    {
        $result = $this->aResult('invalid_expiry');

        $this->assertSame(Payment_Process2::RESULT_DECLINED, $result->getCode());
        $this->assertSame("Message detail error", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandInvalidTransactionTypeResponse()
    {
        $result = $this->aResult('invalid_transaction');

        $this->assertSame(Payment_Process2::RESULT_DECLINED, $result->getCode());
        $this->assertSame("Transaction declined - transaction type not supported", $result->getMessage());
    }

    public function testShouldCorrectlyUnderstandInvalidBankDeclinedGoAwayResponse()
    {
        $result = $this->aResult('declined_go_away');

        $this->assertSame(Payment_Process2::RESULT_DECLINED, $result->getCode());
        $this->assertSame("Bank declined transaction - do not contact bank", $result->getMessage());
    }
}
