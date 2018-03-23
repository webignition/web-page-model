<?php

namespace webignition\Tests\WebResource\WebPage;

use Psr\Http\Message\ResponseInterface;
use QueryPath\Exception as QueryPathException;
use PHPUnit_Framework_TestCase;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\TestingTools\ContentTypes;
use webignition\WebResource\TestingTools\FixtureLoader;
use webignition\WebResource\TestingTools\ResponseFactory;
use webignition\WebResource\WebPage\WebPage;

class WebPageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createInvalidContentTypeDataProvider
     *
     * @param ResponseInterface $response
     * @param string $expectedExceptionMessage
     * @param string $expectedExceptionContentType
     *
     * @throws InternetMediaTypeParseException
     */
    public function testCreateInvalidContentType(
        ResponseInterface $response,
        $expectedExceptionMessage,
        $expectedExceptionContentType
    ) {
        try {
            new WebPage($response);
            $this->fail(InvalidContentTypeException::class . ' not thrown');
        } catch (InvalidContentTypeException $invalidContentTypeException) {
            $this->assertEquals(InvalidContentTypeException::CODE, $invalidContentTypeException->getCode());
            $this->assertEquals($expectedExceptionMessage, $invalidContentTypeException->getMessage());
            $this->assertEquals($expectedExceptionContentType, (string)$invalidContentTypeException->getContentType());
        }
    }

    /**
     * @return array
     */
    public function createInvalidContentTypeDataProvider()
    {
        return [
            'text/plain' => [
                'response' => ResponseFactory::create('text/plain'),
                'expectedExceptionMessage' => 'Invalid content type "text/plain"',
                'expectedExceptionContentType' => 'text/plain',
            ],
            'application/json' => [
                'response' => ResponseFactory::create('application/json'),
                'expectedExceptionMessage' => 'Invalid content type "application/json"',
                'expectedExceptionContentType' => 'application/json',
            ],
        ];
    }

    /**
     * @dataProvider createValidContentTypeDataProvider
     *
     * @param ResponseInterface $response
     * @param string $expectedContentTypeString
     *
     * @throws InvalidContentTypeException
     * @throws InternetMediaTypeParseException
     */
    public function testCreateValidContentType(ResponseInterface $response, $expectedContentTypeString)
    {
        $webPage = new WebPage($response);

        $this->assertEquals($expectedContentTypeString, (string)$webPage->getContentType());
    }

    /**
     * @return array
     */
    public function createValidContentTypeDataProvider()
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'text/html' => [
                'response' => ResponseFactory::create('text/html'),
                'expectedContentTypeString' => 'text/html',
            ],
            'text/xml' => [
                'response' => ResponseFactory::create('text/xml'),
                'expectedContentTypeString' => 'text/xml',
            ],
            'application/xml' => [
                'response' => ResponseFactory::create('application/xml'),
                'expectedContentTypeString' => 'application/xml',
            ],
            'application/xhtml+xml' => [
                'response' => ResponseFactory::create('application/xhtml+xml'),
                'expectedContentTypeString' => 'application/xhtml+xml',
            ],
        ];
    }

    /**
     * @dataProvider findDataProvider
     *
     * @param ResponseInterface $response
     * @param string $selector
     * @param mixed $eachFunction
     * @param array $expectedFoundValues
     *
     * @throws QueryPathException
     * @throws InvalidContentTypeException
     * @throws InternetMediaTypeParseException
     */
    public function testFind(ResponseInterface $response, $selector, $eachFunction, $expectedFoundValues)
    {
        $webPage = new WebPage($response);

        $foundValues = [];

        $webPage
            ->find($selector)
            ->each(function ($index, \DOMElement $domElement) use (&$foundValues, $eachFunction) {
                unset($index);
                $foundValues[] = call_user_func($eachFunction, $domElement);
            });

        $cleanedFoundValues = [];

        foreach ($foundValues as $foundValue) {
            if (!empty($foundValue)) {
                $cleanedFoundValues[] = $foundValue;
            }
        }

        $this->assertEquals($expectedFoundValues, $cleanedFoundValues);
    }

    /**
     * @return array
     */
    public function findDataProvider()
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'script src values' => [
                'response' => ResponseFactory::createFromFixture(
                    'document-with-script-elements.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'selector' => 'script',
                'eachFunction' => function (\DOMElement $domElement) {
                    return trim($domElement->getAttribute('src'));
                },
                'expectedFoundValues' => [
                    '//example.com/foo.js',
                    '/vendor/example/bar.js',

                ],
            ],
            'script values' => [
                'response' => ResponseFactory::createFromFixture(
                    'document-with-script-elements.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'selector' => 'script',
                'eachFunction' => function (\DOMElement $domElement) {
                    return trim($domElement->nodeValue);
                },
                'expectedFoundValues' => [
                    'var firstFromHead = true;',
                    'var secondFromHead = true;',
                    'var firstFromBody = true;',
                ],
            ],
            'script values from charset=gb2313 content' => [
                'response' => ResponseFactory::createFromFixture(
                    'document-with-script-elements-charset=gb2312.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'selector' => 'script',
                'eachFunction' => function (\DOMElement $domElement) {
                    return trim($domElement->nodeValue);
                },
                'expectedFoundValues' => [
                    'var firstFromHead = true;',
                    'var secondFromHead = true;',
                    'var firstFromBody = true;',
                ],
            ],
            'script values from charset=big5 content' => [
                'response' => ResponseFactory::createFromFixture(
                    'document-with-big5-charset.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'selector' => 'script',
                'eachFunction' => function (\DOMElement $domElement) {
                    return trim($domElement->nodeValue);
                },
                'expectedFoundValues' => [],
            ],
        ];
    }

    /**
     * @dataProvider getCharacterSetDataProvider
     *
     * @param ResponseInterface $response
     * @param string $expectedCharacterSet
     *
     * @throws InvalidContentTypeException
     * @throws QueryPathException
     * @throws InternetMediaTypeParseException
     */
    public function testGetCharacterSet(ResponseInterface $response, $expectedCharacterSet)
    {
        $webPage = new WebPage($response);

        $this->assertEquals($expectedCharacterSet, $webPage->getCharacterSet());
    }

    /**
     * @return array
     */
    public function getCharacterSetDataProvider()
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'get from http response when missing in document meta' => [
                'response' => ResponseFactory::createFromFixture(
                    'empty-document.html',
                    'text/html; charset=utf-8'
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'get from http response when invalid in document meta' => [
                'response' => ResponseFactory::createFromFixture(
                    'empty-document-with-invalid-meta-charset.html',
                    'text/html; charset=utf-8'
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'get from document meta when missing in http response' => [
                'response' => ResponseFactory::createFromFixture(
                    'empty-document-with-valid-meta-charset.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'get when missing in document and http response' => [
                'response' => ResponseFactory::createFromFixture(
                    'empty-document.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'expectedCharacterSet' => null,
            ],
        ];
    }

    /**
     * @dataProvider getDocumentCharacterSetDataProvider
     *
     * @param ResponseInterface $response
     * @param string $expectedCharacterSet
     *
     * @throws InvalidContentTypeException
     * @throws QueryPathException
     * @throws InternetMediaTypeParseException
     */
    public function testGetDocumentCharacterSet(ResponseInterface $response, $expectedCharacterSet)
    {
        $webPage = new WebPage($response);

        $this->assertEquals($expectedCharacterSet, $webPage->getDocumentCharacterSet());
    }

    /**
     * @return array
     */
    public function getDocumentCharacterSetDataProvider()
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'meta charset' => [
                'response' => ResponseFactory::createFromFixture(
                    'empty-document-with-valid-meta-charset.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'expectedCharset' => 'utf-8',
            ],
            'meta http-equiv content type charset' => [
                'response' => ResponseFactory::createFromFixture(
                    'empty-document-with-valid-http-equiv-content-type.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'expectedCharset' => 'utf-8',
            ],
            'meta http-equiv content type charset lowercase' => [
                'response' => ResponseFactory::createFromFixture(
                    'empty-document-with-valid-http-equiv-content-type-lowercase.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'expectedCharset' => 'utf-8',
            ],
            'meta http-equiv content type charset malformed' => [
                'response' => ResponseFactory::createFromFixture(
                    'empty-document-with-malformed-http-equiv-content-type.html',
                    ContentTypes::CONTENT_TYPE_HTML
                ),
                'expectedCharset' => 'utf-8',
            ],
        ];
    }

    /**
     * @dataProvider getHttpResponseCharacterSetDataProvider
     *
     * @param ResponseInterface $response
     * @param $expectedHttpResponseCharacterSet
     *
     * @throws InvalidContentTypeException
     * @throws InternetMediaTypeParseException
     */
    public function testGetHttpResponseCharacterSet(ResponseInterface $response, $expectedHttpResponseCharacterSet)
    {
        $webPage = new WebPage($response);

        $this->assertEquals($expectedHttpResponseCharacterSet, $webPage->getResponseCharacterSet());
    }

    /**
     * @return array
     */
    public function getHttpResponseCharacterSetDataProvider()
    {
        return [
            'none' => [
                'response' => ResponseFactory::create('text/html'),
                'expectedHttpResponseCharacterSet' => null,
            ],
            'is present' => [
                'response' => ResponseFactory::create('text/html; charset=utf-8'),
                'expectedHttpResponseCharacterSet' => 'utf-8',
            ],
        ];
    }

    public function testGetModelledContentTypeStrings()
    {
        $this->assertEquals(
            [
                WebPage::CONTENT_TYPE_TEXT_HTML,
                WebPage::CONTENT_TYPE_APPLICATION_XML,
                WebPage::CONTENT_TYPE_TEXT_XML,
                WebPage::CONTENT_TYPE_APPLICATION_XHTML_XML,
            ],
            WebPage::getModelledContentTypeStrings()
        );
    }
}
