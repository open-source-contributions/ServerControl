<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

interface Parameter
{
    public function toString(): string;
}
