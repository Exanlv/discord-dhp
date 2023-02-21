<?php

declare(strict_types=1);

namespace Tests\Exan\Fenrir\Rest\Helpers\GuildSticker;

use Exan\Fenrir\Rest\Helpers\GuildSticker\ModifyStickerBuilder;
use PHPUnit\Framework\TestCase;

class ModifyStickerBuilderTest extends TestCase
{
    public function testSetName()
    {
        $modifyStickerBuilder = new ModifyStickerBuilder();
        $modifyStickerBuilder->setName('::name::');

        $this->assertEquals(['name' => '::name::'], $modifyStickerBuilder->get());
    }

    public function testSetDescription()
    {
        $modifyStickerBuilder = new ModifyStickerBuilder();
        $modifyStickerBuilder->setDescription('::description::');

        $this->assertEquals(['description' => '::description::'], $modifyStickerBuilder->get());
    }

    public function testSetTags()
    {
        $modifyStickerBuilder = new ModifyStickerBuilder();
        $modifyStickerBuilder->setTags('::tags::');

        $this->assertEquals(['tags' => '::tags::'], $modifyStickerBuilder->get());
    }
}
