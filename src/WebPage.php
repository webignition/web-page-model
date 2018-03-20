<?php

namespace webignition\WebResource\WebPage;

use Psr\Http\Message\ResponseInterface;
use QueryPath\DOMQuery;
use QueryPath\Exception as QueryPathException;
use webignition\CharacterSetList\CharacterSetList;
use webignition\WebResource\WebResource;

/**
 * Models a web page
 */
class WebPage extends WebResource
{
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_APPLICATION_XML = 'application/xml';
    const CONTENT_TYPE_TEXT_XML = 'text/xml';
    const APPLICATION_XML_SUB_CONTENT_TYPE_PATTERN = '/application\/[a-z]+\+xml/';

    const CHARSET_GB2312 = 'GB2312';
    const CHARSET_BIG5 = 'BIG5';
    const CHARSET_UTF_8 = 'UTF-8';

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param ResponseInterface $response
     * @param string|null $url
     *
     * @throws InvalidContentTypeException
     */
    public function __construct(ResponseInterface $response, $url = null)
    {
        parent::__construct($response, $url);

        $contentType = $this->getContentType();
        $contentTypeSubtypeString = $contentType->getTypeSubtypeString();

        $hasTextHtmlContentType = self::CONTENT_TYPE_TEXT_HTML === $contentTypeSubtypeString;
        $hasApplicationXmlContentType = self::CONTENT_TYPE_APPLICATION_XML === $contentTypeSubtypeString;
        $hasTextXmlContentType = self::CONTENT_TYPE_TEXT_XML === $contentTypeSubtypeString;

        if (!$hasTextHtmlContentType && !$hasApplicationXmlContentType && !$hasTextXmlContentType) {
            if (0 === preg_match(self::APPLICATION_XML_SUB_CONTENT_TYPE_PATTERN, $contentTypeSubtypeString)) {
                throw new InvalidContentTypeException($contentType);
            }
        }

        $this->parser = new Parser();
        $this->parser->setWebPage($this);
    }

    /**
     * Returns, in order of preference, first found to be valid of:
     *  - document character set
     *  - http response character set
     *
     * @return string|null
     *
     * @throws QueryPathException
     */
    public function getCharacterSet()
    {
        if ($this->hasDocumentCharacterSet() && $this->isValidCharacterSet($this->getDocumentCharacterSet())) {
            return $this->getDocumentCharacterSet();
        }

        if ($this->hasResponseCharacterSet() && $this->isValidCharacterSet($this->getResponseCharacterSet())) {
            return $this->getResponseCharacterSet();
        }

        return null;
    }

    /**
     * @return string
     *
     * @throws \QueryPath\Exception
     */
    public function getDocumentCharacterSet()
    {
        return $this->parser->getCharacterSet();
    }

    /**
     * @return bool
     *
     * @throws QueryPathException
     */
    private function hasDocumentCharacterSet()
    {
        return !is_null($this->getDocumentCharacterSet());
    }

    /**
     * @return bool
     */
    private function hasResponseCharacterSet()
    {
        return !is_null($this->getResponseCharacterSet());
    }

    /**
     * @param string $characterSet
     *
     * @return bool
     */
    private function isValidCharacterSet($characterSet)
    {
        $characterSetList = new CharacterSetList();

        return $characterSetList->contains($characterSet);
    }

    /**
     * @return bool
     */
    public function getDocumentCharacterSetDefinitionIsMalformed()
    {
        return $this->parser->getIsContentTypeMalformed();
    }

    /**
     * @return string|null
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
    public function find($cssSelector, $options = [])
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
}
