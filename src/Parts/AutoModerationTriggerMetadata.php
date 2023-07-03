<?php

declare(strict_types=1);

namespace Ragnarok\Fenrir\Parts;

use Ragnarok\Fenrir\Enums\AutoModerationKeywordPresetType;

class AutoModerationTriggerMetadata
{
    /**
     * @var string[]
     */
    public array $keyword_filter;
    /**
     * @var string[]
     */
    public array $regex_patterns;
    /**
     * @var \Ragnarok\Fenrir\Enums\AutoModerationKeywordPresetType[]
     */
    public array $presets;
    /**
     * @var string[]
     */
    public array $allow_list;
    public int $mention_total_limit;
    public bool $mention_raid_protection_enabled;

    public function setPresets(array $value): void
    {
        $this->presets = [];

        foreach ($value as $entry) {
            $this->presets[] = AutoModerationKeywordPresetType::from($entry);
        }
    }
}
