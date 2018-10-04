<?php

namespace webignition\WebResource\WebPage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use QueryPath\DOMQuery;
use QueryPath\Exception as QueryPathException;
use webignition\CharacterSetList\CharacterSetList;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\ContentTypeFactory;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\WebResource;
use webignition\WebResourceInterfaces\WebPageInterface;
use webignition\WebResourceInterfaces\WebResourceInterface;

class WebPage extends WebResource implements WebPageInterface
{
    const DEFAULT_CONTENT_TYPE_TYPE = 'text';
    const DEFAULT_CONTENT_TYPE_SUBTYPE = 'html';

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var string|null
     */
    private $characterSet;

    protected function __construct(array $args)
    {
        parent::__construct($args);

        $this->parser = new Parser();
        $this->parser->setWebPage($this);
    }

    public static function getDefaultContentType(): InternetMediaType
    {
        $contentType = new InternetMediaType();
        $contentType->setType(self::DEFAULT_CONTENT_TYPE_TYPE);
        $contentType->setSubtype(self::DEFAULT_CONTENT_TYPE_SUBTYPE);

        return $contentType;
    }

    public function setResponse(ResponseInterface $response): WebResourceInterface
    {
        $this->characterSet = null;

        return parent::setResponse($response);
    }

    /**
     * @return null|string
     *
     * @throws QueryPathException
     * @throws UnparseableContentTypeException
     */
    public function getCharacterSet(): ?string
    {
        if (empty($this->characterSet)) {
            $this->characterSet = $this->deriveCharacterSet();
        }

        return $this->characterSet;
    }

    /**
     * @param string $cssSelector
     * @param array $options
     *
     * @return DOMQuery
     *
     * @throws QueryPathException
     * @throws UnparseableContentTypeException
     */
    public function find(string $cssSelector, array $options = []): DOMQuery
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
     * @throws UnparseableContentTypeException
     */
    private function convertToReadableCharacterSet(string $content): string
    {
        $comparatorCharacterSet = strtoupper($this->getCharacterSet());

        if (CharacterSets::CHARSET_GB2312 === $comparatorCharacterSet) {
            $content = iconv(CharacterSets::CHARSET_GB2312, CharacterSets::CHARSET_UTF_8, $content);
        }

        if (CharacterSets::CHARSET_BIG5 === $comparatorCharacterSet) {
            $content = iconv(CharacterSets::CHARSET_BIG5, CharacterSets::CHARSET_UTF_8, $content);
        }

        return $content;
    }

    /**
     * @throws QueryPathException
     * @throws UnparseableContentTypeException
     */
    public function deriveCharacterSet(): ?string
    {
        $characterSetList = new CharacterSetList();

        $documentCharacterSet = $this->parser->getCharacterSet();
        if ($characterSetList->contains($documentCharacterSet)) {
            return $documentCharacterSet;
        }

        if (!empty($this->getResponse())) {
            $responseCharacterSet = $this->getResponseCharacterSet();
            if ($characterSetList->contains($responseCharacterSet)) {
                return $responseCharacterSet;
            }
        }

        return null;
    }

    private function getResponseCharacterSet(): ?string
    {
        $charsetParameter = 'charset';
        $contentType = $this->getContentType();

        if (!$contentType->hasParameter($charsetParameter)) {
            return null;
        }

        return $contentType->getParameter($charsetParameter)->getValue();
    }

    public static function models(InternetMediaTypeInterface $internetMediaType): bool
    {
        return in_array($internetMediaType->getTypeSubtypeString(), self::getModelledContentTypeStrings());
    }

    public static function getModelledContentTypeStrings(): array
    {
        return [
            ContentTypes::CONTENT_TYPE_TEXT_HTML,
            ContentTypes::CONTENT_TYPE_APPLICATION_XML,
            ContentTypes::CONTENT_TYPE_TEXT_XML,
            ContentTypes::CONTENT_TYPE_APPLICATION_XHTML_XML,
        ];
    }
}
