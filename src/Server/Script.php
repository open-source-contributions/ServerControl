<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server,
    Exception\ScriptFailed,
};
use Innmind\Immutable\Sequence;

final class Script
{
    private Sequence $commands;

    public function __construct(Command ...$commands)
    {
        $this->commands = Sequence::of(Command::class, ...$commands);
    }

    public static function of(string ...$commands): self
    {
        return new self(...\array_map(
            static fn(string $command): Command => Command::foreground($command),
            $commands,
        ));
    }

    public function __invoke(Server $server): void
    {
        $processes = $server->processes();

        $this->commands->reduce(
            $processes,
            static function(Processes $processes, Command $command): Processes {
                $process = $processes->execute($command);
                $process->wait();
                $exitCode = $process->exitCode();

                if (!$exitCode->isSuccessful()) {
                    throw new ScriptFailed($command, $process);
                }

                return $processes;
            },
        );
    }
}
