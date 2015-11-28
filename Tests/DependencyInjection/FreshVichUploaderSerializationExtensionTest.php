<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Genvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\Tests\DependencyInjection;

use Fresh\VichUploaderSerializationBundle\DependencyInjection\FreshVichUploaderSerializationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * FreshVichUploaderSerializationExtensionTest
 *
 * @author Artem Genvald <genvaldartem@gmail.com>
 */
class FreshVichUploaderSerializationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FreshVichUploaderSerializationExtension $extension FreshVichUploaderSerializationExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder $container Container builder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->extension = new FreshVichUploaderSerializationExtension();
        $this->container = new ContainerBuilder();
        $this->container->registerExtension($this->extension);
    }

    /**
     * Test load extension
     */
    public function testLoadExtension()
    {
        // Add some dummy required services
        $this->container->set('vich_uploader.storage', new \StdClass());
        $this->container->set('router.request_context', new \StdClass());
        $this->container->set('annotations.cached_reader', new \StdClass());
        $this->container->set('logger', new \StdClass());

        $this->container->loadFromExtension($this->extension->getAlias());
        $this->container->compile();

        // Check that services have been loaded
        $this->assertTrue($this->container->has('vich_uploader.jms_serializer.listener'));
    }
}
