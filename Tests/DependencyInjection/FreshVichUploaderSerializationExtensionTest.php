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

use Doctrine\Common\Annotations\CachedReader;
use Fresh\VichUploaderSerializationBundle\DependencyInjection\FreshVichUploaderSerializationExtension;
use Fresh\VichUploaderSerializationBundle\EventListener\JmsSerializerSubscriber;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RequestContext;
use Vich\UploaderBundle\Storage\StorageInterface;

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
        $this->container->set(StorageInterface::class, new \stdClass());
        $this->container->set(RequestContext::class, new \stdClass());
        $this->container->set(CachedReader::class, new \stdClass());
        $this->container->set(Logger::class, new \stdClass());
        $this->container->set(PropertyAccessor::class, new \stdClass());

        $this->container->loadFromExtension($this->extension->getAlias());
        $this->container->compile();

        $this->assertArrayHasKey(JmsSerializerSubscriber::class, $this->container->getRemovedIds());
        $this->assertArrayNotHasKey(JmsSerializerSubscriber::class, $this->container->getDefinitions());
        $this->expectException(ServiceNotFoundException::class);
        $this->container->get(JmsSerializerSubscriber::class);
    }
}
