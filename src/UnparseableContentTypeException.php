<?php

namespace webignition\WebResource\WebPage;

class UnparseableContentTypeException extends \Exception
{
    const MESSAGE = 'Unparseable content type "%s"';
    const CODE = 0;

    /**
     * @var string
     */
    private $contentTypeString;

    public function __construct(string $contentTypeString)
    {
        parent::__construct(sprintf(self::MESSAGE, $contentTypeString), self::CODE);

        $this->contentTypeString = $contentTypeString;
    }

    public function getContentType(): string
    {
        return $this->contentTypeString;
    }
}
