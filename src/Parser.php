<?php

namespace webignition\WebResource\WebPage;

use QueryPath\DOMQuery;
use QueryPath\Exception as QueryPathException;
use webignition\InternetMediaType\Parser\ParseException;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;

class Parser
{
    /**
     * @var WebPage
     */
    private $webPage;

    /**
     * Was the detected content type provided in a correct way?
     *
     * We can detect some invalid means of specifying the content type and it's
     * useful to know whether what was found was formed correctly.
     *
     * @var bool
     */
    private $isContentTypeMalformed = null;

    public function setWebPage(WebPage $webPage)
    {
        $this->webPage = $webPage;
    }

    /**
     * @param string $cssSelector
     * @param array $options
     *
     * @return DOMQuery
     *
     * @throws QueryPathException
     */
    public function getDomQuery(string $cssSelector, $options = array()): DOMQuery
    {
        $options += array(
            'ignore_parser_warnings' => true,
            'convert_to_encoding' => 'ISO-8859-1',
            'convert_from_encoding' => 'auto',
            'use_parser' => 'html'
        );

        return new DOMQuery($this->webPage->getContent(), $cssSelector, $options);
    }

    /**
     * @return bool
     *
     * @throws QueryPathException
     * @throws UnparseableContentTypeException
     */
    public function getIsContentTypeMalformed(): bool
    {
        if (is_null($this->isContentTypeMalformed)) {
            $this->getCharacterSet();
        }

        return $this->isContentTypeMalformed;
    }

    /**
     * Get the character set from the current web page
     *
     * @return string|null
     *
     * @throws QueryPathException
     * @throws UnparseableContentTypeException
     */
    public function getCharacterSet(): ?string
    {
        $this->isContentTypeMalformed = false;

        $metaContentTypeSelectors = array(
            'meta[http-equiv=Content-Type]' => false,
            'meta[http-equiv=content-type]' => false,
            'meta[name=Content-Type]' => true // invalid but happens
        );

        foreach ($metaContentTypeSelectors as $metaContentTypeSelector => $isMalformed) {
            $contentTypeString = null;

            @$this->getDomQuery(
                $metaContentTypeSelector
            )->each(function ($index, \DOMElement $domElement) use (&$contentTypeString) {
                unset($index);
                $contentTypeString = $domElement->getAttribute('content');
            });

            if (is_string($contentTypeString)) {
                $mediaTypeParser = new InternetMediaTypeParser();
                $mediaTypeParser->setIgnoreInvalidAttributes(true);
                $mediaTypeParser->setAttemptToRecoverFromInvalidInternalCharacter(true);

                try {
                    $mediaType = $mediaTypeParser->parse($contentTypeString);

                    if ($mediaType->hasParameter('charset')) {
                        $this->isContentTypeMalformed = $isMalformed;
                        return (string)$mediaType->getParameter('charset')->getValue();
                    }
                } catch (ParseException $parseException) {
                    throw new UnparseableContentTypeException($contentTypeString);
                }
            }
        }

        $charsetString = '';
        @$this->getDomQuery('meta[charset]')->each(function ($index, \DOMElement $domElement) use (&$charsetString) {
            unset($index);
            $charsetString = $domElement->getAttribute('charset');
        });

        if (!empty($charsetString)) {
            return $charsetString;
        }

        return null;
    }
}
