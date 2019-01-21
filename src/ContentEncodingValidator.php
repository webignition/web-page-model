<?php

namespace webignition\WebResource\WebPage;

use webignition\InternetMediaType\Parameter\Parameter;
use webignition\StreamFactory\StreamFactory;

class ContentEncodingValidator
{
    const UTF_8_CHARACTER_ENCODING = 'utf-8';

    public function isValid(WebPage $webPage): bool
    {
        $content = $webPage->getContent();
        if (empty($content)) {
            return true;
        }

        $encoding = $webPage->getCharacterSet() ?? self::UTF_8_CHARACTER_ENCODING;
        $detectedEncoding = mb_detect_encoding($content, null, true);

        if ($encoding === $detectedEncoding) {
            return true;
        }

        if (false === $detectedEncoding) {
            return false;
        }

        return mb_check_encoding($content, $encoding);
    }

    public function convertToUtf8(WebPage $webPage): WebPage
    {
        $content = $this->convertContentToUtf8($webPage->getContent(), $webPage->getCharacterSet());

        /* @var WebPage $webPage */
        /** @noinspection PhpUnhandledExceptionInspection */
        $webPage = $webPage->setContent(
            $content,
            new StreamFactory()
        );

        $contentType = $webPage->getContentType();
        $charsetParameter = $contentType->getParameter('charset');

        if ($charsetParameter) {
            $charsetParameter->setValue(self::UTF_8_CHARACTER_ENCODING);
        } else {
            $contentType->addParameter(new Parameter('charset', self::UTF_8_CHARACTER_ENCODING));
        }

        $webPage = $webPage->setContentType($contentType);

        return $webPage;
    }

    private function convertContentToUtf8(string $content, ?string $characterSet): string
    {
        $currentEncoding = $characterSet;
        if (null === $currentEncoding) {
            $currentEncoding = mb_detect_encoding($content, mb_detect_order(), true);
        }

        if (false === $currentEncoding) {
            $currentEncoding = self::UTF_8_CHARACTER_ENCODING;
        }

        return iconv($currentEncoding, self::UTF_8_CHARACTER_ENCODING . '//IGNORE', $content);
    }
}
