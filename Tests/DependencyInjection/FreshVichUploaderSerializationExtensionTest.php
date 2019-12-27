<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Henvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
use Vich\UploaderBundle\Storage\GaufretteStorage;

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

    protected function setUp(): void
    {
        $this->extension = new FreshVichUploaderSerializationExtension();
        $this->container = new ContainerBuilder();
        $this->container->registerExtension($this->extension);
    }

    protected function tearDown(): void
    {
        unset(
            $this->extension,
            $this->container
        );
    }

    public function testLoadExtension(): void
    {
        // Add some dummy required services
        $this->container->set(GaufretteStorage::class, new \stdClass());
        $this->container->set(RequestContext::class, new \stdClass());
        $this->container->set(CachedReader::class, new \stdClass());
        $this->container->set(Logger::class, new \stdClass());
        $this->container->set(PropertyAccessor::class, new \stdClass());

        $this->container->loadFromExtension($this->extension->getAlias());
        $this->container->compile();

        self::assertArrayHasKey(JmsSerializerSubscriber::class, $this->container->getRemovedIds());
        self::assertArrayNotHasKey(JmsSerializerSubscriber::class, $this->container->getDefinitions());
        $this->expectException(ServiceNotFoundException::class);
        $this->container->get(JmsSerializerSubscriber::class);
    }
}
