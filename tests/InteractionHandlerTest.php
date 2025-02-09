<?php

declare(strict_types=1);

namespace Tests\Ragnarok\Fenrir;

use Fakes\Ragnarok\Fenrir\DataMapperFake;
use Fakes\Ragnarok\Fenrir\DiscordFake;
use Fakes\Ragnarok\Fenrir\PromiseFake;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Ragnarok\Fenrir\Component\Button\DangerButton;
use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Enums\InteractionType;
use Ragnarok\Fenrir\EventHandler;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Gateway\Objects\Payload;
use Ragnarok\Fenrir\Interaction\ButtonInteraction;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\InteractionHandler;
use Ragnarok\Fenrir\Parts\ApplicationCommand;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use React\Promise\Promise;

class InteractionHandlerTest extends MockeryTestCase
{
    private function emitReady(EventHandler $eventHandler): void
    {
        /** @var Payload */
        $payload = DataMapperFake::get()->map((object) [
            'op' => 0,
            't' => Events::READY,
            'd' => (object) [
                'user' => (object) [
                    'id' => '::bot user id::',
                ],
            ],
        ], Payload::class);

        $eventHandler->handle(
            $payload
        );
    }

    public function testRegisterGlobalCommand(): void
    {
        $discord = DiscordFake::get();

        $interactionHandler = new InteractionHandler();
        $interactionHandler->initialize($discord);

        $commandBuilder = CommandBuilder::new()
            ->setName('command')
            ->setDescription('::description::');

        $applicationCommand = new ApplicationCommand();
        $applicationCommand->id = '::application command id::';
        $discord->rest->globalCommand
            ->shouldReceive('createApplicationCommand')
            ->with('::bot user id::', $commandBuilder)
            ->andReturn(PromiseFake::get($applicationCommand))
            ->once();

        $interactionHandler->registerGlobalCommand(
            $commandBuilder,
            static fn (CommandInteraction $command) => 1
        );

        $this->emitReady($discord->gateway->events);
    }

    public function testRegisterGuildCommand(): void
    {
        $discord = DiscordFake::get();

        $interactionHandler = new InteractionHandler();
        $interactionHandler->initialize($discord);

        $commandBuilder = CommandBuilder::new()
            ->setName('command')
            ->setDescription('::description::');

        $applicationCommand = new ApplicationCommand();
        $applicationCommand->id = '::application command id::';
        $discord->rest->guildCommand
            ->shouldReceive('createApplicationCommand')
            ->with('::bot user id::', '::guild id::', $commandBuilder)
            ->andReturn(PromiseFake::get($applicationCommand))
            ->once();

        $interactionHandler->registerGuildCommand(
            $commandBuilder,
            '::guild id::',
            static fn (CommandInteraction $command) => 1
        );

        $this->emitReady($discord->gateway->events);
    }

    public function testRegisterCommandIsGlobalWithoutDevGuild(): void
    {
        $discord = DiscordFake::get();

        $interactionHandler = new InteractionHandler();
        $interactionHandler->initialize($discord);

        $commandBuilder = CommandBuilder::new()
            ->setName('command')
            ->setDescription('::description::');

        $discord->rest->globalCommand
            ->shouldReceive('createApplicationCommand')
            ->with('::bot user id::', $commandBuilder)
            ->andReturn(new Promise(static fn ($resolver) => $resolver))
            ->once();

        $interactionHandler->registerCommand(
            $commandBuilder,
            static fn (CommandInteraction $command) => 1
        );

        $this->emitReady($discord->gateway->events);
    }

    public function testItHandlesAnInteraction(): void
    {
        $discord = DiscordFake::get();

        $interactionHandler = new InteractionHandler();
        $interactionHandler->initialize($discord);

        $commandBuilder = CommandBuilder::new()
            ->setName('command')
            ->setDescription('::description::');

        $discord->rest->globalCommand
            ->shouldReceive('createApplicationCommand')
            ->with('::bot user id::', $commandBuilder)
            ->andReturn(PromiseFake::get(
                DataMapperFake::get()->map((object) [
                    'id' => '::application command id::',
                ], ApplicationCommand::class)
            ))
            ->once();

        $discord->rest->guildCommand
            ->shouldReceive('createApplicationCommand')
            ->with('::bot user id::', '::guild id::', $commandBuilder)
            ->andReturn(PromiseFake::get(
                DataMapperFake::get()->map((object) [
                    'id' => '::guild application command id::',
                ], ApplicationCommand::class)
            ))
            ->once();

        $hasRun = false;

        $interactionHandler->registerGlobalCommand(
            $commandBuilder,
            function ($command) use (&$hasRun) {
                $hasRun = true;

                $this->assertInstanceOf(CommandInteraction::class, $command);
            }
        );

        $interactionHandler->registerGuildCommand(
            $commandBuilder,
            '::guild id::',
            function ($command) use (&$hasRun) {
                $hasRun = true;

                $this->assertInstanceOf(CommandInteraction::class, $command);
            }
        );

        $this->emitReady($discord->gateway->events);

        /** @var InteractionCreate */
        $interactionCreate = DataMapperFake::get()->map((object) [
            'type' => InteractionType::APPLICATION_COMMAND->value,
            'data' => (object) [
                'id' => '::application command id::',
            ],
        ], InteractionCreate::class);

        $discord->gateway->events->emit(Events::INTERACTION_CREATE, [$interactionCreate]);

        $this->assertTrue($hasRun, 'Command handler has not been run');
    }

