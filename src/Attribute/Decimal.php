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
 * @author     Cliff Parnitzky <github@cliff-parnitzky.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_decimal/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeDecimalBundle\Attribute;

use Doctrine\DBAL\Exception;
use MetaModels\Attribute\BaseSimple;

use function array_map;
use function array_merge;
use function is_numeric;
use function sprintf;

/**
 * This is the MetaModelAttribute class for handling decimal fields.
 */
class Decimal extends BaseSimple
{
    /**
     * {@inheritDoc}
     */
    public function getSQLDataType()
    {
        return 'double NULL default NULL';
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(
            parent::getAttributeSettingNames(),
            [
                'isunique',
                'mandatory',
                'filterable',
                'searchable',
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldDefinition($arrOverrides = [])
    {
        $arrFieldDef = parent::getFieldDefinition($arrOverrides);

        $arrFieldDef['inputType']    = 'text';
        $arrFieldDef['eval']['rgxp'] = 'digit';

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function filterGreaterThan($varValue, $blnInclusive = false)
    {
        return $this->getIdsFiltered($varValue, ($blnInclusive) ? '>=' : '>');
    }

    /**
     * {@inheritdoc}
     */
    public function filterLessThan($varValue, $blnInclusive = false)
    {
        return $this->getIdsFiltered($varValue, ($blnInclusive) ? '<=' : '<');
    }

    /**
     * {@inheritdoc}
     */
    public function filterNotEqual($varValue)
    {
        return $this->getIdsFiltered($varValue, '!=');
    }

    /**
     * {@inheritdoc}
     */
    public function searchFor($strPattern)
    {
        // If search with wildcard => parent implementation with "LIKE" search.
        if (str_contains($strPattern, '*') || \str_contains($strPattern, '?')) {
            return parent::searchFor($strPattern);
        }

        // Not with wildcard but also not numeric, impossible to get decimal results.
        if (!is_numeric($strPattern)) {
            return [];
        }

        // Do a simple search on given column.
        $statement = $this->connection->createQueryBuilder()
            ->select('t.id')
            ->from($this->getMetaModel()->getTableName(), 't')
            ->where('t.' . $this->getColName() . '=:value')
            ->setParameter('value', $strPattern)
            ->executeQuery();

        // Return value list as list<mixed>, parent function wants a list<string> so we make a cast.
        return array_map(static fn(mixed $value) => (string) $value, $statement->fetchFirstColumn());
    }

    /**
     * {@inheritDoc}
     *
     * This is needed for compatibility with MySQL strict mode so we will not write an empty string to decimal col.
     */
    public function serializeData($value)
    {
        return $value === '' ? null : $value;
    }

    /**
     * Filter all values by specified operation.
     *
     * @param int    $varValue     The value to use as upper end.
     * @param string $strOperation The specified operation like greater than, lower than etc.
     *
     * @return list<string> The list of item ids of all items matching the condition.
     *
     * @throws Exception
     */
    private function getIdsFiltered(int $varValue, string $strOperation): array
    {
        $strSql = sprintf(
            'SELECT t.id FROM %s AS t WHERE t.%s %s %f',
            $this->getMetaModel()->getTableName(),
            $this->getColName(),
            $strOperation,
            (float) $varValue
        );

        return $this->connection->executeQuery($strSql)->fetchFirstColumn();
    }
}
