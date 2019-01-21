<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\Tests\WebResource\WebPage;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use webignition\WebResource\TestingTools\FixtureLoader;
use webignition\WebResource\WebPage\ContentEncodingValidator;
use webignition\WebResource\WebPage\WebPage;

class ContentEncodingValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContentEncodingValidator
     */
    private $contentEncodingValidator;

    protected function setUp()
    {
        parent::setUp();

        $this->contentEncodingValidator = new ContentEncodingValidator();
    }

    /**
     * @dataProvider isValidDataProvider
     *
     * @param WebPage $webPage
     */
    public function testIsValid(WebPage $webPage, bool $expectedIsValid)
    {
        $this->assertEquals($expectedIsValid, $this->contentEncodingValidator->isValid($webPage));
    }

    public function isValidDataProvider()
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'all-ascii empty document has valid encoding (from content)' => [
                'webPage' => WebPage::createFromContent(
                    FixtureLoader::load('empty-document.html')
                ),
                'expectedIsValid' => true,
            ],
            'all-ascii empty document has valid encoding (from response)' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(200, ['content-type' => 'text/html'], FixtureLoader::load('empty-document.html'))
                ),
                'expectedIsValid' => true,
            ],
            'big5 has invalid encoding (from content)' => [
                'webPage' => WebPage::createFromContent(
                    FixtureLoader::load('document-with-big5-charset.html')
                ),
                'expectedIsValid' => false,
            ],
            'big5 has invalid encoding (from content, with content type)' => [
                'webPage' => WebPage::createFromContent(
                    FixtureLoader::load('document-with-big5-charset.html'),
                    ContentTypeFactory::createFromString('text/html; charset=big5')
                ),
                'expectedIsValid' => false,
            ],
            'gb2312-encoded document has invalid encoding (from Response)' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        FixtureLoader::load('document-with-script-elements-charset=gb2312.html')
                    )
                ),
                'expectedIsValid' => false,
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
                'expectedIsValid' => true,
            ],
            'invalidly-encoded windows-1251 document is not valid windows-1251' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html charset=utf-8'],
                        FixtureLoader::load('document-with-invalid-windows-1251-encoding.html')
                    )
                ),
                'expectedIsValid' => false,
            ],
            'hi∑ is valid utf-8' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html; charset=utf-8'],
                        $this->createMarkupContainingFragment("hi∑")
                    )
                ),
                'expectedIsValid' => true,
            ],
            'hi∑ is valid windows-1252' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html; charset=windows-1252'],
                        $this->createMarkupContainingFragment("hi∑")
                    )
                ),
                'expectedIsValid' => true,
            ],
        ];
    }

    /**
     * @dataProvider convertToUtf8DataProvider
     *
     * @param WebPage $webPage
     */
    public function testConvertToUtf8(
        WebPage $webPage,
        string $expectedContentType,
        string $expectedCharacterSet,
        string $expectedCharacterEncoding,
        string $expectedContent
    ) {
        $mutatedWebPage = $this->contentEncodingValidator->convertToUtf8($webPage);
        $content = $mutatedWebPage->getContent();

        $this->assertNotSame($webPage, $mutatedWebPage);
        $this->assertEquals($expectedContentType, (string) $mutatedWebPage->getContentType());
        $this->assertEquals($expectedCharacterSet, $mutatedWebPage->getCharacterSet());
        $this->assertEquals($expectedCharacterEncoding, $mutatedWebPage->getCharacterEncoding());
        $this->assertEquals($expectedContent, $content);
        $this->assertNotFalse(mb_detect_encoding($content, null, true));
    }

    public function convertToUtf8DataProvider()
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        $emptyHtmlDocument = FixtureLoader::load('empty-document.html');

        return [
            'ASCII empty document, from content, no specific charset' => [
                'webPage' => WebPage::createFromContent($emptyHtmlDocument),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'utf-8',
                'expectedCharacterEncoding' => 'ascii',
                'expectedContent' => $emptyHtmlDocument,
            ],
            'ASCII empty document, from content, utf-7 charset' => [
                'webPage' => WebPage::createFromContent(
                    $emptyHtmlDocument,
                    ContentTypeFactory::createFromString('text/html; charset=utf-7')
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'utf-8',
                'expectedCharacterEncoding' => 'ascii',
                'expectedContent' => $emptyHtmlDocument,
            ],
            'ASCII empty document, from content, utf-8 charset' => [
                'webPage' => WebPage::createFromContent(
                    $emptyHtmlDocument,
                    ContentTypeFactory::createFromString('text/html; charset=utf-7')
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'utf-8',
                'expectedCharacterEncoding' => 'ascii',
                'expectedContent' => $emptyHtmlDocument,
            ],
            'ASCII empty document, from response, no specific charset' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(200, ['content-type' => 'text/html'], $emptyHtmlDocument)
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'utf-8',
                'expectedCharacterEncoding' => 'ascii',
                'expectedContent' => $emptyHtmlDocument,
            ],
            'ASCII empty document, from response, utf-7 specific charset' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(200, ['content-type' => 'text/html; charset=utf-7'], $emptyHtmlDocument)
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'utf-8',
                'expectedCharacterEncoding' => 'ascii',
                'expectedContent' => $emptyHtmlDocument,
            ],
            'big5 populated document, from content, no specific charset, conversion fails' => [
                'webPage' => WebPage::createFromContent(
                    mb_convert_encoding($this->createMarkupContainingFragment('搜'), 'big5', 'utf-8')
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'utf-8',
                'expectedCharacterEncoding' => 'ascii',
                'expectedContent' => $this->createMarkupContainingFragment('j'),
            ],
            'big5 populated document, from content, big5 charset' => [
                'webPage' => WebPage::createFromContent(
                    mb_convert_encoding($this->createMarkupContainingFragment('搜', 'big5'), 'big5', 'utf-8')
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'big5',
                'expectedCharacterEncoding' => 'utf-8',
                'expectedContent' => $this->createMarkupContainingFragment('搜', 'big5'),
            ],
            'big5 document, from content, big5 charset in document' => [
                'webPage' => WebPage::createFromContent(
                    FixtureLoader::load('document-with-big5-charset.html')
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'big5',
                'expectedCharacterEncoding' => 'utf-8',
                'expectedContent' => mb_convert_encoding(
                    FixtureLoader::load('document-with-big5-charset.html'),
                    'utf-8',
                    'big5'
                ),
            ],
            'big5 document, from response, big5 charset in document' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri(),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        FixtureLoader::load('document-with-big5-charset.html')
                    )
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'big5',
                'expectedCharacterEncoding' => 'utf-8',
                'expectedContent' => mb_convert_encoding(
                    FixtureLoader::load('document-with-big5-charset.html'),
                    'utf-8',
                    'big5'
                ),
            ],
            'gb2312-encoded document, from response, gb2312 charset in document' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        FixtureLoader::load('document-with-script-elements-charset=gb2312.html')
                    )
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'gb2312',
                'expectedCharacterEncoding' => 'utf-8',
                'expectedContent' => mb_convert_encoding(
                    FixtureLoader::load('document-with-script-elements-charset=gb2312.html'),
                    'utf-8',
                    'gb2312'
                ),
            ],
            'greek kosme fragment, from content' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html; charset=utf-8'],
                        $this->createMarkupContainingFragment("κόσμε")
                    )
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'utf-8',
                'expectedCharacterEncoding' => 'utf-8',
                'expectedContent' => $this->createMarkupContainingFragment("κόσμε"),
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
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'windows-1251',
                'expectedCharacterEncoding' => 'utf-8',
                'expectedContent' => mb_convert_encoding(
                    FixtureLoader::load('document-with-invalid-windows-1251-encoding.html'),
                    'utf-8',
                    'windows-1251'
                ),
            ],
            'hi∑ fragment from content, already utf-8' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html; charset=utf-8'],
                        $this->createMarkupContainingFragment("hi∑")
                    )
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'utf-8',
                'expectedCharacterEncoding' => 'utf-8',
                'expectedContent' => $this->createMarkupContainingFragment("hi∑"),
            ],
            'hi∑ fragment from content, already windows-1252, cannot be represented as utf-8' => [
                'webPage' => WebPage::createFromResponse(
                    new Uri('http://example.com/'),
                    new Response(
                        200,
                        ['content-type' => 'text/html; charset=windows-1252'],
                        $this->createMarkupContainingFragment("hi∑")
                    )
                ),
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedCharacterSet' => 'utf-8',
                'expectedCharacterEncoding' => 'utf-8',
                'expectedContent' => mb_convert_encoding(
                    $this->createMarkupContainingFragment("hi∑"),
                    'utf-8',
                    'windows-1252'
                ),
            ],
        ];
    }

    private function createMarkupContainingFragment(string $fragment, ?string $charset = null)
    {
        $charsetFragment = null === $charset
            ? ''
            : '<meta charset="' . $charset . '">';

        return sprintf(
            '<!doctype html><html lang="en"><head><title>%s</title>%s</head></html>',
            $fragment,
            $charsetFragment
        );
    }
}
