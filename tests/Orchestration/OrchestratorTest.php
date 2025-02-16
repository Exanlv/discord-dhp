<?php

declare(strict_types=1);

namespace Tests\Ragnarok\Fenrir\Orchestration;

use Carbon\Carbon;
use DateTimeInterface;
use Fakes\Ragnarok\Fenrir\LoopFake;
use Fakes\Ragnarok\Fenrir\PromiseFake;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Ragnarok\Fenrir\Orchestration\Orchestrator;
use Ragnarok\Fenrir\Parts\GatewayBot;
use Ragnarok\Fenrir\Parts\SessionStartLimit;
use Ragnarok\Fenrir\Rest\Gateway as HttpGateway;

class OrchestratorTest extends TestCase
{
    private const EXPECT_SPAWN = 'expect_spawn';
    private const EXPECT_RUN_TIMERS = 'expect_run_timers';

    /** @dataProvider orchestrationDataProvider */
    public function testItSpawnsBasedOnGatewayBotInfo(array $gatewayBots, array $expectations): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1));

        $loop = new LoopFake();
        /** @var HttpGateway&MockInterface */
        $gateway = Mockery::mock(HttpGateway::class);

        $orchestrator = new Orchestrator($loop, $gateway);

        $gateway->shouldReceive()
            ->getBot()
            ->andReturnUsing(function () use (&$gatewayBots) {
                $this->assertNotEmpty($gatewayBots, 'GatewayBot requested unexpectedly.');

                return PromiseFake::get(array_shift($gatewayBots));
            })
            ->once();

        $spawned = [];
        $orchestrator->on(Orchestrator::ALLOW_SPAWN, function (int $shard, int $totalShards) use (&$spawned) {
            $spawned[] = [$shard, $totalShards];
        });

        $orchestrator->init();

        foreach ($expectations as $expectation) {
            switch ($expectation['type']) {
                case self::EXPECT_SPAWN: {
                    $this->assertEquals($expectation['data'], $spawned);
                    $spawned = [];
                    break;
                }

                case self::EXPECT_RUN_TIMERS: {
                    $loop->runTimers($expectation['data'] ?? null);
                    break;
                }
            }
        }

        $this->assertEmpty($spawned);
    }

    private static function getGatewayBot(
        int $shards,
        int $sessionStartLimitTotal,
        int $sessionStartLimitRemaining,
        DateTimeInterface $sessionStartLimitResetAfter,
        int $sessionStartLimitConcurrency,
        string $url = '::url::'
    ): GatewayBot {
        $gatewayBot = new GatewayBot();
        $gatewayBot->url = $url;
        $gatewayBot->shards = $shards;

        $gatewayBot->session_start_limit = new SessionStartLimit();
        $gatewayBot->session_start_limit->total = $sessionStartLimitTotal;
        $gatewayBot->session_start_limit->remaining = $sessionStartLimitRemaining;
        $gatewayBot->session_start_limit->reset_after = $sessionStartLimitResetAfter->getTimestamp();
        $gatewayBot->session_start_limit->max_concurrency = $sessionStartLimitConcurrency;

        return $gatewayBot;
    }

    public static function orchestrationDataProvider()
    {
        return [
            'Ideal conditions, total % concurrency == 0' => [
                'gatewayBots' => [
                    self::getGatewayBot(
                        15,
                        15,
                        15,
                        Carbon::now()->addMinutes(5),
                        5,
                    ),
                ],
                'expectations' => [
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [0, 15],
                            [1, 15],
                            [2, 15],
                            [3, 15],
                            [4, 15],
                        ]
                        ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [5, 15],
                            [6, 15],
                            [7, 15],
                            [8, 15],
                            [9, 15],
                        ]
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [10, 15],
                            [11, 15],
                            [12, 15],
                            [13, 15],
                            [14, 15],
                        ]
                    ],
                ],
            ],

            'Less ideal conditions, total % concurrency == 3' => [
                'gatewayBots' => [
                    self::getGatewayBot(
                        18,
                        18,
                        18,
                        Carbon::now()->addMinutes(5),
                        5,
                    ),
                ],
                'expectations' => [
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [0, 18],
                            [1, 18],
                            [2, 18],
                            [3, 18],
                            [4, 18],
                        ]
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [5, 18],
                            [6, 18],
                            [7, 18],
                            [8, 18],
                            [9, 18],
                        ]
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [10, 18],
                            [11, 18],
                            [12, 18],
                            [13, 18],
                            [14, 18],
                        ]
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [15, 18],
                            [16, 18],
                            [17, 18],
                        ]
                    ],
                ],
            ],

            'It spawns only to the limit of remaining before waiting for reset, and fetching new allowance' => [
                'gatewayBots' => [
                    self::getGatewayBot(
                        28,
                        28,
                        15,
                        Carbon::createFromTimestamp(20),
                        5,
                    ),

                    self::getGatewayBot(
                        28,
                        28,
                        15,
                        Carbon::createFromTimestamp(50),
                        5,
                    ),
                ],
                'expectations' => [
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [0, 28],
                            [1, 28],
                            [2, 28],
                            [3, 28],
                            [4, 28],
                        ]
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [5, 28],
                            [6, 28],
                            [7, 28],
                            [8, 28],
                            [9, 28],
                        ]
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [10, 28],
                            [11, 28],
                            [12, 28],
                            [13, 28],
                            [14, 28],
                        ]
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    ['type' => self::EXPECT_RUN_TIMERS, 'data' => 15],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => []
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS, 'data' => 10],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [15, 28],
                            [16, 28],
                            [17, 28],
                            [18, 28],
                            [19, 28],
                        ]
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [20, 28],
                            [21, 28],
                            [22, 28],
                            [23, 28],
                            [24, 28],
                        ]
                    ],
                    ['type' => self::EXPECT_RUN_TIMERS],
                    [
                        'type' => self::EXPECT_SPAWN,
                        'data' => [
                            [25, 28],
                            [26, 28],
                            [27, 28],
                        ]
                    ],
                ],
            ],
        ];
    }
}
