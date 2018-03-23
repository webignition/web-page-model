<?php

namespace webignition\Tests\WebResource\WebPage;

use QueryPath\Exception as QueryPathException;
use PHPUnit_Framework_TestCase;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\TestingTools\ContentTypes;
use webignition\WebResource\TestingTools\FixtureLoader;
use webignition\WebResource\TestingTools\ResponseFactory;
use webignition\WebResource\WebPage\Parser;
use webignition\WebResource\WebPage\UnparseableContentTypeException;
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
     * @dataProvider getIsContentTypeMalformedDataProvider
     *
     * @param WebPage $webPage
     * @param string $expectedContentTypeIsMalformed
     *
     * @throws QueryPathException
     * @throws UnparseableContentTypeException
     */
    public function testGetIsContentTypeMalformed(WebPage $webPage, $expectedContentTypeIsMalformed)
    {
        $this->parser->setWebPage($webPage);

        $this->assertEquals($expectedContentTypeIsMalformed, $this->parser->getIsContentTypeMalformed());
    }

    /**
     * @return array
     *
     * @throws InvalidContentTypeException
     * @throws InternetMediaTypeParseException
     */
    public function getIsContentTypeMalformedDataProvider()
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'empty response' => [
                'webPage' => new WebPage(ResponseFactory::create(ContentTypes::CONTENT_TYPE_HTML)),
                'expectedContentTypeIsMalformed' => false,
            ],
            'empty document' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedContentTypeIsMalformed' => false,
            ],
            'meta http-equiv="Content-Type" (valid)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-valid-http-equiv-content-type.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedContentTypeIsMalformed' => false,
            ],
            'meta http-equiv="Content-Type" (valid, empty)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-empty-http-equiv-content-type.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedContentTypeIsMalformed' => false,
            ],
            'meta http-equiv="content-type" (valid)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-valid-http-equiv-content-type-lowercase.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedContentTypeIsMalformed' => false,
            ],
            'meta name="Content-Type" (valid value, malformed)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-malformed-http-equiv-content-type.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedContentTypeIsMalformed' => true,
            ],
            'meta charset="foo" (invalid value, well-formed)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-invalid-meta-charset.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedContentTypeIsMalformed' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCharacterSetSuccessDataProvider
     *
     * @param WebPage $webPage
     * @param string $expectedCharacterSet
     *
     * @throws QueryPathException
     * @throws UnparseableContentTypeException
     */
    public function testGetCharacterSetSuccess(WebPage $webPage, $expectedCharacterSet)
    {
        $this->parser->setWebPage($webPage);

        $this->assertEquals($expectedCharacterSet, $this->parser->getCharacterSet());
    }

    /**
     * @return array
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     */
    public function getCharacterSetSuccessDataProvider()
    {
        return [
            'empty response' => [
                'webPage' => new WebPage(ResponseFactory::create(ContentTypes::CONTENT_TYPE_HTML)),
                'expectedCharacterSet' => ''
            ],
            'empty document' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedCharacterSet' => '',
            ],
            'meta http-equiv="Content-Type" (valid)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-valid-http-equiv-content-type.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta http-equiv="Content-Type" (valid, empty)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-empty-http-equiv-content-type.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedCharacterSet' => null,
            ],
            'meta http-equiv="content-type" (valid)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-valid-http-equiv-content-type-lowercase.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta name="Content-Type" (valid value, malformed)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-malformed-http-equiv-content-type.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta charset="foo" (invalid value, well-formed)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-invalid-meta-charset.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedCharacterSet' => 'foo',
            ],
        ];
    }

    /**
     * @dataProvider getCharacterSetUnparseableContentTypeDataProvider
     *
     * @param WebPage $webPage
     * @param string $expectedExceptionMessage
     * @param string $expectedContentType
     *
     * @throws QueryPathException
     */
    public function testGetCharacterSetUnparseableContentType(
        WebPage $webPage,
        $expectedExceptionMessage,
        $expectedContentType
    ) {
        $this->parser->setWebPage($webPage);

        try {
            $this->parser->getCharacterSet();
            $this->fail(UnparseableContentTypeException::class . ' not thrown');
        } catch (UnparseableContentTypeException $unparseableContentTypeException) {
            $this->assertEquals(UnparseableContentTypeException::CODE, $unparseableContentTypeException->getCode());
            $this->assertEquals($expectedExceptionMessage, $unparseableContentTypeException->getMessage());
            $this->assertEquals($expectedContentType, $unparseableContentTypeException->getContentType());
        }
    }

    /**
     * @return array
     *
     * @throws InvalidContentTypeException
     * @throws InternetMediaTypeParseException
     */
    public function getCharacterSetUnparseableContentTypeDataProvider()
    {
        return [
            'meta name="Content-Type" (unparseable value, malformed)' => [
                'webPage' => new WebPage(ResponseFactory::createFromFixture(
                    'empty-document-with-unparseable-http-equiv-content-type.html',
                    ContentTypes::CONTENT_TYPE_HTML
                )),
                'expectedExceptionMessage' => 'Unparseable content type "f o o"',
                'expectedContentType' => 'f o o',
            ],
        ];
    }
}
