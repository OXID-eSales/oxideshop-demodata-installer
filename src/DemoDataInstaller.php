<?php
/**
 * This file is part of OXID eSales Demo Data Installer.
 *
 * OXID eSales Demo Data Installer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales Demo Data Installer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales Demo Data Installer.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2017
 */

namespace OxidEsales\DemoDataInstaller;

use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class responsible to copy demo images from needed OXID eShop edition in vendor
 * to the ``OUT`` directory so they would be browser accessible.
 */
class DemoDataInstaller
{
    /** @var string path to demo data directory */
    private $demoDataPath = null;

    /** @var string path to directory where to copy files to */
    private $outPath = null;

    /** @var \Symfony\Component\Filesystem\Filesystem filesystem component */
    private $filesystem = null;

    /**
     * Initialize with all needed dependencies.
     *
     * @param \OxidEsales\Facts\Facts $facts to get path to OXID eShop OUT directory.
     * @param \OxidEsales\DemoDataInstaller\DemoDataPathSelector $demoDataPathSelector to get path to demo data directory.
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem dependency which does actual copying.
     */
    public function __construct($facts, $demoDataPathSelector, $filesystem)
    {
        $this->demoDataPath = $demoDataPathSelector->getPath();
        $this->outPath = $facts->getOutPath();
        $this->filesystem = $filesystem;
    }

    /**
     * Copies DemoData images from vendor directory of needed edition
     * to the OXID eShop ``OUT`` directory.
     *
     * @return int error code
     */
    public function execute()
    {
        try {
            $this->filesystem->mirror($this->demoDataPath, $this->outPath);
        } catch (IOException $exception) {
            $items = [
                "Error occurred while copying files:",
                $exception->getMessage(),
                "\n"
            ];
            $message = implode(" ", $items);
            echo $message;
            return 1;
        }
        return 0;
    }
}
