<?php

/**
 * This file is part of MetaModels/attribute_decimal.
 *
 * (c) 2012-2024 The MetaModels team.
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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_decimal/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeDecimalBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use MetaModels\AttributeDecimalBundle\Attribute\Decimal;
use MetaModels\Helper\TableManipulator;
use MetaModels\IMetaModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests to test class Decimal.
 *
 * @covers \MetaModels\AttributeDecimalBundle\Attribute\Decimal
 */
class DecimalTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string   $language         The language.
     * @param string   $fallbackLanguage The fallback language.
     *
     * @return \MetaModels\IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage)
    {
        $metaModel = $this->getMockBuilder(IMetaModel::class)->getMock();

        $metaModel
            ->expects(self::any())
            ->method('getTableName')
            ->willReturn('mm_unittest');

        $metaModel
            ->expects(self::any())
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->expects(self::any())
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return MockObject|Connection
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
     * @return TableManipulator|MockObject
     */
    private function mockTableManipulator(Connection $connection)
    {
        return $this->getMockBuilder(TableManipulator::class)
            ->setConstructorArgs([$connection, []])
            ->getMock();
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $text = new Decimal($this->mockMetaModel('en', 'en'), [], $connection, $manipulator);
        $this->assertInstanceOf(Decimal::class, $text);
    }

    /**
     * Test provider for testSearchFor().
     *
     * @return array
     */
    public function searchForProvider()
    {
        return [
            ['10'],
            ['10.0'],
            [10],
            [10.5],
        ];
    }

    /**
     * Test the searchFor() method.
     *
     * @param string|int|float $value The value to search.
     *
     * @return void
     *
     * @dataProvider searchForProvider
     */
    public function testSearchFor($value)
    {
        $connection   = $this->mockConnection();
        $manipulator  = $this->mockTableManipulator($connection);
        $builder = $this
            ->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$connection])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($builder);

        $result = $this
            ->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchFirstColumn'])
            ->getMock();

        $result
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn([1, 2]);

        $builder->expects(self::once())->method('select')->with('t.id')->willReturn($builder);
        $builder->expects(self::once())->method('from')->with('mm_unittest', 't')->willReturn($builder);
        $builder->expects(self::once())->method('where')->with('t.test=:value')->willReturn($builder);
        $builder->expects(self::once())->method('setParameter')->with('value', $value)->willReturn($builder);
        $builder->expects(self::once())->method('executeQuery')->willReturn($result);

        $decimal = new Decimal(
            $this->mockMetaModel(
                'en',
                'en'
            ),
            ['colname' => 'test'],
            $connection,
            $manipulator
        );

        $this->assertEquals([1, 2], $decimal->searchFor($value));
    }

    /**
     * Test the searchFor() method with a wildcard.
     *
     * @return void
     */
    public function testSearchForWithWildcard()
    {
        $connection   = $this->mockConnection();
        $manipulator  = $this->mockTableManipulator($connection);
        $builder = $this
            ->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$connection])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($builder);

        $result = $this
            ->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchFirstColumn'])
            ->getMock();

        $result
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn([1, 2]);

        $builder->expects(self::once())->method('select')->with('t.id')->willReturn($builder);
        $builder->expects(self::once())->method('from')->with('mm_unittest', 't')->willReturn($builder);
        $builder->expects(self::once())->method('where')->with('t.test LIKE :pattern')->willReturn($builder);
        $builder->expects(self::once())->method('setParameter')->with('pattern', '10%')->willReturn($builder);
        $builder->expects(self::once())->method('executeQuery')->willReturn($result);

        $decimal = new Decimal(
            $this->mockMetaModel('en', 'en'),
            ['colname' => 'test'],
            $connection,
            $manipulator
        );

        $this->assertEquals([1, 2], $decimal->searchFor('10*'));
    }

    /**
     * Test the searchFor() method with a non numeric value.
     *
     * @return void
     */
    public function testSearchForWithNonNumeric()
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $decimal = new Decimal(
            $this->mockMetaModel(
                'en',
                'en'
            ),
            ['colname' => 'test'],
            $connection,
            $manipulator
        );

        $this->assertEquals([], $decimal->searchFor('abc'));
    }
}
