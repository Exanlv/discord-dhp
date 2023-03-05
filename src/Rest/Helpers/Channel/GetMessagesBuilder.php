<?php

declare(strict_types=1);

namespace Exan\Fenrir\Rest\Helpers\Channel;

use Exan\Fenrir\Const\Validation\ItemLimit;
use Exan\Fenrir\Rest\Helpers\GetNew;

class GetMessagesBuilder
{
    use GetNew;

    private array $data = [];

    public function setAround(string $around): GetMessagesBuilder
    {
        $this->data['around'] = $around;

        return $this;
    }

    public function setBefore(string $before): GetMessagesBuilder
    {
        $this->data['before'] = $before;

        return $this;
    }

    public function setAfter(string $after): GetMessagesBuilder
    {
        $this->data['after'] = $after;

        return $this;
    }

    public function setLimit(int $limit): GetMessagesBuilder
    {
        $limit = ItemLimit::withinLimit($limit);

        $this->data['limit'] = $limit;

        return $this;
    }

    public function get(): array
    {
        return $this->data;
    }
}
