<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Command\Argument,
    Server\Command\Option,
    Exception\EmptyExecutableNotAllowed,
    Exception\EmptyEnvironmentKeyNotAllowed
};
use Innmind\Filesystem\StreamInterface;
use Innmind\Immutable\{
    Stream,
    Map,
    MapInterface
};

final class Command
{
    private $executable;
    private $parameters;
    private $environment;
    private $workingDirectory;
    private $input;
    private $background = false;

    public function __construct(string $executable)
    {
        if (empty($executable)) {
            throw new EmptyExecutableNotAllowed;
        }

        $this->executable = $executable;
        $this->parameters = new Stream('object');
        $this->environment = new Map('string', 'string');
    }

    /**
     * Will run the command in the background and will survive even if the
     * current process ends
     *
     * You will not have access to the process output nor if the process is
     * still running
     */
    public static function background(string $executable): self
    {
        $self = new self($executable);
        $self->background = true;

        return $self;
    }

    /**
     * Will run the command in a non blocking way but will be killed when the
     * current process ends
     */
    public static function foreground(string $executable): self
    {
        return new self($executable);
    }

    public function withArgument(string $value): self
    {
        $self = clone $this;
        $self->parameters = $this->parameters->add(new Argument($value));

        return $self;
    }

    public function withOption(string $key, string $value = null): self
    {
        $self = clone $this;
        $self->parameters = $this->parameters->add(Option::long($key, $value));

        return $self;
    }

    public function withShortOption(string $key, string $value = null): self
    {
        $self = clone $this;
        $self->parameters = $this->parameters->add(Option::short($key, $value));

        return $self;
    }

    public function withEnvironment(string $key, string $value): self
    {
        if (empty($key)) {
            throw new EmptyEnvironmentKeyNotAllowed;
        }

        $self = clone $this;
        $self->environment = $this->environment->put($key, $value);

        return $self;
    }

    public function withWorkingDirectory(string $path): self
    {
        if (empty($path)) {
            return $this;
        }

        $self = clone $this;
        $self->workingDirectory = $path;

        return $self;
    }

    public function withInput(StreamInterface $input): self
    {
        $self = clone $this;
        $self->input = $input;

        return $self;
    }

    public function environment(): MapInterface
    {
        return $this->environment;
    }

    public function hasWorkingDirectory(): bool
    {
        return is_string($this->workingDirectory);
    }

    public function workingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function hasInput(): bool
    {
        return $this->input instanceof StreamInterface;
    }

    public function input(): StreamInterface
    {
        return $this->input;
    }

    public function toBeRunInBackground(): bool
    {
        return $this->background;
    }

    public function __toString(): string
    {
        $string = $this->executable;

        if ($this->parameters->size() > 0) {
            $string .= ' '.$this->parameters->join(' ');
        }

        return $string;
    }
}
