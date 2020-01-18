<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Remote,
    Server,
    Server\Processes,
    Server\Processes\RemoteProcesses,
    Server\Command
};
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User
};
use PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Server::class,
            new Remote(
                $this->createMock(Server::class),
                User::none(),
                Host::none(),
            ),
        );
    }

    public function testProcesses()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'ls'";
            }));

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            RemoteProcesses::class,
            $remote->processes()
        );
        $remote->processes()->execute(Command::foreground('ls'));
    }

    public function testProcessesViaSpecificPort()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "ssh '-p' '42' 'foo@example.com' 'ls'";
            }));

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
            Port::of(42),
        );

        $this->assertInstanceOf(
            RemoteProcesses::class,
            $remote->processes()
        );
        $remote->processes()->execute(Command::foreground('ls'));
    }
}
