<?php
namespace Barberry\Filter;
use Barberry\Test;

class OpenOfficeTemplateIntegrationTest extends \PHPUnit_Framework_TestCase {

    const PARSED_DOCUMENT_SIZE = 8369;

    public function testFiltersSpreadSheet() {
        $files = new \Barberry\PostedFile\Collection(
            array('file' => new \Barberry\PostedFile(Test\Data::otsTemplate(), 'test.ots'))
        );

        self::p()->filter(
            $files,
            array(
                'd' => array(
                    array('id' => 1),
                    array('id' => 2),
                ),
                'message' => 'Maxim was here'
            )
        );

        $this->assertEquals(self::PARSED_DOCUMENT_SIZE, strlen($files['file']->bin));
    }

//--------------------------------------------------------------------------------------------------

    private static function p() {
        return new OpenOfficeTemplate('/tmp/');
    }
}
