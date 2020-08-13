<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception;

use Exception;
use Throwable;
use function assert;
use function count;
use function reset;

class AggregateException extends Exception implements AggregateExceptionInterface
{
    /** @var (Throwable)[] */
    private $exceptions = [];

    /**
     * @param (Throwable)[] $exceptions
     */
    public function __construct(iterable $exceptions = [])
    {
        parent::__construct('Many exceptions have be thrown:');
        foreach ($exceptions as $exception) {
            $this->add($exception);
        }
    }

    /**
     * @param Throwable $exception
     */
    public function add(Throwable $exception): void
    {
        $this->exceptions[] = $exception;
        $this->message .= "\n" . $exception->getMessage();
    }

    /**
     * @return (Throwable)[]
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    public function hasExceptions(): bool
    {
        return ! empty($this->exceptions);
    }

    /**
     * @param (Throwable)[] $exceptions
     */
    public static function throwExceptions(array $exceptions): void
    {
        $count = count($exceptions);
        if ($count === 0) {
            return;
        }
        if ($count === 1) {
            $exception = reset($exceptions);
            assert($exception instanceof Throwable);
            throw $exception;
        }
        throw new self($exceptions);
    }
}