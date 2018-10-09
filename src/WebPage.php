<?php

namespace webignition\WebResource\WebPage;

use Psr\Http\Message\ResponseInterface;
use webignition\CharacterSetList\CharacterSetList;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\WebResource;
use webignition\WebResource\WebResourcePropertiesInterface;
use webignition\WebResourceInterfaces\WebPageInterface;
use webignition\WebResourceInterfaces\WebResourceInterface;

class WebPage extends WebResource implements WebPageInterface
{
    const DEFAULT_CONTENT_TYPE_TYPE = 'text';
    const DEFAULT_CONTENT_TYPE_SUBTYPE = 'html';

    /**
     * @var WebPageInspector
     */
    private $inspector;

    /**
     * @var string|null
     */
    private $characterSet;

    /**
     * @param WebResourcePropertiesInterface $properties
     *
     * @throws InvalidContentTypeException
     * @throws UnparseableContentTypeException
     */
    public function __construct(?WebResourcePropertiesInterface $properties = null)
    {
        parent::__construct($properties);

        $this->inspector = new WebPageInspector($this);
    }

    public static function createFromContent(
        string $content,
        ?InternetMediaTypeInterface $contentType = null
    ) : WebResourceInterface {
        $contentType = static::getDefaultContentType();

        return parent::createFromContent($content, $contentType);
    }

    public function getInspector(): WebPageInspector
    {
        return $this->inspector;
    }

    public static function getDefaultContentType(): InternetMediaTypeInterface
    {
        return new InternetMediaType(self::DEFAULT_CONTENT_TYPE_TYPE, self::DEFAULT_CONTENT_TYPE_SUBTYPE);
    }

    public function setResponse(ResponseInterface $response): WebResourceInterface
    {
        $this->characterSet = null;

        return parent::setResponse($response);
    }

    public function getCharacterSet(): ?string
    {
        if (empty($this->characterSet)) {
            $this->characterSet = $this->deriveCharacterSet();
        }

        return $this->characterSet;
    }

    public function deriveCharacterSet(): ?string
    {
        $characterSetList = new CharacterSetList();

        $documentCharacterSet = $this->inspector->getCharacterSet();
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
