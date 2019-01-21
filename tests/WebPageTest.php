<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\Tests\WebResource\WebPage;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
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
        $response = new Response(200, ['content-type' => 'image/jpg']);

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
    public function testSetContentType(InternetMediaType $contentType, ?string $expectedCharacterSet)
    {
        $initialContentType = ContentTypeFactory::createFromString('text/html');

        $webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_CONTENT => '',
            WebResourceProperties::ARG_CONTENT_TYPE => $initialContentType,
        ]));

        $this->assertEquals('text/html', (string) $webPage->getContentType());
        $this->assertNull($webPage->getCharacterSet());

        $webPage = $webPage->setContentType($contentType);

        $this->assertSame($contentType, $webPage->getContentType());

        if ($webPage instanceof WebPage) {
            $this->assertEquals($expectedCharacterSet, $webPage->getCharacterSet());
        }
    }

    public function setContentTypeDataProvider(): array
    {
        return [
            'no character set' => [
                'contentType' => ContentTypeFactory::createFromString('text/html'),
                'expectedCharacterSet' => null,
            ],
            'utf-8 character set' => [
                'contentType' => ContentTypeFactory::createFromString('text/html; charset=utf-8'),
                'expectedCharacterSet' => 'utf-8',
            ],
            'big5 character set' => [
                'contentType' => ContentTypeFactory::createFromString('text/html; charset=big5'),
                'expectedCharacterSet' => 'big5',
            ],
        ];
    }

    public function testSetResponseWithInvalidContentType()
    {
        $currentResponse = new Response(200, ['content-type' => 'text/html']);
        $newResponse = new Response(200, ['content-type' => 'image/jpg']);

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
                'contentType' => ContentTypeFactory::createFromString('text/html; charset=utf-8'),
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
                'response' => new Response(
                    200,
                    ['content-type' => 'text/html'],
                    FixtureLoader::load('empty-document.html')
                ),
                'expectedCharacterSet' => null,
            ],
            'no character set in content, has character set in response' => [
                'response' => new Response(
                    200,
                    ['content-type' => 'text/html; charset=utf-8'],
                    FixtureLoader::load('empty-document.html')
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'invalid character set in content, has character set in response' => [
                'response' => new Response(
                    200,
                    ['content-type' => 'text/html; charset=utf-8'],
                    FixtureLoader::load('empty-document-with-invalid-meta-charset.html')
                ),
                'expectedCharacterSet' => 'utf-8',
            ],
            'has character set in content, no character set in response' => array(
                'response' => new Response(
                    200,
                    ['content-type' => 'text/html'],
                    FixtureLoader::load('empty-document-with-valid-meta-charset.html')
                ),
                'expectedCharacterSet' => 'utf-8',
            ),
            'character set in content overrides character set in response' => array(
                'response' => new Response(
                    200,
                    ['content-type' => 'text/html; charset=utf-8'],
                    FixtureLoader::load('document-with-big5-charset.html')
                ),
                'expectedCharacterSet' => 'big5',
            ),
            'unparseable content type in content, no character set in response' => array(
                'response' => new Response(
                    200,
                    ['content-type' => 'text/html'],
                    FixtureLoader::load('empty-document-with-unparseable-content-type.html')
                ),
                'expectedCharacterSet' => null,
            ),
            'unparseable content type in content, has character set in response' => array(
                'response' => new Response(
                    200,
                    ['content-type' => 'text/html; charset=utf-8'],
                    FixtureLoader::load('empty-document-with-unparseable-content-type.html')
                ),
                'expectedCharacterSet' => 'utf-8',
            ),
            'incorrectly-encoded document with gb2312 charset in page' => array(
                'response' => new Response(
                    200,
                    ['content-type' => 'text/html; charset=utf-8'],
                    FixtureLoader::load('document-with-script-elements-charset=gb2312.html')
                ),
                'expectedCharacterSet' => 'gb2312',
            ),
            'utf-8 document converted from gb2312 with gb2312 charset in page' => array(
                'response' => new Response(
                    200,
                    ['content-type' => 'text/html; charset=utf-8'],
                    mb_convert_encoding(
                        FixtureLoader::load('document-with-script-elements-charset=gb2312.html'),
                        'utf-8',
                        'gb2312'
                    )
                ),
                'expectedCharacterSet' => 'gb2312',
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
    public function testGetBaseUrl(string $content, Uri $uri, ?string $expectedBaseUrl)
    {
        /* @var WebPage $webPage */
        $webPage = WebPage::createFromResponse(
            $uri,
            new Response(200, ['content-type' => 'text/html'], $content)
        );

        if ($webPage instanceof  WebPage) {
            $this->assertSame($expectedBaseUrl, $webPage->getBaseUrl());
        }
    }

    public function getBaseUrlDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'empty content, empty uri' => [
                'content' => '',
                'uri' => new Uri(),
                'expectedBaseUrl' => null,
            ],
            'empty content, non-empty uri' => [
                'content' => '',
                'uri' => new Uri('http://example.com/'),
                'expectedBaseUrl' => 'http://example.com/',
            ],
            'empty base element' => [
                'content' => FixtureLoader::load('empty-base-element.html'),
                'uri' => new Uri('http://example.com/'),
                'expectedBaseUrl' => 'http://example.com/',
            ],
            'non-empty base element' => [
                'content' => FixtureLoader::load('base-element.html'),
                'uri' => new Uri('http://example.com/'),
                'expectedBaseUrl' => 'http://base.example.com/foobar/',
            ],
            'root-relative base element' => [
                'content' => FixtureLoader::load('root-relative-base-element.html'),
                'uri' => new Uri('http://example.com/'),
                'expectedBaseUrl' => 'http://example.com/',
            ],
            'relative base element' => [
                'content' => FixtureLoader::load('relative-base-element.html'),
                'uri' => new Uri('http://example.com/'),
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
                    new Uri('http://example.com/'),
                    new Response(200, ['content-type' => 'text/html'], FixtureLoader::load('empty-document.html'))
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
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        FixtureLoader::load('document-with-big5-charset.html')
                    )
                ),
                'expectedEncodingIsValid' => true,
            ],
            'gb2312-encoded document has valid encoding' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        FixtureLoader::load('document-with-script-elements-charset=gb2312.html')
                    )
                ),
                'expectedEncodingIsValid' => true,
            ],
            'greek kosme is valid utf-8' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html; charset=utf-8'],
                        $this->createMarkupContainingFragment("κόσμε")
                    )
                ),
                'expectedEncodingIsValid' => true,
            ],
            'invalidly-encoded windows-1251 document is not valid windows-1251' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html; charset=utf-8'],
                        FixtureLoader::load('document-with-invalid-windows-1251-encoding.html')
                    )
                ),
                'expectedEncodingIsValid' => false,
            ],
            'hi∑ is not valid utf-8' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html; charset=utf-8'],
                        $this->createMarkupContainingFragment("hi∑")
                    )
                ),
                'expectedEncodingIsValid' => true,
            ],
            'hi∑ is not valid windows-1252' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html; charset=windows-1252'],
                        $this->createMarkupContainingFragment("hi∑")
                    )
                ),
                'expectedEncodingIsValid' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCharacterEncodingDataProvider
     */
    public function testGetCharacterEncoding(
        WebPage $webPage,
        ?string $expectedCharacterEncoding,
        ?string $expectedCharacterSet
    ) {
        $this->assertEquals($expectedCharacterEncoding, $webPage->getCharacterEncoding());
        $this->assertEquals($expectedCharacterSet, $webPage->getCharacterSet());
    }

    public function getCharacterEncodingDataProvider(): array
    {
        return [
            'ascii' => [
                'webPage' => WebPage::createFromContent(
                    $this->createMarkupContainingFragment('')
                ),
                'expectedCharacterEncoding' => 'ascii',
                'expectedCharacterSet' => null,
            ],
            'utf-8' => [
                'webPage' => WebPage::createFromContent(
                    $this->createMarkupContainingFragment('hi∑')
                ),
                'expectedCharacterEncoding' => 'utf-8',
                'expectedCharacterSet' => null,
            ],
            'chinese "search", utf-8' => [
                'webPage' => WebPage::createFromContent(
                    $this->createMarkupContainingFragment('搜')
                ),
                'expectedCharacterEncoding' => 'utf-8',
                'expectedCharacterSet' => null,
            ],
            'chinese "search", big5, from content' => [
                'webPage' => WebPage::createFromContent(
                    FixtureLoader::load('document-with-big5-charset.html')
                ),
                'expectedCharacterEncoding' => null,
                'expectedCharacterSet' => 'big5',
            ],
            'chinese "search", big5, from re-encoded content' => [
                'webPage' => WebPage::createFromContent(
                    iconv('big5', 'utf-8', FixtureLoader::load('document-with-big5-charset.html'))
                ),
                'expectedCharacterEncoding' => 'utf-8',
                'expectedCharacterSet' => 'big5',
            ],
            'chinese "search", big5, mb_convert_encoding' => [
                'webPage' => WebPage::createFromContent(
                    mb_convert_encoding($this->createMarkupContainingFragment('搜'), 'big5', 'utf-8')
                ),
                'expectedCharacterEncoding' => null,
                'expectedCharacterSet' => null,
            ],
            'chinese "search", big5, iconv' => [
                'webPage' => WebPage::createFromContent(
                    iconv('utf-8', 'big5', $this->createMarkupContainingFragment('搜'))
                ),
                'expectedCharacterEncoding' => null,
                'expectedCharacterSet' => null,
            ],
            'chinese "search", gb2312, mb_convert_encoding' => [
                'webPage' => WebPage::createFromContent(
                    mb_convert_encoding($this->createMarkupContainingFragment('搜'), 'gb2312', 'utf-8')
                ),
                'expectedCharacterEncoding' => null,
                'expectedCharacterSet' => null,
            ],
            'chinese "search", gb2312, iconv' => [
                'webPage' => WebPage::createFromContent(
                    iconv('utf-8', 'gb2312', $this->createMarkupContainingFragment('搜'))
                ),
                'expectedCharacterEncoding' => null,
                'expectedCharacterSet' => null,
            ],
            'chinese "cross", big5, mb_convert_encoding' => [
                'webPage' => WebPage::createFromContent(
                    mb_convert_encoding($this->createMarkupContainingFragment('交'), 'big5', 'utf-8')
                ),
                'expectedCharacterEncoding' => null,
                'expectedCharacterSet' => null,
            ],
            'chinese "cross", big5, iconv' => [
                'webPage' => WebPage::createFromContent(
                    iconv('utf-8', 'big5', $this->createMarkupContainingFragment('交'))
                ),
                'expectedCharacterEncoding' => null,
                'expectedCharacterSet' => null,
            ],
            'chinese "cross", gb2312, mb_convert_encoding' => [
                'webPage' => WebPage::createFromContent(
                    mb_convert_encoding($this->createMarkupContainingFragment('交'), 'gb2312', 'utf-8')
                ),
                'expectedCharacterEncoding' => null,
                'expectedCharacterSet' => null,
            ],
            'chinese "cross", gb2312, iconv' => [
                'webPage' => WebPage::createFromContent(
                    iconv('utf-8', 'gb2312', $this->createMarkupContainingFragment('交'))
                ),
                'expectedCharacterEncoding' => null,
                'expectedCharacterSet' => null,
            ],
        ];
    }

    private function createMarkupContainingFragment(string $fragment)
    {
        return sprintf(
            '<!doctype html><html lang="en"><head><title>%s</title></head></html>',
            $fragment
        );
    }
}
