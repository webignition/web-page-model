<?php

namespace webignition\Tests\WebResource\WebPage;

use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\TestingTools\FixtureLoader;
use webignition\WebResource\WebPage\WebPage;

class WebPageTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateFromContentWithInvalidContentType()
    {
        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        $contentType = new InternetMediaType();
        $contentType->setType('image');
        $contentType->setSubtype('png');

        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "image/png"');

        WebPage::createFromContent($uri, 'content', $contentType);
    }

    /**
     * @dataProvider createFromContentDataProvider
     *
     * @param InternetMediaTypeInterface|null $contentType
     * @param string $expectedContentTypeString
     */
    public function testCreateFromContent(?InternetMediaTypeInterface $contentType, string $expectedContentTypeString)
    {
        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        $content = 'web page content';

        $webPage = WebPage::createFromContent($uri, $content, $contentType);

        $this->assertInstanceOf(WebPage::class, $webPage);
        $this->assertEquals($uri, $webPage->getUri());
        $this->assertEquals($content, $webPage->getContent());
        $this->assertEquals($expectedContentTypeString, (string)$webPage->getContentType());
        $this->assertNull($webPage->getResponse());
    }

    public function createFromContentDataProvider(): array
    {
        return [
            'no content type' => [
                'contentType' => null,
                'expectedContentTypeString' => 'text/html',
            ],
            'text/html content type' => [
                'contentType' => $this->createContentType('text', 'html'),
                'expectedContentTypeString' => 'text/html',
            ],
            'application/xml content type' => [
                'contentType' => $this->createContentType('application', 'xml'),
                'expectedContentTypeString' => 'application/xml',
            ],
            'text/xml content type' => [
                'contentType' => $this->createContentType('text', 'xml'),
                'expectedContentTypeString' => 'text/xml',
            ],
            'application/xhtml+xml content type' => [
                'contentType' => $this->createContentType('application', 'xhtml+xml'),
                'expectedContentTypeString' => 'application/xhtml+xml',
            ],
        ];
    }

    public function testCreateFromResponseWithInvalidContentType()
    {
        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        /* @var ResponseInterface|MockInterface $response */
        $response = \Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('getHeaderLine')
            ->with(WebPage::HEADER_CONTENT_TYPE)
            ->andReturn('image/jpg');

        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "image/jpg"');

        WebPage::createFromResponse($uri, $response);
    }

    /**
     * @dataProvider createFromResponseDataProvider
     *
     * @param string $responseContentTypeHeader
     * @param string $expectedContentTypeString
     */
    public function testCreateFromResponse(string $responseContentTypeHeader, string $expectedContentTypeString)
    {
        $content = 'web page content';

        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        /* @var StreamInterface|MockInterface $responseBody */
        $responseBody = \Mockery::mock(StreamInterface::class);
        $responseBody
            ->shouldReceive('__toString')
            ->andReturn($content);

        /* @var ResponseInterface|MockInterface $response */
        $response = \Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('getHeaderLine')
            ->with(WebPage::HEADER_CONTENT_TYPE)
            ->andReturn($responseContentTypeHeader);

        $response
            ->shouldReceive('getBody')
            ->andReturn($responseBody);

        $webPage = WebPage::createFromResponse($uri, $response);

        $this->assertInstanceOf(WebPage::class, $webPage);
        $this->assertEquals($uri, $webPage->getUri());
        $this->assertEquals($content, $webPage->getContent());
        $this->assertEquals($expectedContentTypeString, (string)$webPage->getContentType());
        $this->assertEquals($response, $webPage->getResponse());
    }

    public function createFromResponseDataProvider(): array
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

    public function testSetUri()
    {
        /* @var UriInterface|MockInterface $currentUri */
        $currentUri = \Mockery::mock(UriInterface::class);

        /* @var UriInterface|MockInterface $newUri */
        $newUri = \Mockery::mock(UriInterface::class);

        $webPage = WebPage::createFromContent($currentUri, '');

        $this->assertEquals($currentUri, $webPage->getUri());

        $updatedWebPage = $webPage->setUri($newUri);

        $this->assertInstanceOf(WebPage::class, $updatedWebPage);
        $this->assertEquals($newUri, $updatedWebPage->getUri());
        $this->assertNotEquals(spl_object_hash($webPage), spl_object_hash($updatedWebPage));
    }

    public function testSetContentTypeValidContentType()
    {
        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        $webPage = WebPage::createFromContent($uri, 'web page content');

        $this->assertEquals('text/html', (string)$webPage->getContentType());

        $contentType = $this->createContentType('application', 'xhtml+xml');

        $updatedWebPage = $webPage->setContentType($contentType);

        $this->assertEquals('application/xhtml+xml', (string)$updatedWebPage->getContentType());
    }

    public function testSetContentTypeInvalidContentType()
    {
        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        $webPage = WebPage::createFromContent($uri, 'web page content');

        $this->assertEquals('text/html', (string)$webPage->getContentType());

        $contentType = $this->createContentType('application', 'octetstream');

        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "application/octetstream"');

        $webPage->setContentType($contentType);
    }

    public function testSetContent()
    {
        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        $currentContent = 'current content';
        $newContent = 'new content';

        $webPage = WebPage::createFromContent($uri, $currentContent);

        $this->assertEquals($currentContent, $webPage->getContent());

        $updatedWebPage = $webPage->setContent($newContent);

        $this->assertInstanceOf(WebPage::class, $updatedWebPage);
        $this->assertEquals($newContent, $updatedWebPage->getContent());
        $this->assertNotEquals(spl_object_hash($webPage), spl_object_hash($updatedWebPage));
    }

    public function testSetResponseWithInvalidContentType()
    {
        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        $responseBody = \Mockery::mock(StreamInterface::class);
        $responseBody
            ->shouldReceive('__toString')
            ->andReturn('');

        /* @var ResponseInterface|MockInterface $currentResponse */
        $currentResponse = \Mockery::mock(ResponseInterface::class);
        $currentResponse
            ->shouldReceive('getHeaderLine')
            ->with(WebPage::HEADER_CONTENT_TYPE)
            ->andReturn('text/html');

        $currentResponse
            ->shouldReceive('getBody')
            ->andReturn($responseBody);

        /* @var ResponseInterface|MockInterface $newResponse */
        $newResponse = \Mockery::mock(ResponseInterface::class);
        $newResponse
            ->shouldReceive('getHeaderLine')
            ->with(WebPage::HEADER_CONTENT_TYPE)
            ->andReturn('image/jpg');

        $webPage = WebPage::createFromResponse($uri, $currentResponse);

        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "image/jpg"');

        $webPage->setResponse($newResponse);
    }

    /**
     * @dataProvider getCharacterSetForWebPageCreatedFromContentDataProvider
     *
     * @param string $content
     * @param string|null $expectedCharacterSet
     */
    public function testGetCharacterSetForWebPageCreatedFromContent(string $content, ?string $expectedCharacterSet)
    {
        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        /* @var Webpage $webPage */
        $webPage = WebPage::createFromContent($uri, $content);

        $this->assertSame($expectedCharacterSet, $webPage->getCharacterSet());
    }

    public function getCharacterSetForWebPageCreatedFromContentDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'invalid web page content' => [
                'content' => 'foo',
                'expectedCharacterSet' => null,
            ],
            'missing in document meta' => [
                'content' => FixtureLoader::load('empty-document.html'),
                'expectedCharacterSet' => null,
            ],
            'invalid in document meta' => [
                'content' => FixtureLoader::load('empty-document-with-invalid-meta-charset.html'),
                'expectedCharacterSet' => null,
            ],
            'present in document meta' => [
                'content' => FixtureLoader::load('empty-document-with-valid-meta-charset.html'),
                'expectedCharacterSet' => 'utf-8',
            ],
        ];
    }

    /**
     * @dataProvider getCharacterSetForWebPageCreatedFromResponseDataProvider
     *
     * @param ResponseInterface $response
     * @param string|null $expectedCharacterSet
     */
    public function testGetCharacterSetForWebPageCreatedFromResponse(
        ResponseInterface $response,
        ?string $expectedCharacterSet
    ) {
        /* @var UriInterface|MockInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        /* @var WebPage $webPage */
        $webPage = WebPage::createFromResponse($uri, $response);

        $this->assertSame($expectedCharacterSet, $webPage->getCharacterSet());
    }

    public function getCharacterSetForWebPageCreatedFromResponseDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'no character set in content, no character set in response' => [
                'response' => $this->createResponse(
                    'text/html',
                    FixtureLoader::load('empty-document.html')
                ),
                'expectedCharacterSet' => null,
            ],
            'no character set in content, has character set in response' => [
                'response' => $this->createResponse(
                    'text/html; charset=utf-8',
                    FixtureLoader::load('empty-document.html')
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'invalid character set in content, has character set in response' => [
                'response' => $this->createResponse(
                    'text/html; charset=utf-8',
                    FixtureLoader::load('empty-document-with-invalid-meta-charset.html')
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'has character set in content, no character set in response' => array(
                'response' => $this->createResponse(
                    'text/html',
                    FixtureLoader::load('empty-document-with-valid-meta-charset.html')
                ),
                'expectedCharacterSet' => 'utf-8',
            ),
            'character set in content overrides character set in response' => array(
                'response' => $this->createResponse(
                    'text/html; charset=utf-8',
                    FixtureLoader::load('document-with-big5-charset.html')
                ),
                'expectedCharacterSet' => 'big5',
            ),
        ];
    }

    public function testGetInspector()
    {
        /* @var UriInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        $contentType = new InternetMediaType();
        $contentType->setType('text');
        $contentType->setSubtype('html');

        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($uri, '');

        $this->assertInstanceOf(WebPageInspector::class, $webPage->getInspector());
    }

    private function createResponse(string $contentTypeHeader, string $content): ResponseInterface
    {
        $responseBody = \Mockery::mock(StreamInterface::class);
        $responseBody
            ->shouldReceive('__toString')
            ->andReturn($content);

        /* @var ResponseInterface|MockInterface $response */
        $response = \Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('getHeaderLine')
            ->with(WebPage::HEADER_CONTENT_TYPE)
            ->andReturn($contentTypeHeader);

        $response
            ->shouldReceive('getBody')
            ->andReturn($responseBody);

        return $response;
    }

    private function createContentType(string $type, string $subtype): InternetMediaTypeInterface
    {
        $contentType = new InternetMediaType();
        $contentType->setType($type);
        $contentType->setSubtype($subtype);

        return $contentType;
    }
}
