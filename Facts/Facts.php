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

namespace OxidEsales\Facts;

use Webmozart\PathUtil\Path;

/**
 * Class responsible for providing information about the environment.
 */
class Facts
{
    /** @var string Path where to start the directory discovery from. */
    private $startPath = null;

    public function __construct($startPath = __DIR__)
    {
        $this->startPath = $startPath;
    }

    /**
     * @return string Root path of shop.
     */
    public function getShopRootPath()
    {
        $vendorPaths = [
            '/vendor',
            '/../vendor',
            '/../../vendor',
            '/../../../vendor',
            '/../../../../vendor',
        ];

        $rootPath = '';
        foreach ($vendorPaths as $vendorPath) {
            if (file_exists(Path::join($this->startPath, $vendorPath))) {
                $rootPath = Path::join($this->startPath, $vendorPath, '..');
                break;
            }
        }
        return $rootPath;
    }

    /**
     * @return string Path to vendor directory.
     */
    public function getVendorPath()
    {
        return Path::join($this->getShopRootPath(), 'vendor');
    }

    /**
     * @return string Path to source directory.
     */
    public function getSourcePath()
    {
        return Path::join($this->getShopRootPath(), 'source');
    }

    /**
     * @return string Path to ``out`` directory.
     */
    public function getOutPath()
    {
        return Path::join($this->getSourcePath(), 'out');
    }

    /**
     * @return string Eshop edition as capital two letters code.
     */
    public function getEdition()
    {
        return 'CE';
    }
}
