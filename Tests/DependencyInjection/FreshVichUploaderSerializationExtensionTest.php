<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Henvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\Tests\DependencyInjection;

use Fresh\VichUploaderSerializationBundle\DependencyInjection\FreshVichUploaderSerializationExtension;
use Fresh\VichUploaderSerializationBundle\EventListener\JmsSerializerSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * FreshVichUploaderSerializationExtensionTest.
 *
 * @author Artem Henvald <genvaldartem@gmail.com>
 */
class FreshVichUploaderSerializationExtensionTest extends TestCase
{
    /** @var FreshVichUploaderSerializationExtension */
    private $extension;

    /** @var ContainerBuilder */
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

    public function testLoadExtension()
    {
        // Add some dummy required services
        $this->container->set('vich_uploader.storage', new \stdClass());
        $this->container->set('router.request_context', new \stdClass());
        $this->container->set('annotations.cached_reader', new \stdClass());
        $this->container->set('logger', new \stdClass());
        $this->container->set('property_accessor', new \stdClass());

        $this->container->loadFromExtension($this->extension->getAlias());
        $this->container->compile();

        $this->assertArrayHasKey(JmsSerializerSubscriber::class, $this->container->getRemovedIds());
        $this->assertArrayNotHasKey(JmsSerializerSubscriber::class, $this->container->getDefinitions());
        $this->expectException(ServiceNotFoundException::class);
        $this->container->get(JmsSerializerSubscriber::class);
    }
}
