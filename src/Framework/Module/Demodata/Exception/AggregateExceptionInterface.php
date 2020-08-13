<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception;

use Throwable;

interface AggregateExceptionInterface extends Throwable
{
    /**
     * @param Throwable $exception
     */
    public function add(Throwable $exception): void;

    /**
     * @return (Throwable)[]
     */
    public function getExceptions(): array;

    public function hasExceptions(): bool;

    /**
     * @param (Throwable)[] $exceptions
     */
    public static function throwExceptions(array $exceptions): void;
}
