<?php

namespace webignition\Tests\WebResource\WebPage;

use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResourceProperties;

class WebPageCreationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebPage
     */
    private $webPage;

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertInstanceOf(WebPage::class, $this->webPage);
    }

    /**
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function testCreateWithNoArgs()
    {
        $this->webPage = new WebPage();

        $this->assertNull($this->webPage->getUri());
        $this->assertEquals(null, $this->webPage->getContent());
        $this->assertEquals('text/html', (string)$this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());
    }

    /**
     * @throws InvalidContentTypeException
     */
    public function testCreateFromContent()
    {
        $content = '';

        $this->webPage = WebPage::createFromContent($content);

        $this->assertEquals($content, $this->webPage->getContent());
        $this->assertEquals('text/html', (string)$this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());
    }


    /**
     * @dataProvider createWithResponseDataProvider
     *
     * @param string $responseContentTypeHeader
     * @param string $expectedContentTypeString
     *
     * @throws InvalidContentTypeException
     */
    public function testCreateFromResponse(string $responseContentTypeHeader, string $expectedContentTypeString)
    {
        $content = 'web page content';
        $uri = \Mockery::mock(UriInterface::class);
        $response = $this->createResponse($content, $responseContentTypeHeader);

        $this->webPage = WebPage::createFromResponse($uri, $response);

        $this->assertEquals($uri, $this->webPage->getUri());
        $this->assertEquals($content, $this->webPage->getContent());
        $this->assertEquals($expectedContentTypeString, (string)$this->webPage->getContentType());
        $this->assertEquals($response, $this->webPage->getResponse());
    }

    /**
     * @dataProvider createWithContentDataProvider
     *
     * @param InternetMediaTypeInterface|null $contentType
     * @param string $expectedContentTypeString
     *
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function testCreateWithContent(?InternetMediaTypeInterface $contentType, string $expectedContentTypeString)
    {
        $uri = \Mockery::mock(UriInterface::class);

        $content = 'web page content';

        $this->webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_URI => $uri,
            WebResourceProperties::ARG_CONTENT_TYPE => $contentType,
            WebResourceProperties::ARG_CONTENT => $content,
        ]));

        $this->assertEquals($uri, $this->webPage->getUri());
        $this->assertEquals($content, $this->webPage->getContent());
        $this->assertEquals($expectedContentTypeString, (string)$this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());
    }

    public function createWithContentDataProvider(): array
    {
        return [
            'no content type' => [
                'contentType' => null,
                'expectedContentTypeString' => 'text/html',
            ],
            'text/html content type' => [
                'contentType' => new InternetMediaType('text', 'html'),
                'expectedContentTypeString' => 'text/html',
            ],
            'application/xml content type' => [
                'contentType' => new InternetMediaType('application', 'xml'),
                'expectedContentTypeString' => 'application/xml',
            ],
            'text/xml content type' => [
                'contentType' => new InternetMediaType('text', 'xml'),
                'expectedContentTypeString' => 'text/xml',
            ],
            'application/xhtml+xml content type' => [
                'contentType' => new InternetMediaType('application', 'xhtml+xml'),
                'expectedContentTypeString' => 'application/xhtml+xml',
            ],
        ];
    }

    /**
     * @dataProvider createWithResponseDataProvider
     *
     * @param string $responseContentTypeHeader
     * @param string $expectedContentTypeString
     *
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function testCreateWithResponse(string $responseContentTypeHeader, string $expectedContentTypeString)
    {
        $content = 'web page content';
        $uri = \Mockery::mock(UriInterface::class);
        $response = $this->createResponse($content, $responseContentTypeHeader);

        $this->webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_URI => $uri,
            WebResourceProperties::ARG_RESPONSE => $response,
        ]));

        $this->assertEquals($uri, $this->webPage->getUri());
        $this->assertEquals($content, $this->webPage->getContent());
        $this->assertEquals($expectedContentTypeString, (string)$this->webPage->getContentType());
        $this->assertEquals($response, $this->webPage->getResponse());
    }

    public function createWithResponseDataProvider(): array
    {
        return [
            'text/html content type' => [
                'responseContentTypeHeader' => 'text/html',
                'expectedContentTypeString' => 'text/html',
            ],
            'application/xml content type' => [
                'responseContentTypeHeader' => 'application/xml',
                'expectedContentTypeString' => 'application/xml',
            ],
            'text/xml content type' => [
                'responseContentTypeHeader' => 'text/xml',
                'expectedContentTypeString' => 'text/xml',
            ],
            'application/xhtml+xml content type' => [
                'responseContentTypeHeader' => 'application/xhtml+xml',
                'expectedContentTypeString' => 'application/xhtml+xml',
            ],
        ];
    }

    /**
     * @param string $content
     * @param string $responseContentTypeHeader
     *
     * @return MockInterface|ResponseInterface
     */
    private function createResponse(string $content, string $responseContentTypeHeader)
    {
        $responseBody = \Mockery::mock(StreamInterface::class);
        $responseBody
            ->shouldReceive('__toString')
            ->andReturn($content);

        $response = \Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('getHeaderLine')
            ->with(WebPage::HEADER_CONTENT_TYPE)
            ->andReturn($responseContentTypeHeader);

        $response
            ->shouldReceive('getBody')
            ->andReturn($responseBody);

        return $response;
    }
}
