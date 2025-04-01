<?php

/**
 * This file is part of MetaModels/attribute_decimal.
 *
 * (c) 2012-2022 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_decimal
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_decimal/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

declare(strict_types=1);

namespace MetaModels\AttributeDecimalBundle\Schema;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use MetaModels\Information\AttributeInformation;
use MetaModels\Schema\Doctrine\AbstractAttributeTypeSchemaGenerator;

/**
 * This adds all alias columns to doctrine tables schemas.
 */
class DoctrineSchemaGenerator extends AbstractAttributeTypeSchemaGenerator
{
    /**
     * {@inheritDoc}
     */
    protected function getTypeName(): string
    {
        return 'decimal';
    }

    /**
     * {@inheritDoc}
     */
    protected function generateAttribute(Table $tableSchema, AttributeInformation $attribute): void
    {
        $this->setColumnData($tableSchema, $attribute->getName(), Types::FLOAT, [
            'notnull' => false,
        ]);
    }
}
