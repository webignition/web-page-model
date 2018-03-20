<?php

namespace webignition\Tests\WebResource\WebPage;

use QueryPath\Exception as QueryPathException;
use PHPUnit_Framework_TestCase;
use webignition\Tests\WebResource\WebPage\Factory\ResponseFactory;
use webignition\WebResource\WebPage\InvalidContentTypeException;
use webignition\WebResource\WebPage\Parser;
use webignition\WebResource\WebPage\WebPage;

class ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->parser = new Parser();
    }

    /**
     * @dataProvider getCharacterSetDataProvider
     *
     * @param WebPage $webPage
     * @param string $expectedCharacterSet
     *
     * @throws QueryPathException
     */
    public function testGetCharacterSet(WebPage $webPage, $expectedCharacterSet)
    {
        $this->parser->setWebPage($webPage);

        $this->assertEquals($expectedCharacterSet, $this->parser->getCharacterSet());
    }

    /**
     * @return array
     *
     * @throws InvalidContentTypeException
     */
    public function getCharacterSetDataProvider()
    {
        return [
            'empty response' => [
                'webPage' => new WebPage(ResponseFactory::create('')),
                'expectedCharacterSet' => '',
            ],
            'empty document' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture('empty-document.html')),
                'expectedCharacterSet' => '',
            ],
            'meta http-equiv="Content-Type" (valid)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-valid-http-equiv-content-type.html'
                )),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta http-equiv="content-type" (valid)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-valid-http-equiv-content-type-lowercase.html'
                )),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta name="Content-Type" (valid value, malformed)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-malformed-http-equiv-content-type.html'
                )),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta charset="foo" (invalid value, well-formed)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-invalid-meta-charset.html'
                )),
                'expectedCharacterSet' => 'foo',
            ],
            'meta name="Content-Type" (unparseable value, malformed)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-unparseable-http-equiv-content-type.html'
                )),
                'expectedCharacterSet' => null,
            ],
        ];
    }
}
