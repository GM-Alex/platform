<?php declare(strict_types=1);

namespace Shopware\Storefront\Test\Framework\Cache\ReverseProxy;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Storefront\Framework\Cache\ReverseProxy\RedisReverseProxyGateway;

class RedisReverseProxyGatewayTest extends TestCase
{
    private RedisReverseProxyGateway $gateway;

    private \Redis $redis;

    private MockHandler $mockHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->redis = $this->createMock(\Redis::class);
        $this->mockHandler = new MockHandler();

        $this->gateway = new RedisReverseProxyGateway(
            ['http://localhost'],
            1,
            'BAN',
            $this->redis,
            new Client(['handler' => HandlerStack::create($this->mockHandler)])
        );
    }

    public function testDecorated(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->gateway->getDecorated();
    }

    public function testTagging(): void
    {
        $this->redis->expects(static::exactly(2))->method('lPush')->withConsecutive(['product-1', '/foo'], ['product-2', '/foo']);

        $this->gateway->tag(['product-1', 'product-2'], '/foo');
    }

    public function testInvalidate(): void
    {
        $this->redis->expects(static::once())->method('eval')->willReturn(['/foo']);
        $this->redis->expects(static::once())->method('del')->with('product-1');

        $this->mockHandler->append(new Response(200, [], null));

        $this->gateway->invalidate(['product-1']);

        static::assertNotNull($this->mockHandler->getLastRequest());
        static::assertSame('http://localhost/foo', $this->mockHandler->getLastRequest()->getUri()->__toString());
    }

    public function testInvalidateFails(): void
    {
        $this->redis->expects(static::once())->method('eval')->willReturn(['/foo']);

        $this->mockHandler->append(new Response(500, [], null));

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('BAN request failed to http://localhost/foo failed with error: Server error: `BAN http://localhost/foo` resulted in a `500 Internal Server Error` response');
        $this->gateway->invalidate(['product-1']);
    }
}
