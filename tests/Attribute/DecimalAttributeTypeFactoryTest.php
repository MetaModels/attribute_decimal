<?php

/**
 * This file is part of MetaModels/attribute_decimal.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_decimal
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_decimal/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeDecimalBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\AttributeDecimalBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeDecimalBundle\Attribute\Decimal;
use MetaModels\Helper\TableManipulator;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;
use MetaModels\MetaModel;

/**
 * Test the attribute factory.
 */
class DecimalAttributeTypeFactoryTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $tableName        The table name.
     *
     * @param string $language         The language.
     *
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($tableName, $language, $fallbackLanguage)
    {
        $metaModel = $this->getMockBuilder(MetaModel::class)->setMethods([])->setConstructorArgs([[]])->getMock();

        $metaModel
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue($tableName));

        $metaModel
            ->expects($this->any())
            ->method('getActiveLanguage')
            ->will($this->returnValue($language));

        $metaModel
            ->expects($this->any())
            ->method('getFallbackLanguage')
            ->will($this->returnValue($fallbackLanguage));

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock the table manipulator.
     *
     * @param Connection $connection The database connection mock.
     *
     * @return TableManipulator|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTableManipulator(Connection $connection)
    {
        return $this->getMockBuilder(TableManipulator::class)
            ->setConstructorArgs([$connection, []])
            ->getMock();
    }

    /**
     * Override the method to run the tests on the attribute factories to be tested.
     *
     * @return IAttributeTypeFactory[]
     */
    protected function getAttributeFactories()
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        return [new AttributeTypeFactory($connection, $manipulator)];
    }

    /**
     * Test creation of a decimal attribute.
     *
     * @return void
     */
    public function testCreateAttribute()
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $factory   = new AttributeTypeFactory($connection, $manipulator);
        $values    = [];
        $attribute = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $this->assertInstanceOf(Decimal::class, $attribute);

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $attribute->get($key), $key);
        }
    }
}
