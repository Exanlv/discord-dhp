<?php

declare(strict_types=1);

namespace Ragnarok\Fenrir\Rest;

use Psr\Log\LoggerInterface;
use Ragnarok\Fenrir\DataMapper;
use Ragnarok\Fenrir\Http\Scheduler;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Rest
{
    public readonly Application $application;
    public readonly ApplicationRoleConnectionMetadata $applicationRoleConnectionMetadata;
    public readonly AuditLog $auditLog;
    public readonly Channel $channel;
    public readonly Emoji $emoji;
    public readonly GuildAutoModeration $guildAutoModeration;
    public readonly GuildScheduledEvent $guildScheduledEvent;
    public readonly GuildSticker $guildSticker;
    public readonly GuildTemplate $guildTemplate;
    public readonly Invite $invite;
    public readonly StageInstance $stageInstance;
    public readonly Sticker $sticker;
    public readonly User $user;
    public readonly GuildCommand $guildCommand;
    public readonly GlobalCommand $globalCommand;
    public readonly Webhook $webhook;
    public readonly Guild $guild;

    public function __construct(private Scheduler $http, private DataMapper $dataMapper, private LoggerInterface $logger)
    {
        $args = [$this->http, $this->dataMapper, $this->logger];

        $this->application = new Application(...$args);
        $this->applicationRoleConnectionMetadata = new ApplicationRoleConnectionMetadata(...$args);
        $this->auditLog = new AuditLog(...$args);
        $this->channel = new Channel(...$args);
        $this->emoji = new Emoji(...$args);
        $this->guildAutoModeration = new GuildAutoModeration(...$args);
        $this->guildScheduledEvent = new GuildScheduledEvent(...$args);
        $this->guildSticker = new GuildSticker(...$args);
        $this->guildTemplate = new GuildTemplate(...$args);
        $this->invite = new Invite(...$args);
        $this->stageInstance = new StageInstance(...$args);
        $this->sticker = new Sticker(...$args);
        $this->user = new User(...$args);
        $this->guildCommand = new GuildCommand(...$args);
        $this->globalCommand = new GlobalCommand(...$args);
        $this->webhook = new Webhook(...$args);
        $this->guild = new Guild(...$args);
    }
}
