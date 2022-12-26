<?php

namespace Exan\Dhp\Websocket\Events;

/**
 * @see https://discord.com/developers/docs/topics/gateway-events#stage-instance-delete
 */
class StageInstanceDelete
{
    public string $token;
    public string $guild_id;
    public ?string $endpoint;
}
