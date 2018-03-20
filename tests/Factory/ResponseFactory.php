<?php

namespace webignition\Tests\WebResource\WebPage\Factory;

use Mockery;
use Mockery\Mock;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use webignition\WebResource\WebResource;

class ResponseFactory
{
    const CONTENT_TYPE_HTML = 'text/html';

    /**
     * @param string $fixtureName
     * @param string $contentType
     *
     * @return Mock|ResponseInterface
     */
    public static function createFromFixture($fixtureName, $contentType = self::CONTENT_TYPE_HTML)
    {
        return self::create(FixtureLoader::load($fixtureName), $contentType);
    }

    /**
     * @param string $content
     * @param string $contentType
     *
     * @return Mock|ResponseInterface
     */
    public static function create($content, $contentType = self::CONTENT_TYPE_HTML)
    {
        /* @var ResponseInterface|Mock $response */
        $response = Mockery::mock(ResponseInterface::class);

        $response
            ->shouldReceive('getHeader')
            ->with(WebResource::HEADER_CONTENT_TYPE)
            ->andReturn([
                $contentType,
            ]);

        /* @var StreamInterface|Mock $bodyStream */
        $bodyStream = Mockery::mock(StreamInterface::class);
        $bodyStream
            ->shouldReceive('__toString')
            ->andReturn($content);

        $response
            ->shouldReceive('getBody')
            ->andReturn($bodyStream);

        return $response;
    }
}
