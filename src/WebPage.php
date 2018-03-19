<?php

namespace webignition\WebResource\WebPage;

use QueryPath\DOMQuery;
use QueryPath\Exception as QueryPathException;
use webignition\CharacterSetList\CharacterSetList;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\WebResource;
use webignition\WebResource\Exception as WebResourceException;

/**
 * Models a web page
 */
class WebPage extends WebResource
{
    const CHARSET_GB2312 = 'GB2312';
    const CHARSET_BIG5 = 'BIG5';
    const CHARSET_UTF_8 = 'UTF-8';

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @throws WebResourceException
     */
    public function __construct()
    {
        $validContentTypes = array(
            'text/html',
            'application/xhtml+xml',
            'application/xml'
        );

        foreach ($validContentTypes as $validContentTypeString) {
            list($type, $subType) = explode('/', $validContentTypeString);
            $mediaType = new InternetMediaType();
            $mediaType->setType($type);
            $mediaType->setSubtype($subType);

            $this->addValidContentType($mediaType);
        }
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

        if ($this->hasHttpResponseCharacterSet() && $this->isValidCharacterSet($this->getHttpResponseCharacterSet())) {
            return $this->getHttpResponseCharacterSet();
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
        return $this->getWebPageParser()->getCharacterSet();
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
    private function hasHttpResponseCharacterSet()
    {
        return !is_null($this->getHttpResponseCharacterSet());
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
        return $this->getWebPageParser()->getIsContentTypeMalformed();
    }

    /**
     * @return string|null
     */
    public function getHttpResponseCharacterSet()
    {
        if (!$this->getContentType()->hasParameter('charset')) {
            return null;
        }

        return $this->getContentType()->getParameter('charset')->getValue();
    }

    /**
     * @return Parser
     */
    private function getWebPageParser()
    {
        if (is_null($this->parser)) {
            $this->parser = new Parser();
            $this->parser->setWebPage($this);
        }

        return $this->parser;
    }

    /**
     * @param string $cssSelector
     * @param array $options
     *
     * @return DOMQuery
     *
     * @throws QueryPathException
     */
    public function find($cssSelector, $options = array())
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
