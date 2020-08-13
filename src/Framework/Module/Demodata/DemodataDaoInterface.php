<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Framework\Module\Demodata;

use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateException;
use Symfony\Component\Filesystem\Exception\IOException;

interface DemodataDaoInterface
{
    /**
     * @throws AggregateException
     */
    public function checkPreconditions(): void;

    /**
     * @throws IOException
     */
    public function applyDemodata(): void;
}