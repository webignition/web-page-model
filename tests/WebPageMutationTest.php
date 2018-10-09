<?php

namespace webignition\Tests\WebResource\WebPage;

use Psr\Http\Message\UriInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\Exception\ReadOnlyResponseException;
use webignition\WebResource\Exception\UnseekableResponseException;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResourceProperties;

class WebPageMutationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebPage
     */
    private $webPage;

    /**
     * @var WebPage
     */
    private $updatedWebPage;

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertInstanceOf(WebPage::class, $this->webPage);
        $this->assertInstanceOf(WebPage::class, $this->updatedWebPage);
        $this->assertNotEquals(spl_object_hash($this->webPage), spl_object_hash($this->updatedWebPage));
    }

    /**
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function testSetUri()
    {
        $currentUri = \Mockery::mock(UriInterface::class);
        $newUri = \Mockery::mock(UriInterface::class);

        $this->webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_URI => $currentUri,
        ]));

        $this->assertEquals($currentUri, $this->webPage->getUri());
        $this->assertNull($this->webPage->getContent());
        $this->assertEquals('text/html', $this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());

        $this->updatedWebPage = $this->webPage->setUri($newUri);

        $this->assertEquals($currentUri, $this->webPage->getUri());
        $this->assertNull($this->webPage->getContent());
        $this->assertEquals('text/html', $this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());

        $this->assertEquals($newUri, $this->updatedWebPage->getUri());
        $this->assertNull($this->updatedWebPage->getContent());
        $this->assertEquals('text/html', $this->updatedWebPage->getContentType());
        $this->assertNull($this->updatedWebPage->getResponse());
    }

    /**
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function testSetContentTypeValidContentType()
    {
        $this->webPage = new WebPage();

        $this->assertNull($this->webPage->getUri());
        $this->assertNull($this->webPage->getContent());
        $this->assertEquals('text/html', $this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());

        $contentType = new InternetMediaType('application', 'xhtml+xml');

        $this->updatedWebPage = $this->webPage->setContentType($contentType);

        $this->assertNull($this->webPage->getUri());
        $this->assertNull($this->webPage->getContent());
        $this->assertEquals('text/html', $this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());

        $this->assertNull($this->updatedWebPage->getUri());
        $this->assertNull($this->updatedWebPage->getContent());
        $this->assertEquals('application/xhtml+xml', $this->updatedWebPage->getContentType());
        $this->assertNull($this->updatedWebPage->getResponse());
    }

    /**
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     * @throws ReadOnlyResponseException
     * @throws UnseekableResponseException
     */
    public function testSetContent()
    {
        $currentContent = 'current content';
        $newContent = 'new content';

        $this->webPage = new WebPage(WebResourceProperties::create([
            WebResourceProperties::ARG_CONTENT => $currentContent,
        ]));

        $this->assertNull($this->webPage->getUri());
        $this->assertEquals($currentContent, $this->webPage->getContent());
        $this->assertEquals('text/html', $this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());

        $this->updatedWebPage = $this->webPage->setContent($newContent);

        $this->assertNull($this->webPage->getUri());
        $this->assertEquals($currentContent, $this->webPage->getContent());
        $this->assertEquals('text/html', $this->webPage->getContentType());
        $this->assertNull($this->webPage->getResponse());


        $this->assertNull($this->updatedWebPage->getUri());
        $this->assertEquals($newContent, $this->updatedWebPage->getContent());
        $this->assertEquals('text/html', $this->updatedWebPage->getContentType());
        $this->assertNull($this->updatedWebPage->getResponse());
    }
}
