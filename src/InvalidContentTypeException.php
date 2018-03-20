<?php

namespace webignition\WebResource\WebPage;

use webignition\InternetMediaType\InternetMediaType;

class InvalidContentTypeException extends \Exception
{
    const MESSAGE = 'Invalid content type: "%s"';
    const CODE = 0;

    /**
     * @var InternetMediaType
     */
    private $contentType;

    /**
     * @param InternetMediaType $contentType
     */
    public function __construct(InternetMediaType $contentType)
    {
        parent::__construct(sprintf(self::MESSAGE, (string)$contentType), self::CODE);

        $this->contentType = $contentType;
    }

    /**
     * @return InternetMediaType
     */
    public function getContentType()
    {
        return $this->contentType;
    }
}
