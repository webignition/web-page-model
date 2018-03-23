<?php

namespace webignition\WebResource\WebPage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use QueryPath\DOMQuery;
use QueryPath\Exception as QueryPathException;
use webignition\CharacterSetList\CharacterSetList;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\SpecificContentTypeWebResource;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResourceInterfaces\WebPageInterface;

class WebPage extends SpecificContentTypeWebResource implements WebPageInterface
{
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_APPLICATION_XML = 'application/xml';
    const CONTENT_TYPE_TEXT_XML = 'text/xml';
    const CONTENT_TYPE_APPLICATION_XHTML_XML = 'application/xhtml+xml';

    const CHARSET_GB2312 = 'GB2312';
    const CHARSET_BIG5 = 'BIG5';
    const CHARSET_UTF_8 = 'UTF-8';

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param ResponseInterface $response
     * @param string|UriInterface $uri
     *
     * @throws InvalidContentTypeException
     * @throws InternetMediaTypeParseException
     */
    public function __construct(ResponseInterface $response, UriInterface $uri = null)
    {
        parent::__construct($response, $uri);

        $this->parser = new Parser();
        $this->parser->setWebPage($this);
    }

    /**
     * {@inheritdoc}
     *
     * @throws QueryPathException
     */
    public function getCharacterSet()
    {
        $characterSetList = new CharacterSetList();

        $documentCharacterSet = $this->getDocumentCharacterSet();
        if ($characterSetList->contains($documentCharacterSet)) {
            return $documentCharacterSet;
        }

        $responseCharacterSet = $this->getResponseCharacterSet();
        if ($characterSetList->contains($responseCharacterSet)) {
            return $responseCharacterSet;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws QueryPathException
     */
    public function getDocumentCharacterSet()
    {
        return $this->parser->getCharacterSet();
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseCharacterSet()
    {
        $contentType = $this->getContentType();

        if (!$contentType->hasParameter('charset')) {
            return null;
        }

        return $contentType->getParameter('charset')->getValue();
    }

    /**
     * @param string $cssSelector
     * @param array $options
     *
     * @return DOMQuery
     *
     * @throws QueryPathException
     */
    public function find($cssSelector, array $options = [])
    {
        $content = $this->convertToReadableCharacterSet($this->getContent());

        $options += array(
            'ignore_parser_warnings' => true,
            'convert_to_encoding' => is_null($this->getCharacterSet()) ? 'utf-8' : $this->getCharacterSet(),
            'convert_from_encoding' => 'auto',
            'use_parser' => 'html'
        );

        $currentLibxmlUseInternalErrorsValue = libxml_use_internal_errors();
        libxml_use_internal_errors(true);

        $result = new DOMQuery($content, $cssSelector, $options);

        libxml_use_internal_errors($currentLibxmlUseInternalErrorsValue);

        return $result;
    }

    /**
     * @param string $content
     *
     * @return string
     *
     * @throws QueryPathException
     */
    private function convertToReadableCharacterSet($content)
    {
        $comparatorCharacterSet = strtoupper($this->getCharacterSet());

        if (self::CHARSET_GB2312 === $comparatorCharacterSet) {
            $content = iconv(self::CHARSET_GB2312, self::CHARSET_UTF_8, $content);
        }

        if (self::CHARSET_BIG5 === $comparatorCharacterSet) {
            $content = iconv(self::CHARSET_BIG5, self::CHARSET_UTF_8, $content);
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    protected static function getAllowedContentTypeStrings()
    {
        return [
            self::CONTENT_TYPE_TEXT_HTML,
            self::CONTENT_TYPE_APPLICATION_XML,
            self::CONTENT_TYPE_TEXT_XML,
            self::CONTENT_TYPE_APPLICATION_XHTML_XML,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected static function getAllowedContentTypePatterns()
    {
        return null;
    }
}
