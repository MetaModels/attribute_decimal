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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_decimal/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeDecimalBundle\Test\DependencyInjection;

use MetaModels\AttributeDecimalBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeDecimalBundle\DependencyInjection\MetaModelsAttributeDecimalExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * This test case test the extension.
 *
 * @covers \MetaModels\AttributeDecimalBundle\DependencyInjection\MetaModelsAttributeDecimalExtension
 */
class MetaModelsAttributeDecimalExtensionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $extension = new MetaModelsAttributeDecimalExtension();

        $this->assertInstanceOf(MetaModelsAttributeDecimalExtension::class, $extension);
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    public function testFactoryIsRegistered(): void
    {
        $container = new ContainerBuilder();

        $extension = new MetaModelsAttributeDecimalExtension();
        $extension->load([], $container);

        self::assertTrue($container->hasDefinition('metamodels.attribute_decimal.factory'));
        $definition = $container->getDefinition('metamodels.attribute_decimal.factory');
        self::assertCount(1, $definition->getTag('metamodels.attribute_factory'));
    }
}
