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

    /**
     * @param $contentTypeString
     */
    public function __construct($contentTypeString)
    {
        parent::__construct(sprintf(self::MESSAGE, $contentTypeString), self::CODE);

        $this->contentTypeString = $contentTypeString;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentTypeString;
    }
}
