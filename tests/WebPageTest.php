<?php

namespace webignition\Tests\WebResource\WebPage;

use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\TestingTools\FixtureLoader;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResourceProperties;

class WebPageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function testCreateWithContentWithInvalidContentType()
    {
        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "image/png"');

        new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_CONTENT_TYPE => new InternetMediaType('image', 'png'),
        ]));
    }

    /**
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
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

    /**
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function testSetContentTypeInvalidContentType()
    {
        $webPage = new WebPage(WebResourceProperties::create([]));

        $this->assertEquals('text/html', (string)$webPage->getContentType());

        $this->expectException(InvalidContentTypeException::class);
        $this->expectExceptionMessage('Invalid content type "application/octetstream"');

        $webPage->setContentType(new InternetMediaType('application', 'octetstream'));
    }

    /**
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
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
     *
     * @param string $content
     * @param string|null $expectedCharacterSet
     *
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
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
     *
     * @param ResponseInterface $response
     * @param string|null $expectedCharacterSet
     *
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
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

    /**
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function testGetInspector()
    {
        $webPage = new WebPage(WebResourceProperties::create([]));

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
}
