<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\Tests\WebResource\WebPage;

use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;

class ContentTypeFactory
{
    public static function createFromString(string $contentTypeString): ?InternetMediaTypeInterface
    {
        $parser = new ContentTypeParser();

        return $parser->parse($contentTypeString);
    }
}
