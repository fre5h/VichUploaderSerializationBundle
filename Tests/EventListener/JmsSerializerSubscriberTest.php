<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Henvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\Tests\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Fresh\VichUploaderSerializationBundle\EventListener\JmsSerializerSubscriber;
use Fresh\VichUploaderSerializationBundle\Tests\Fixtures\UserA;
use Fresh\VichUploaderSerializationBundle\Tests\Fixtures\UserB;
use Fresh\VichUploaderSerializationBundle\Tests\Fixtures\UserC;
use Fresh\VichUploaderSerializationBundle\Tests\Fixtures\UserPicture;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use JMS\Serializer\EventDispatcher\Events as JmsEvents;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RequestContext;
use Vich\UploaderBundle\Storage\FileSystemStorage;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * JmsSerializerSubscriberTest.
 *
 * @author Artem Henvald <genvaldartem@gmail.com>
 */
class JmsSerializerSubscriberTest extends TestCase
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var StorageInterface */
    private $storage;

    /** @var RequestContext */
    private $requestContext;

    /** @var CachedReader */
    private $annotationReader;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var Logger */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        AnnotationRegistry::registerLoader('class_exists');

        $this->storage = $this->getMockBuilder(FileSystemStorage::class)->disableOriginalConstructor()->getMock();
        $this->storage->expects($this->any())
                      ->method('resolveUri')
                      ->will($this->onConsecutiveCalls('/uploads/photo.jpg', '/uploads/cover.jpg', '/uploads/photo.jpg', '/uploads/cover.jpg'));

        $this->propertyAccessor = new PropertyAccessor();

        $this->annotationReader = new CachedReader(new AnnotationReader(), new ArrayCache());

        $this->logger = $this->getMockBuilder(Logger::class)
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
        $this->propertyAccessor = null;
        $this->logger = null;
    }

    public function testSerializationWithIncludedHost()
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
    }

    public function testPostSerializationEvent()
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());

        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertNotEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertNotEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
        $this->assertEquals('photo.jpg', $user->getPhotoName());
        $this->assertEquals('cover.jpg', $user->getCoverName());
    }

    public function testPostSerializationEventWithoutPreviousSerialization()
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertNotEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertNotEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
        $this->assertEquals('photo.jpg', $user->getPhotoName());
        $this->assertEquals('cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithIncludedHttpHostAndPort()
    {
        $this->generateRequestContext(false, true);

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com:8000/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com:8000/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithIncludedHttpsHostAndPort()
    {
        $this->generateRequestContext(true, true);

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertEquals('https://example.com:8800/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('https://example.com:8800/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithoutIncludedHost()
    {
        $this->generateRequestContext();

        $user = (new UserB())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserB::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('/uploads/cover.jpg', $user->getCoverName());
    }

    /**
     * @expectedException \Fresh\VichUploaderSerializationBundle\Exception\IncompatibleUploadableAndSerializableFieldAnnotationException
     */
    public function testExceptionForIncompatibleAnnotations()
    {
        $this->generateRequestContext();

        $user = (new UserC())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserC::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationOfTheSameObjectTwice()
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());

        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
    }

    public function testDeserializationEventOfTheSameObjectTwice()
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());

        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertNotEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertNotEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
        $this->assertEquals('photo.jpg', $user->getPhotoName());
        $this->assertEquals('cover.jpg', $user->getCoverName());

        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());

        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserA::class, $context->getFormat(), $event);

        $this->assertNotEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        $this->assertNotEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
        $this->assertEquals('photo.jpg', $user->getPhotoName());
        $this->assertEquals('cover.jpg', $user->getCoverName());
    }

    public function testSerializationOfTheProxyObject()
    {
        $this->generateRequestContext();

        $picture = new UserPicture();
        $picture->setPhotoName('photo.jpg')
                ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $picture, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserPicture::class, $context->getFormat(), $event);

        $this->assertEquals('http://example.com/uploads/photo.jpg', $picture->getPhotoName());
        $this->assertEquals('http://example.com/uploads/cover.jpg', $picture->getCoverName());

        $picture->setStatus(false);
        $event = new ObjectEvent($context, $picture, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserPicture::class, $context->getFormat(), $event);

        $this->assertNotEquals('http://example.com/uploads/photo.jpg', $picture->getPhotoName());
        $this->assertNotEquals('http://example.com/uploads/cover.jpg', $picture->getCoverName());
        $this->assertEquals('photo.jpg', $picture->getPhotoName());
        $this->assertEquals('cover.jpg', $picture->getCoverName());
    }

    protected function generateRequestContext($https = false, $port = false)
    {
        $this->requestContext = $this->getMockBuilder(RequestContext::class)
                                     ->disableOriginalConstructor()
                                     ->getMock();

        $scheme = $https ? 'https' : 'http';

        $this->requestContext->expects($this->any())->method('getScheme')->willReturn($scheme);
        $this->requestContext->expects($this->any())->method('getHost')->willReturn('example.com');

        if ($port) {
            if ($https) {
                $this->requestContext->expects($this->any())->method('getHttpsPort')->willReturn(8800);
            } else {
                $this->requestContext->expects($this->any())->method('getHttpPort')->willReturn(8000);
            }
        }

        $this->addJmsSerializerSubscriber();
    }

    protected function addJmsSerializerSubscriber()
    {
        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber(new JmsSerializerSubscriber(
            $this->storage,
            $this->requestContext,
            $this->annotationReader,
            $this->propertyAccessor,
            $this->logger
        ));
    }
}
