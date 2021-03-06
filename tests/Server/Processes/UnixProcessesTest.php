<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes\UnixProcesses,
    Processes,
    Command,
    Process\ForegroundProcess,
    Process\BackgroundProcess,
    Signal
};
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class UnixProcessesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Processes::class, new UnixProcesses);
    }

    public function testExecute()
    {
        $processes = new UnixProcesses;
        $start = time();
        $process = $processes->execute(
            Command::foreground('php')->withArgument('fixtures/slow.php')
        );

        $this->assertTrue($process->isRunning());
        $this->assertInstanceOf(ForegroundProcess::class, $process);
        $process->wait();
        $this->assertTrue((time() - $start) >= 6);
    }

    public function testExecuteInBackground()
    {
        $processes = new UnixProcesses;
        $start = time();
        $process = $processes->execute(
            Command::background('php')->withArgument('fixtures/slow.php')
        );

        $this->assertInstanceOf(BackgroundProcess::class, $process);
        $this->assertTrue((time() - $start) < 2);
    }

    public function testExecuteWithInput()
    {
        $processes = new UnixProcesses;
        $process = $processes->execute(
            Command::foreground('cat')->withInput(new Stream(fopen('fixtures/symfony.log', 'r')))
        );
        $process->wait();

        $this->assertSame(
            file_get_contents('fixtures/symfony.log'),
            $process->output()->toString(),
        );
    }

    public function testKill()
    {
        $processes = new UnixProcesses;
        $start = time();
        $process = $processes->execute(
            Command::foreground('php')->withArgument('fixtures/slow.php')
        );

        $this->assertNull($processes->kill($process->pid(), Signal::kill()));
        sleep(1);
        $this->assertFalse($process->isRunning());
        $this->assertTrue((time() - $start) < 2);
    }
}
