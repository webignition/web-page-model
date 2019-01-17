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
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\TestingTools\FixtureLoader;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResourceProperties;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;

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

    public function testCreateFromContentInvalidContentType()
    {
        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionCode(InvalidContentTypeException::CODE);
        $this->expectExceptionMessage('Invalid content type "image/png"');

        WebPage::createFromContent('', new InternetMediaType('image', 'png'));
    }

    public function testSetContentTypeInvalidContentType()
    {
        $webPage = new WebPage(WebResourceProperties::create([]));

        $this->assertEquals('text/html', (string)$webPage->getContentType());

        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "application/octetstream"');

        $webPage->setContentType(new InternetMediaType('application', 'octetstream'));
    }

    /**
     * @dataProvider setContentTypeDataProvider
     */
    public function testSetContentTypeFoo(InternetMediaType $contentType, ?string $expectedCharacterSet)
    {
        $initialContentType = $this->createContentType('text/html');

        $webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_CONTENT => '',
            WebResourceProperties::ARG_CONTENT_TYPE => $initialContentType,
        ]));

        $this->assertEquals('text/html', (string) $webPage->getContentType());
        $this->assertNull($webPage->getCharacterSet());

        /* @var WebPage $webPage */
        $webPage = $webPage->setContentType($contentType);

        $this->assertSame($contentType, $webPage->getContentType());
        $this->assertEquals($expectedCharacterSet, $webPage->getCharacterSet());
    }

    public function setContentTypeDataProvider(): array
    {
        return [
            'no character set' => [
                'contentType' => $this->createContentType('text/html'),
                'expectedCharacterSet' => null,
            ],
            'utf-8 character set' => [
                'contentType' => $this->createContentType('text/html; charset=utf-8'),
                'expectedCharacterSet' => 'utf-8',
            ],
            'big5 character set' => [
                'contentType' => $this->createContentType('text/html; charset=big5'),
                'expectedCharacterSet' => 'big5',
            ],
        ];
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
     * @dataProvider getCharacterSetForWebPageCreatedFromContentHasNoCharacterSetDataProvider
     */
    public function testGetCharacterSetForWebPageCreatedFromContentHasNoCharacterSet(
        string $content,
        ?InternetMediaTypeInterface $contentType = null
    ) {
        $webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_CONTENT => $content,
            WebResourceProperties::ARG_CONTENT_TYPE => $contentType
        ]));

        $this->assertNull($webPage->getCharacterSet());
    }

    public function getCharacterSetForWebPageCreatedFromContentHasNoCharacterSetDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'invalid web page content' => [
                'content' => 'foo',
            ],
            'missing in document meta' => [
                'content' => FixtureLoader::load('empty-document.html'),
            ],
            'invalid in document meta' => [
                'content' => FixtureLoader::load('empty-document-with-invalid-meta-charset.html'),
            ],
            'missing in document meta, missing in content type' => [
                'content' => FixtureLoader::load('empty-document.html'),
            ],
            'invalid in document meta, missing in content type' => [
                'content' => FixtureLoader::load('empty-document-with-invalid-meta-charset.html'),
            ],
            'unparseable content type' => [
                'content' => FixtureLoader::load('empty-document-with-unparseable-content-type.html'),
            ],
        ];
    }

    /**
     * @dataProvider getCharacterSetForWebPageCreatedFromContentHasCharacterSetDataProvider
     */
    public function testGetCharacterSetForWebPageCreatedFromContentHasCharacterSet(
        string $content,
        string $expectedCharacterSet,
        ?InternetMediaTypeInterface $contentType = null
    ) {
        $webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_CONTENT => $content,
            WebResourceProperties::ARG_CONTENT_TYPE => $contentType,
        ]));

        $this->assertSame($expectedCharacterSet, $webPage->getCharacterSet());
    }

    public function getCharacterSetForWebPageCreatedFromContentHasCharacterSetDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'present in document meta, no content type' => [
                'content' => FixtureLoader::load('empty-document-with-valid-meta-charset.html'),
                'expectedCharacterSet' => 'utf-8',
                'contentType' => null,
            ],
            'missing in document meta, present in content type' => [
                'content' => FixtureLoader::load('empty-document.html'),
                'expectedCharacterSet' => 'utf-8',
                'contentType' => $this->createContentType('text/html; charset=utf-8'),
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
            'unparseable content type in content, no character set in response' => array(
                'response' => $this->createResponse(
                    'text/html',
                    FixtureLoader::load('empty-document-with-unparseable-content-type.html')
                ),
                'expectedCharacterSet' => null,
            ),
            'unparseable content type in content, has character set in response' => array(
                'response' => $this->createResponse(
                    'text/html; charset=utf-8',
                    FixtureLoader::load('empty-document-with-unparseable-content-type.html')
                ),
                'expectedCharacterSet' => 'utf-8',
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
                        $this->createMarkupContainingFragment("κόσμε")
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
                        $this->createMarkupContainingFragment("hi∑")
                    )
                ),
                'expectedEncodingIsValid' => true,
            ],
            'hi∑ is not valid windows-1252' => [
                'webPage' => WebPage::createFromResponse(
                    $this->createUri('http://example.com/'),
                    $this->createResponse(
                        'text/html; charset=windows-1252',
                        $this->createMarkupContainingFragment("hi∑")
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

    private function createMarkupContainingFragment(string $fragment)
    {
        return sprintf(
            '<!doctype html><html lang="en"><head><title>%s</title></head></html>',
            $fragment
        );
    }

    private function createContentType(string $contentTypeString): InternetMediaTypeInterface
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return (new ContentTypeParser())->parse($contentTypeString);
    }
}
