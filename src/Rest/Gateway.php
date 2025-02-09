<?php

declare(strict_types=1);

namespace Ragnarok\Fenrir\Rest;

use Discord\Http\Endpoint;
use Ragnarok\Fenrir\Parts\Gateway as PartsGateway;
use Ragnarok\Fenrir\Parts\GatewayBot;
use React\Promise\PromiseInterface;

/**
 * @see https://discord.com/developers/docs/events/gateway
 */
class Gateway extends HttpResource
{
    public function get(): PromiseInterface
    {
        return $this->mapPromise(
            $this->http->get(
                Endpoint::GATEWAY
            ),
            PartsGateway::class,
        );
    }

    public function getBot(): PromiseInterface
    {
        return $this->mapPromise(
            $this->http->get(
                Endpoint::GATEWAY_BOT
            ),
            GatewayBot::class,
        );
    }
}
