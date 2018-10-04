<?php

namespace webignition\Tests\WebResource\WebPage;

use Psr\Http\Message\UriInterface;
use QueryPath\Exception as QueryPathException;
use webignition\WebResource\TestingTools\FixtureLoader;
use webignition\WebResource\WebPage\Parser;
use webignition\WebResource\WebPage\UnparseableContentTypeException;
use webignition\WebResource\WebPage\WebPage;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->parser = new Parser();
    }

    /**
     * @dataProvider getIsContentTypeMalformedDataProvider
     *
     * @param string $content
     * @param bool $expectedContentTypeIsMalformed
     *
     * @throws QueryPathException
     * @throws UnparseableContentTypeException
     */
    public function testGetIsContentTypeMalformed(string $content, bool $expectedContentTypeIsMalformed)
    {
        /* @var UriInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($uri, $content);

        $this->parser->setWebPage($webPage);

        $this->assertEquals($expectedContentTypeIsMalformed, $this->parser->getIsContentTypeMalformed());
    }

    public function getIsContentTypeMalformedDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'empty document' => [
                'content' => '',
                'expectedContentTypeIsMalformed' => false,
            ],
            'meta http-equiv="Content-Type" (valid)' => [
                'content' => FixtureLoader::load('empty-document-with-valid-http-equiv-content-type.html'),
                'expectedContentTypeIsMalformed' => false,
            ],
            'meta http-equiv="Content-Type" (valid, empty)' => [
                'content' => FixtureLoader::load('empty-document-with-empty-http-equiv-content-type.html'),
                'expectedContentTypeIsMalformed' => false,
            ],
            'meta http-equiv="content-type" (valid)' => [
                'content' => FixtureLoader::load('empty-document-with-valid-http-equiv-content-type-lowercase.html'),
                'expectedContentTypeIsMalformed' => false,
            ],
            'meta name="Content-Type" (valid value, malformed)' => [
                'content' => FixtureLoader::load('empty-document-with-malformed-http-equiv-content-type.html'),
                'expectedContentTypeIsMalformed' => true,
            ],
            'meta charset="foo" (invalid value, well-formed)' => [
                'content' => FixtureLoader::load('empty-document-with-invalid-meta-charset.html'),
                'expectedContentTypeIsMalformed' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCharacterSetSuccessDataProvider
     *
     * @param string $content
     * @param string|null $expectedCharacterSet
     *
     * @throws QueryPathException
     * @throws UnparseableContentTypeException
     */
    public function testGetCharacterSetSuccess(string $content, ?string $expectedCharacterSet)
    {
        /* @var UriInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($uri, $content);

        $this->parser->setWebPage($webPage);

        $this->assertSame($expectedCharacterSet, $this->parser->getCharacterSet());
    }

    public function getCharacterSetSuccessDataProvider(): array
    {
        return [
            'empty document' => [
                'content' => '',
                'expectedCharacterSet' => null,
            ],
            'meta http-equiv="Content-Type" (valid)' => [
                'content' => FixtureLoader::load('empty-document-with-valid-http-equiv-content-type.html'),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta http-equiv="Content-Type" (valid, empty)' => [
                'content' => FixtureLoader::load('empty-document-with-empty-http-equiv-content-type.html'),
                'expectedCharacterSet' => null,
            ],
            'meta http-equiv="content-type" (valid)' => [
                'content' => FixtureLoader::load('empty-document-with-valid-http-equiv-content-type-lowercase.html'),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta name="Content-Type" (valid value, malformed)' => [
                'content' => FixtureLoader::load('empty-document-with-malformed-http-equiv-content-type.html'),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta charset="foo" (invalid value, well-formed)' => [
                'content' => FixtureLoader::load('empty-document-with-invalid-meta-charset.html'),
                'expectedCharacterSet' => 'foo',
            ],
        ];
    }

    /**
     * @dataProvider getCharacterSetUnparseableContentTypeDataProvider
     *
     * @param string $content
     * @param string $expectedExceptionMessage
     * @param string $expectedContentType
     *
     * @throws QueryPathException
     */
    public function testGetCharacterSetUnparseableContentType(
        string $content,
        string $expectedExceptionMessage,
        string $expectedContentType
    ) {
        /* @var UriInterface $uri */
        $uri = \Mockery::mock(UriInterface::class);

        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($uri, $content);

        $this->parser->setWebPage($webPage);

        try {
            $this->parser->getCharacterSet();
            $this->fail(UnparseableContentTypeException::class . ' not thrown');
        } catch (UnparseableContentTypeException $unparseableContentTypeException) {
            $this->assertEquals(UnparseableContentTypeException::CODE, $unparseableContentTypeException->getCode());
            $this->assertEquals($expectedExceptionMessage, $unparseableContentTypeException->getMessage());
            $this->assertEquals($expectedContentType, $unparseableContentTypeException->getContentType());
        }
    }

    public function getCharacterSetUnparseableContentTypeDataProvider(): array
    {
        return [
            'meta name="Content-Type" (unparseable value, malformed)' => [
                'content' => FixtureLoader::load('empty-document-with-unparseable-http-equiv-content-type.html'),
                'expectedExceptionMessage' => 'Unparseable content type "f o o"',
                'expectedContentType' => 'f o o',
            ],
        ];
    }
}
