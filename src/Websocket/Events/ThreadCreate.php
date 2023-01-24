<?php

declare(strict_types=1);

namespace Exan\Dhp\Websocket\Events;

use Exan\Dhp\Parts\Channel;
use Exan\Dhp\Parts\Traits\WithOptionalNewlyCreated;
use Exan\Dhp\Parts\Traits\WithOptionalThreadMember;

/**
 * @see https://discord.com/developers/docs/topics/gateway-events#thread-create
 */
class ThreadCreate extends Channel
{
    use WithOptionalNewlyCreated;
    use WithOptionalThreadMember;
}
