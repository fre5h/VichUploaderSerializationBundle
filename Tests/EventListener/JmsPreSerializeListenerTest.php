<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Genvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\Tests\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Fresh\VichUploaderSerializationBundle\EventListener\JmsPreSerializeListener;
use Fresh\VichUploaderSerializationBundle\Tests\Fixtures;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\Events as JmsEvents;
use Monolog\Logger;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Vich\UploaderBundle\Storage\StorageInterface;
use JMS\Serializer\EventDispatcher\EventDispatcher;

/**
 * JmsPreSerializeListenerTest
 *
 * @author Artem Genvald <genvaldartem@gmail.com>
 */
class JmsPreSerializeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface $dispatcher Dispatcher
     */
    private $dispatcher;

    /**
     * @var StorageInterface $storage Vich storage
     */
    private $storage;

    /**
     * @var RequestContext $requestContext Request context
     */
    private $requestContext;

    /**
     * @var CachedReader $annotationReader Cached annotation reader
     */
    private $annotationReader;

    /**
     * @var Logger $logger Logger
     */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        // Trigger the autoloading of annotations
        class_exists('Doctrine\ORM\Mapping\Table');
        class_exists('Doctrine\ORM\Mapping\Entity');
        class_exists('JMS\Serializer\Annotation\ExclusionPolicy');
        class_exists('JMS\Serializer\Annotation\Expose');
        class_exists('JMS\Serializer\Annotation\SerializedName');
        class_exists('JMS\Serializer\Annotation\Exclude');
        class_exists('Vich\UploaderBundle\Mapping\Annotation\Uploadable');
        class_exists('Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableClass');
        class_exists('Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableField');

        // Mock storage
        $this->storage = $this->getMockBuilder('Vich\UploaderBundle\Storage\FileSystemStorage')
                              ->disableOriginalConstructor()
                              ->getMock();
        $this->storage->expects($this->any())
                      ->method('resolveUri')
                      ->will($this->onConsecutiveCalls('/uploads/photo.jpg', '/uploads/cover.jpg'));

        // Mock Request contest
        $this->requestContext = $this->getMockBuilder('Symfony\Component\Routing\RequestContext')
                                     ->disableOriginalConstructor()
                                     ->getMock();
        $this->requestContext->expects($this->any())
                             ->method('getScheme')
                             ->willReturn('http');
        $this->requestContext->expects($this->any())
                             ->method('getHost')
                             ->willReturn('example.com');

        $this->annotationReader = new CachedReader(new AnnotationReader(), new ArrayCache());

        // Mock logger
        $this->logger = $this->getMockBuilder('Monolog\Logger')
                             ->disableOriginalConstructor()
                             ->setMethods(['debug'])
                             ->getMock();

        $this->dispatcher = new EventDispatcher();
        $listener = new JmsPreSerializeListener($this->storage, $this->requestContext, $this->annotationReader, $this->logger);

        $this->dispatcher->addListener(JmsEvents::PRE_SERIALIZE, [$listener, 'onPreSerialize']);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->dispatcher       = null;
        $this->storage          = null;
        $this->requestContext   = null;
        $this->annotationReader = null;
        $this->logger           = null;
    }

    /**
     * Test serialization with included host in the URI
     */
    public function testSerializationWithIncludedHost()
    {
        $user = (new Fixtures\UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
    }

    /**
     * Test serialization without included host in the URI
     */
    public function testSerializationWithoutIncludedHost()
    {
        $user = (new Fixtures\UserB())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserB::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('/uploads/cover.jpg', $user->getCoverName());
    }

    /**
     * Test serialization without included host in the URI
     *
     * @expectedException \Fresh\VichUploaderSerializationBundle\Exception\IncompatibleUploadableAndSerializableFieldAnnotationException
     */
    public function testExceptionForIncompatibleAnnotations()
    {
        $user = (new Fixtures\UserC())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserC::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('/uploads/cover.jpg', $user->getCoverName());
    }
}
