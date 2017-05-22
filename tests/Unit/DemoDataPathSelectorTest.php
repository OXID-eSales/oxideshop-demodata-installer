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

namespace OxidEsales\DemoDataInstaller\Tests\Unit;

use OxidEsales\DemoDataInstaller\DemoDataPathSelector;

class DemoDataPathSelectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerGetPathWithoutVendorPath
     */
    public function testGetPathWithoutVendorPath($edition, $expectedPath)
    {
        $facts = $this->getMock('Facts', ['getVendorPath']);
        $demoDataPathSelector = new DemoDataPathSelector($facts, $edition);
        $this->assertEquals($expectedPath, $demoDataPathSelector->getPath());
    }

    public function providerGetPathWithoutVendorPath()
    {
        return [
            ['CE', $this->getPathToOut('oxid-esales'.DIRECTORY_SEPARATOR.'oxideshop-demodata-ce')],
            ['PE', $this->getPathToOut('oxid-esales'.DIRECTORY_SEPARATOR.'oxideshop-demodata-pe')],
            ['EE', $this->getPathToOut('oxid-esales'.DIRECTORY_SEPARATOR.'oxideshop-demodata-ee')]
        ];
    }

    /**
     * @dataProvider providerGetPathWithVendorPath
     */
    public function testGetPathWithVendorPath($edition, $expectedPath)
    {
        $facts = $this->getMock('Facts', ['getVendorPath']);
        $facts->expects($this->any())->method('getVendorPath')->willReturn('vendor');
        $demoDataPathSelector = new DemoDataPathSelector($facts, $edition);
        $this->assertEquals($expectedPath, $demoDataPathSelector->getPath());
    }

    public function providerGetPathWithVendorPath()
    {
        return [
            ['CE', $this->getPathToOut('vendor'.DIRECTORY_SEPARATOR.'oxid-esales'.DIRECTORY_SEPARATOR.'oxideshop-demodata-ce')],
            ['PE', $this->getPathToOut('vendor'.DIRECTORY_SEPARATOR.'oxid-esales'.DIRECTORY_SEPARATOR.'oxideshop-demodata-pe')],
            ['EE', $this->getPathToOut('vendor'.DIRECTORY_SEPARATOR.'oxid-esales'.DIRECTORY_SEPARATOR.'oxideshop-demodata-ee')]
        ];
    }

    /**
     * Concat base path to the path to out directory.
     *
     * @param string $basePath
     *
     * @return string
     */
    private function getPathToOut($basePath)
    {
        $fullPath = [$basePath, 'src', 'out'];

        return implode(DIRECTORY_SEPARATOR, $fullPath);
    }
}
