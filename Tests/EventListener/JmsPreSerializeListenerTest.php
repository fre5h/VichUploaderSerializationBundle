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
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Fresh\VichUploaderSerializationBundle\EventListener\JmsPreSerializeListener;
use Fresh\VichUploaderSerializationBundle\Tests\Fixtures;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\EventDispatcher\Events as JmsEvents;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Monolog\Logger;
use Symfony\Component\Routing\RequestContext;
use Vich\UploaderBundle\Storage\FileSystemStorage;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * JmsPreSerializeListenerTest.
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
     * @var StorageInterface|\PHPUnit_Framework_MockObject_MockObject $storage Vich storage
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
        AnnotationRegistry::registerLoader('class_exists');

        // Mock storage
        $this->storage = $this->getMockBuilder(FileSystemStorage::class)
                              ->disableOriginalConstructor()
                              ->getMock();
        $this->storage->expects($this->any())
                      ->method('resolveUri')
                      ->will($this->onConsecutiveCalls('/uploads/photo.jpg', '/uploads/cover.jpg'));

        $this->annotationReader = new CachedReader(new AnnotationReader(), new ArrayCache());

        // Mock logger
        $this->logger = $this->getMockBuilder('Monolog\Logger')
                             ->disableOriginalConstructor()
                             ->setMethods(['debug'])
                             ->getMock();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->dispatcher = null;
        $this->storage = null;
        $this->requestContext = null;
        $this->annotationReader = null;
        $this->logger = null;
    }

    public function testSerializationWithIncludedHost()
    {
        $this->generateRequestContext();

        $user = (new Fixtures\UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithIncludedHttpHostAndPort()
    {
        $this->generateRequestContext(false, true);

        $user = (new Fixtures\UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com:8000/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com:8000/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithIncludedHttpsHostAndPort()
    {
        $this->generateRequestContext(true, true);

        $user = (new Fixtures\UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserA::class, $context->getFormat(), $event);

        $this->assertEquals('https://example.com:8800/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('https://example.com:8800/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithoutIncludedHost()
    {
        $this->generateRequestContext();

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
     * @expectedException \Fresh\VichUploaderSerializationBundle\Exception\IncompatibleUploadableAndSerializableFieldAnnotationException
     */
    public function testExceptionForIncompatibleAnnotations()
    {
        $this->generateRequestContext();

        $user = (new Fixtures\UserC())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserC::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationOfTheSameObjectTwice()
    {
        $this->generateRequestContext();

        $user = (new Fixtures\UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());

        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationOfTheProxyObject()
    {
        $this->generateRequestContext();

        $picture = new Fixtures\UserPicture();
        $picture->setPhotoName('photo.jpg')
                ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event   = new ObjectEvent($context, $picture, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, Fixtures\UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $picture->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $picture->getCoverName());
    }

    protected function generateRequestContext($https = false, $port = false)
    {
        // Mock Request contest
        $this->requestContext = $this->getMockBuilder(RequestContext::class)
                                     ->disableOriginalConstructor()
                                     ->getMock();

        $scheme = ($https) ? 'https' : 'http';

        $this->requestContext->expects($this->any())
                             ->method('getScheme')
                             ->willReturn($scheme);

        $this->requestContext->expects($this->any())
                             ->method('getHost')
                             ->willReturn('example.com');

        if ($port) {
            if ($https) {
                $this->requestContext->expects($this->any())
                                     ->method('getHttpsPort')
                                     ->willReturn(8800);
            } else {
                $this->requestContext->expects($this->any())
                                     ->method('getHttpPort')
                                     ->willReturn(8000);
            }
        }

        $this->addEventListener();
    }

    protected function addEventListener()
    {
        $this->dispatcher = new EventDispatcher();
        $listener = new JmsPreSerializeListener($this->storage, $this->requestContext, $this->annotationReader, $this->logger);

        $this->dispatcher->addListener(JmsEvents::PRE_SERIALIZE, [$listener, 'onPreSerialize']);
    }
}
