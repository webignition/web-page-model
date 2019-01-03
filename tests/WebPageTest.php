<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\Tests\WebResource\WebPage;

use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\TestingTools\FixtureLoader;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResourceProperties;

class WebPageTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateWithContentWithInvalidContentType()
    {
        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "image/png"');

        new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_CONTENT_TYPE => new InternetMediaType('image', 'png'),
        ]));
    }

    public function testCreateWithResponseWithInvalidContentType()
    {
        $response = \Mockery::mock(ResponseInterface::class);
        $response
            ->shouldReceive('getHeaderLine')
            ->with(WebPage::HEADER_CONTENT_TYPE)
            ->andReturn('image/jpg');

        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "image/jpg"');

        new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_RESPONSE => $response,
        ]));
    }

    public function testSetContentTypeInvalidContentType()
    {
        $webPage = new WebPage(WebResourceProperties::create([]));

        $this->assertEquals('text/html', (string)$webPage->getContentType());

        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "application/octetstream"');

        $webPage->setContentType(new InternetMediaType('application', 'octetstream'));
    }

    public function testSetResponseWithInvalidContentType()
    {
        $responseBody = \Mockery::mock(StreamInterface::class);
        $responseBody
            ->shouldReceive('__toString')
            ->andReturn('');

        $currentResponse = \Mockery::mock(ResponseInterface::class);
        $currentResponse
            ->shouldReceive('getHeaderLine')
            ->with(WebPage::HEADER_CONTENT_TYPE)
            ->andReturn('text/html');

        $currentResponse
            ->shouldReceive('getBody')
            ->andReturn($responseBody);

        $newResponse = \Mockery::mock(ResponseInterface::class);
        $newResponse
            ->shouldReceive('getHeaderLine')
            ->with(WebPage::HEADER_CONTENT_TYPE)
            ->andReturn('image/jpg');

        $webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_RESPONSE => $currentResponse,
        ]));

        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "image/jpg"');

        $webPage->setResponse($newResponse);
    }

    /**
     * @dataProvider getCharacterSetForWebPageCreatedFromContentDataProvider
     */
    public function testGetCharacterSetForWebPageCreatedFromContent(string $content, ?string $expectedCharacterSet)
    {
        $webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_CONTENT => $content,
        ]));

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
     */
    public function testGetCharacterSetForWebPageCreatedFromResponse(
        ResponseInterface $response,
        ?string $expectedCharacterSet
    ) {
        $webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_RESPONSE => $response,
        ]));

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
        $webPage = new WebPage(WebResourceProperties::create([]));

        $this->assertInstanceOf(WebPageInspector::class, $webPage->getInspector());
    }

    /**
     * @dataProvider getBaseUrlDataProvider
     */
    public function testGetBaseUrl(WebPage $webPage, ?string $expectedBaseUrl)
    {
        $this->assertSame($expectedBaseUrl, $webPage->getBaseUrl());
    }

    public function getBaseUrlDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'empty content' => [
                'webPage' => WebPage::createFromContent(''),
                'expectedBaseUrl' => null,
            ],
            'response without base element' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse('text/html', '')
                ),
                'expectedBaseUrl' => 'http://example.com/',
            ],
            'response without empty element' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse('text/html', FixtureLoader::load('empty-base-element.html'))
                ),
                'expectedBaseUrl' => 'http://example.com/',
            ],
            'response with empty element' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse('text/html', FixtureLoader::load('base-element.html'))
                ),
                'expectedBaseUrl' => 'http://base.example.com/foobar/',
            ],
            'response with root-relative element' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse('text/html', FixtureLoader::load('root-relative-base-element.html'))
                ),
                'expectedBaseUrl' => 'http://example.com/',
            ],
            'response with relative element' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse('text/html', FixtureLoader::load('relative-base-element.html'))
                ),
                'expectedBaseUrl' => 'http://example.com/foo',
            ],
        ];
    }

    /**
     * @dataProvider isEncodingValidDataProvider
     */
    public function testIsEncodingValid(WebPage $webPage, bool $expectedEncodingIsValid)
    {
        $this->assertEquals($expectedEncodingIsValid, $webPage->isEncodingValid());
    }

    public function isEncodingValidDataProvider(): array
    {
        return [
            'all-ascii empty document has valid encoding (without response)' => [
                'webPage' => WebPage::createFromContent(
                    FixtureLoader::load('empty-document.html')
                ),
                'expectedEncodingIsValid' => true,
            ],
            'all-ascii empty document has valid encoding (with response)' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse('text/html', FixtureLoader::load('empty-document.html'))
                ),
                'expectedEncodingIsValid' => true,
            ],
            'big5-encoded document has valid encoding (without response)' => [
                'webPage' => WebPage::createFromContent(
                    FixtureLoader::load('document-with-big5-charset.html')
                ),
                'expectedEncodingIsValid' => true,
            ],
            'big5-encoded document has valid encoding (with response)' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse('text/html', FixtureLoader::load('document-with-big5-charset.html'))
                ),
                'expectedEncodingIsValid' => true,
            ],
            'gb2312-encoded document has valid encoding' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse(
                        'text/html',
                        FixtureLoader::load('document-with-script-elements-charset=gb2312.html')
                    )
                ),
                'expectedEncodingIsValid' => true,
            ],
            'greek kosme is valid utf-8' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse(
                        'text/html; charset=utf-8',
                        $this->createFoo("κόσμε")
                    )
                ),
                'expectedEncodingIsValid' => true,
            ],
            'invalidly-encoded windows-1251 document is not valid windows-1251' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse(
                        'text/html; charset=utf-8',
                        FixtureLoader::load('document-with-invalid-windows-1251-encoding.html')
                    )
                ),
                'expectedEncodingIsValid' => false,
            ],
            'hi∑ is not valid utf-8' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse(
                        'text/html; charset=utf-8',
                        $this->createFoo("hi∑")
                    )
                ),
                'expectedEncodingIsValid' => true,
            ],
            'hi∑ is not valid windows-1252' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse(
                        'text/html; charset=windows-1252',
                        $this->createFoo("hi∑")
                    )
                ),
                'expectedEncodingIsValid' => false,
            ],
        ];
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

    private function createUri(string $uriString)
    {
        $uri = \Mockery::mock(UriInterface::class);
        $uri
            ->shouldReceive('__toString')
            ->andReturn($uriString);

        return $uri;
    }

    private function createFoo(string $fragment)
    {
        return sprintf(
            '<!doctype html><html lang="en"><head><title>%s</title></head></html>',
            $fragment
        );
    }
}