    public function testItIgnoresCommandIfNoHanlderIsRegistered(): void
    {
        $discord = DiscordFake::get();

        $interactionHandler = new InteractionHandler();
        $interactionHandler->initialize($discord);

        $commandBuilder = CommandBuilder::new()
            ->setName('command')
            ->setDescription('::description::');

        $discord->rest->globalCommand
            ->shouldReceive('createApplicationCommand')
            ->with('::bot user id::', $commandBuilder)
            ->andReturn(PromiseFake::get(
                DataMapperFake::get()->map((object) [
                    'id' => '::application command id::',
                ], ApplicationCommand::class)
            ))
            ->once();

        $hasRun = false;

        $interactionHandler->registerGlobalCommand(
            $commandBuilder,
            static function ($command) use (&$hasRun) {
                $hasRun = true;
            }
        );

        $this->emitReady($discord->gateway->events);

        /** @var InteractionCreate */
        $interactionCreate = DataMapperFake::get()->map((object) [
            'type' => InteractionType::APPLICATION_COMMAND->value,
            'data' => (object) [
                'id' => '::other application command id::',
            ],
        ], InteractionCreate::class);

        $discord->gateway->events->emit(Events::INTERACTION_CREATE, [$interactionCreate]);

        $this->assertFalse($hasRun, 'Command handler should not have been run');
    }

    public function testItCanRegisterButtonInteractionHandlers(): void
    {
        $discord = DiscordFake::get();
        $interactionHandler = new InteractionHandler();
        $interactionHandler->initialize($discord);

        $button = new DangerButton('::custom id::');

        $hasRun = false;
        $interactionHandler->onButtonInteraction(
            $button,
            static function (ButtonInteraction $buttonInteraction) use (&$hasRun) {
                $hasRun = true;
            }
        );

        $interactionCreate = DataMapperFake::get()->map((object) [
                'id' => '::interaction id::',
                'token' => '::token::',
                'type' => InteractionType::MESSAGE_COMPONENT->value,
                'application_id' => '::application id::',
                'data' => (object) [
                    'component_type' => 2, // @todo enum
                    'custom_id' => '::custom id::',
                ],
            ], InteractionCreate::class);

        $discord->gateway->events->emit(Events::INTERACTION_CREATE, [$interactionCreate]);

        $this->assertTrue($hasRun, 'Handler did not run');
    }

    public function testItRemovesButtonListenerIfHandlerReturnsTrue(): void
    {
        $discord = DiscordFake::get();
        $interactionHandler = new InteractionHandler();
        $interactionHandler->initialize($discord);

        $button = new DangerButton('::custom id::');

        $runs = 0;
        $interactionHandler->onButtonInteraction(
            $button,
            static function (ButtonInteraction $buttonInteraction) use (&$runs) {
                $runs++;

                return true;
            }
        );

        $interactionCreate = DataMapperFake::get()->map((object) [
                'id' => '::interaction id::',
                'token' => '::token::',
                'type' => InteractionType::MESSAGE_COMPONENT->value,
                'application_id' => '::application id::',
                'data' => (object) [
                    'component_type' => 2, // @todo enum
                    'custom_id' => '::custom id::',
                ],
            ], InteractionCreate::class);

        $discord->gateway->events->emit(Events::INTERACTION_CREATE, [$interactionCreate]);

        $this->assertEquals(1, $runs, 'Handler did not run');

        $discord->gateway->events->emit(Events::INTERACTION_CREATE, [$interactionCreate]);

        $this->assertEquals(1, $runs, 'Handler ran incorrect number of times');
    }
}
