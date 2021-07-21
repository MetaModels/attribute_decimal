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
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use MetaModels\AttributeDecimalBundle\Attribute\Decimal;
use MetaModels\Helper\TableManipulator;
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
     *
     * @param string   $fallbackLanguage The fallback language.
     *
     * @return \MetaModels\IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage)
    {
        $metaModel = $this->getMockBuilder('MetaModels\IMetaModel')->getMock();

        $metaModel
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue('mm_unittest'));

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
     * Mock the Contao database.
     *
     * @param string|null   expectedQuery The query to expect.
     *
     * @param callable|null $callback     Callback which gets mocked statement passed.
     *
     * @return Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConnection(callable $callback = null, $expectedQuery = null, $queryMethod = 'prepare')
    {
        $mockDb = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->getMock();

        $mockDb->method('prepare')->willReturn($statement);
        $mockDb->method('query')->willReturn($statement);

        if ($callback) {
            call_user_func($callback, $statement);
        }

        if (!$expectedQuery || $expectedQuery === 'prepare') {
            $mockDb->expects($this->never())->method('query');
        }

        if (!$expectedQuery || $expectedQuery === 'query') {
            $mockDb->expects($this->never())->method('prepare');
        }

        if (!$expectedQuery) {
            return $mockDb;
        }

        $mockDb
            ->expects($this->once())
            ->method($queryMethod)
            ->with($expectedQuery);

        if ($queryMethod === 'prepare') {
            $statement
                ->expects($this->once())
                ->method('execute')
                ->willReturn(true);
        }

        return $mockDb;
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
        $queryBuilder = new QueryBuilder($connection);

        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $statement = $this->getMockBuilder(Statement::class)->getMock();
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN, 'id')
            ->willReturn([1, 2]);

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement);

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
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $queryBuilder = new QueryBuilder($connection);

        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $statement = $this->getMockBuilder(Statement::class)->getMock();
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN, 'id')
            ->willReturn([1, 2]);

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement);

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
