<?php

namespace webignition\Tests\WebResource\WebPage;

use QueryPath\Exception as QueryPathException;
use GuzzleHttp\Message\ResponseInterface;
use Mockery\MockInterface;
use PHPUnit_Framework_TestCase;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\Exception as WebResourceException;

class WebPageTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     * @var WebPage
     */
    private $webPage;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->webPage = new WebPage();
    }

    /**
     * @dataProvider findDataProvider
     *
     * @param ResponseInterface $httpResponse
     * @param string $selector
     * @param mixed $eachFunction
     * @param array $expectedFoundValues
     *
     * @throws QueryPathException
     * @throws WebResourceException
     */
    public function testFind(ResponseInterface $httpResponse, $selector, $eachFunction, $expectedFoundValues)
    {
        $this->webPage->setHttpResponse($httpResponse);
        $foundValues = array();

        $this
            ->webPage
            ->find($selector)
            ->each(function ($index, \DOMElement $domElement) use (&$foundValues, $eachFunction) {
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
        return [
            'script src values' => [
                'httpResponse' => $this->createHttpResponse('text/html', 'document-with-script-elements.html'),
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
                'httpResponse' => $this->createHttpResponse('text/html', 'document-with-script-elements.html'),
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
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'document-with-script-elements-charset=gb2312.html'
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
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'document-with-big5-charset.html'
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
     * @param ResponseInterface $httpResponse
     * @param string $expectedCharacterSet
     *
     * @throws QueryPathException
     * @throws WebResourceException
     */
    public function testGetCharacterSet(ResponseInterface $httpResponse, $expectedCharacterSet)
    {
        $this->webPage->setHttpResponse($httpResponse);

        $this->assertEquals($expectedCharacterSet, $this->webPage->getCharacterSet());
    }

    /**
     * @return array
     */
    public function getCharacterSetDataProvider()
    {
        return [
            'get from http response when missing in document meta' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html; charset=utf-8',
                    'empty-document.html'
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'get from http response when invalid in document meta' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html; charset=utf-8',
                    'empty-document-with-invalid-meta-charset.html'
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'get from document meta when missing in http response' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document-with-valid-meta-charset.html'
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'get when missing in document and http response' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document.html'
                ),
                'expectedCharacterSet' => null,
            ],
        ];
    }

    /**
     * @throws QueryPathException
     */
    public function testGetDocumentCharacterSetIsNullByDefault()
    {
        $this->assertNull($this->webPage->getDocumentCharacterSet());
    }

    /**
     * @dataProvider getDocumentCharacterSetDataProvider
     *
     * @param ResponseInterface $httpResponse
     * @param string $expectedCharacterSet
     *
     * @throws QueryPathException
     * @throws WebResourceException
     */
    public function testGetDocumentCharacterSet(ResponseInterface $httpResponse, $expectedCharacterSet)
    {
        $this->webPage->setHttpResponse($httpResponse);

        $this->assertEquals($expectedCharacterSet, $this->webPage->getDocumentCharacterSet());
    }

    /**
     * @return array
     */
    public function getDocumentCharacterSetDataProvider()
    {
        return [
            'meta charset' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document-with-valid-meta-charset.html'
                ),
                'expectedCharset' => 'utf-8',
            ],
            'meta http-equiv content type charset' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document-with-valid-http-equiv-content-type.html'
                ),
                'expectedCharset' => 'utf-8',
            ],
            'meta http-equiv content type charset lowercase' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document-with-valid-http-equiv-content-type-lowercase.html'
                ),
                'expectedCharset' => 'utf-8',
            ],
            'meta http-equiv content type charset malformed' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document-with-malformed-http-equiv-content-type.html'
                ),
                'expectedCharset' => 'utf-8',
            ],
        ];
    }

    /**
     * @dataProvider getDocumentCharacterSetDefinitionIsMalformedDataProvider
     *
     * @param ResponseInterface $httpResponse
     * @param bool $expectedIsMalformed
     *
     * @throws WebResourceException
     */
    public function testGetDocumentCharacterSetDefinitionIsMalformed(
        ResponseInterface $httpResponse,
        $expectedIsMalformed
    ) {
        $this->webPage->setHttpResponse($httpResponse);

        $this->assertEquals($expectedIsMalformed, $this->webPage->getDocumentCharacterSetDefinitionIsMalformed());
    }

    /**
     * @return array
     */
    public function getDocumentCharacterSetDefinitionIsMalformedDataProvider()
    {
        return [
            'meta charset' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document-with-valid-meta-charset.html'
                ),
                'expectedIsMalformed' => false,
            ],
            'meta http-equiv content type charset' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document-with-valid-http-equiv-content-type.html'
                ),
                'expectedIsMalformed' => false,
            ],
            'meta http-equiv content type charset lowercase' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document-with-valid-http-equiv-content-type-lowercase.html'
                ),
                'expectedIsMalformed' => false,
            ],
            'meta http-equiv content type charset malformed' => [
                'httpResponse' => $this->createHttpResponse(
                    'text/html',
                    'empty-document-with-malformed-http-equiv-content-type.html'
                ),
                'expectedIsMalformed' => true,
            ],
        ];
    }

    /**
     * @dataProvider getHttpResponseCharacterSetDataProvider
     *
     * @param ResponseInterface $httpResponse
     * @param $expectedHttpResponseCharacterSet
     *
     * @throws WebResourceException
     */
    public function testGetHttpResponseCharacterSet(ResponseInterface $httpResponse, $expectedHttpResponseCharacterSet)
    {
        $this->webPage->setHttpResponse($httpResponse);
        $this->assertEquals($expectedHttpResponseCharacterSet, $this->webPage->getHttpResponseCharacterSet());
    }

    /**
     * @return array
     */
    public function getHttpResponseCharacterSetDataProvider()
    {
        return [
            'none' => [
                'httpResponse' => $this->createHttpResponse('text/html'),
                'expectedHttpResponseCharacterSet' => null,
            ],
            'is present' => [
                'httpResponse' => $this->createHttpResponse('text/html; charset=utf-8'),
                'expectedHttpResponseCharacterSet' => 'utf-8',
            ],
        ];
    }

    private function loadFixture($fixtureName)
    {
        return file_get_contents(__DIR__ . '/Fixtures/' . $fixtureName);
    }

    /**
     * @param $contentTypeHeader
     * @param $bodyFixture
     * @return MockInterface|ResponseInterface
     */
    private function createHttpResponse($contentTypeHeader, $bodyFixture = null)
    {
        $httpResponse = \Mockery::mock(ResponseInterface::class);
        $httpResponse
            ->shouldReceive('getHeader')
            ->with('content-type')
            ->andReturn($contentTypeHeader);

        if (!empty($bodyFixture)) {
            $httpResponse
                ->shouldReceive('getBody')
                ->andReturn($this->loadFixture($bodyFixture));
        }

        return $httpResponse;
    }
}
