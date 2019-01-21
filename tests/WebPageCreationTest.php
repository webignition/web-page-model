<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\Tests\WebResource\WebPage;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResourceProperties;
use webignition\WebResourceInterfaces\WebResourceInterface;

class WebPageCreationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebPage|WebResourceInterface
     */
    private $webPage;

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertInstanceOf(WebPage::class, $this->webPage);
    }

    public function testCreateWithNoArgs()
    {
        $this->webPage = new WebPage();

        $this->assertNull($this->webPage->getUri());
        $this->assertEquals(null, $this->webPage->getContent());
        $this->assertEquals('text/html', (string)$this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());
    }

    /**
     * @dataProvider createFromContentDataProvider
     */
    public function testCreateFromContent(
        string $content,
        ?InternetMediaTypeInterface $contentType,
        string $expectedContentType
    ) {
        $this->webPage = WebPage::createFromContent($content, $contentType);

        $this->assertEquals($content, $this->webPage->getContent());
        $this->assertEquals($expectedContentType, (string) $this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());
    }

    public function createFromContentDataProvider(): array
    {
        return [
            'no content type' => [
                'content' => '',
                'contentType' => null,
                'expectedContentType' => 'text/html',
            ],
            'text/html content type' => [
                'content' => '',
                'contentType' => ContentTypeFactory::createFromString('text/html'),
                'expectedContentType' => 'text/html',
            ],
            'text/html; charset=utf-8 content type' => [
                'content' => '',
                'contentType' => ContentTypeFactory::createFromString('text/html; charset=utf-8'),
                'expectedContentType' => 'text/html; charset=utf-8',
            ],
            'text/html; charset=big5 content type' => [
                'content' => '',
                'contentType' => ContentTypeFactory::createFromString('text/html; charset=big5'),
                'expectedContentType' => 'text/html; charset=big5',
            ],
        ];
    }

    /**
     * @dataProvider createWithResponseDataProvider
     */
    public function testCreateFromResponse(string $responseContentTypeHeader, string $expectedContentTypeString)
    {
        $content = 'web page content';
        $uri = new Uri();
        $response = new Response(200, ['content-type' => $responseContentTypeHeader], $content);

        $this->webPage = WebPage::createFromResponse($uri, $response);

        $this->assertEquals($uri, $this->webPage->getUri());
        $this->assertEquals($content, $this->webPage->getContent());
        $this->assertEquals($expectedContentTypeString, (string)$this->webPage->getContentType());
        $this->assertEquals($response, $this->webPage->getResponse());
    }

    /**
     * @dataProvider createWithContentDataProvider
     */
    public function testCreateWithContent(?InternetMediaTypeInterface $contentType, string $expectedContentTypeString)
    {
        $uri = new Uri();
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
     */
    public function testCreateWithResponse(string $responseContentTypeHeader, string $expectedContentTypeString)
    {
        $content = 'web page content';
        $uri = new Uri();
        $response = new Response(200, ['content-type' => $responseContentTypeHeader], $content);

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
}
