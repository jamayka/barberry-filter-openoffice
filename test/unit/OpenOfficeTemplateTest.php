<?php
namespace Barberry\Filter;
use Barberry;
use Barberry\Test;

include_once dirname(dirname(__DIR__)) . '/externals/Tbs/tbs_class.php';

class OpenOfficeTemplateTest extends \PHPUnit_Framework_TestCase {

    public function testImplementsParserInterface() {
        $this->assertInstanceOf('Barberry\\Filter\\FilterInterface', self::p());
    }

    public function testSurvivesEmptyTemplate() {
        self::p()->filter(new \Barberry\PostedFile\Collection(), array());
    }

    public function testLoadsBlockVariablesIntoTinyButStrongParser() {
        $tbs = $this->getMockBuilder('clsTinyButStrong')->disableOriginalConstructor()->getMock();
        $tbs->expects($this->once())->method('MergeBlock')->with(
            'arrayKey',
            array('anyarray')
        );

        self::p($tbs)->filter(
            new \Barberry\PostedFile\Collection(array('file' => new \Barberry\PostedFile(Test\Data::ottTemplate()))),
            array('arrayKey' => array('anyarray'))
        );
    }

    public function testLoadsFieldVariablesIntoTinyButStrongParser() {
        $tbs = $this->getMockBuilder('clsTinyButStrong')->disableOriginalConstructor()->getMock();
        $tbs->expects($this->once())->method('MergeField')->with(
            'fieldKey', 'fieldValue'
        );

        self::p($tbs)->filter(
            new \Barberry\PostedFile\Collection(array('file' => new \Barberry\PostedFile(Test\Data::ottTemplate()))),
            array('fieldKey' => 'fieldValue')
        );
    }

    public function testLoadsFilesIntoTinyButStrongParser() {
        $tbs = $this->getMockBuilder('clsTinyButStrong')->disableOriginalConstructor()->getMock();
        $tbs->expects($this->once())
            ->method('MergeField')
            ->with(
                'image',
                $this->logicalAnd($this->stringStartsWith('/tmp/ooparser_'), $this->stringEndsWith('.gif'))
            );

        $p = self::p($tbs);
        $p->filter(
            new \Barberry\PostedFile\Collection(
                array(
                    'file' => new \Barberry\PostedFile(Test\Data::ottTemplate()),
                    'image' => new \Barberry\PostedFile(Test\Data::gif1x1(), 'test.gif')
                )
            ),
            array()
        );
    }

    public function testDoesNotChangeFilesIfUnsupportedContentTypeIsPassed() {
        $files = new \Barberry\PostedFile\Collection(
            array('image' => new \Barberry\PostedFile(Test\Data::gif1x1(), 'test.gif'))
        );

        self::p()->filter($files, array());

        $this->assertEquals(new \Barberry\PostedFile(Test\Data::gif1x1(), 'test.gif'), $files['image']);
    }

//--------------------------------------------------------------------------------------------------

    private static function p($tbs = null, $tempPath = null) {
        return new OpenOfficeTemplate(
            $tempPath ?: '',
            $tbs ?: Test\Stub::create('clsTinyButStrong')
        );
    }
}
