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

namespace Fresh\VichUploaderSerializationBundle\Tests\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Fresh\VichUploaderSerializationBundle\EventListener\JmsSerializerSubscriber;
use Fresh\VichUploaderSerializationBundle\Exception\IncompatibleUploadableAndSerializableFieldAnnotationException;
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
    protected function setUp(): void
    {
        AnnotationRegistry::registerLoader('class_exists');

        $this->setFileSystemStorage('/uploads/photo.jpg', '/uploads/cover.jpg', '/uploads/photo.jpg', '/uploads/cover.jpg');

        $this->propertyAccessor = new PropertyAccessor();

        $this->annotationReader = new CachedReader(new AnnotationReader(), new ArrayCache());

        $this->logger = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->setMethods(['debug'])
            ->getMock()
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->dispatcher = null;
        $this->storage = null;
        $this->requestContext = null;
        $this->annotationReader = null;
        $this->propertyAccessor = null;
        $this->logger = null;
    }

    public function testSerializationWithIncludedHost(): void
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, '', $event);

        self::assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
    }

    public function testPostSerializationEvent(): void
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, '', $event);

        self::assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());

        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserA::class, '', $event);

        self::assertNotEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertNotEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
        self::assertEquals('photo.jpg', $user->getPhotoName());
        self::assertEquals('cover.jpg', $user->getCoverName());
    }

    public function testPostSerializationEventWithoutPreviousSerialization(): void
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserA::class, '', $event);

        self::assertNotEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertNotEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
        self::assertEquals('photo.jpg', $user->getPhotoName());
        self::assertEquals('cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithIncludedHttpHostAndPort(): void
    {
        $this->generateRequestContext(false, true);

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, '', $event);

        self::assertEquals('http://example.com:8000/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('http://example.com:8000/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithIncludedHttpsHostAndPort(): void
    {
        $this->generateRequestContext(true, true);

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, '', $event);

        self::assertEquals('https://example.com:8800/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('https://example.com:8800/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithoutIncludedHost(): void
    {
        $this->generateRequestContext();

        $user = (new UserB())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserB::class, '', $event);

        self::assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationWithHostInStorageUri(): void
    {
        $this->setFileSystemStorage('https://s3.example.com/uploads/photo.jpg', '/uploads/cover.jpg');
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, '', $event);

        self::assertEquals('https://s3.example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
    }

    public function testExceptionForIncompatibleAnnotations(): void
    {
        $this->generateRequestContext();

        $user = (new UserC())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $this->expectException(IncompatibleUploadableAndSerializableFieldAnnotationException::class);

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserC::class, '', $event);

        self::assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('/uploads/cover.jpg', $user->getCoverName());
    }

    public function testSerializationOfTheSameObjectTwice(): void
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, '', $event);

        self::assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());

        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, '', $event);

        self::assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
    }

    public function testDeserializationEventOfTheSameObjectTwice(): void
    {
        $this->generateRequestContext();

        $user = (new UserA())
            ->setPhotoName('photo.jpg')
            ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, '', $event);

        self::assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());

        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserA::class, '', $event);

        self::assertNotEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertNotEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
        self::assertEquals('photo.jpg', $user->getPhotoName());
        self::assertEquals('cover.jpg', $user->getCoverName());

        $event = new PreSerializeEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserA::class, '', $event);

        self::assertEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());

        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserA::class, '', $event);

        self::assertNotEquals('http://example.com/uploads/photo.jpg', $user->getPhotoName());
        self::assertNotEquals('http://example.com/uploads/cover.jpg', $user->getCoverName());
        self::assertEquals('photo.jpg', $user->getPhotoName());
        self::assertEquals('cover.jpg', $user->getCoverName());
    }

    public function testSerializationOfTheProxyObject(): void
    {
        $this->generateRequestContext();

        $picture = new UserPicture();
        $picture->setPhotoName('photo.jpg')
                ->setCoverName('cover.jpg');

        $context = DeserializationContext::create();
        $event = new PreSerializeEvent($context, $picture, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, UserPicture::class, '', $event);

        self::assertEquals('http://example.com/uploads/photo.jpg', $picture->getPhotoName());
        self::assertEquals('http://example.com/uploads/cover.jpg', $picture->getCoverName());

        $picture->setStatus(false);
        $event = new ObjectEvent($context, $picture, []);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, UserPicture::class, '', $event);

        self::assertNotEquals('http://example.com/uploads/photo.jpg', $picture->getPhotoName());
        self::assertNotEquals('http://example.com/uploads/cover.jpg', $picture->getCoverName());
        self::assertEquals('photo.jpg', $picture->getPhotoName());
        self::assertEquals('cover.jpg', $picture->getCoverName());
    }

    protected function generateRequestContext($https = false, $port = false): void
    {
        $this->requestContext = $this->getMockBuilder(RequestContext::class)
                                     ->disableOriginalConstructor()
                                     ->getMock();

        $scheme = $https ? 'https' : 'http';

        $this->requestContext->method('getScheme')->willReturn($scheme);
        $this->requestContext->method('getHost')->willReturn('example.com');

        if ($port) {
            if ($https) {
                $this->requestContext->method('getHttpsPort')->willReturn(8800);
            } else {
                $this->requestContext->method('getHttpPort')->willReturn(8000);
            }
        }

        $this->addJmsSerializerSubscriber();
    }

    protected function addJmsSerializerSubscriber(): void
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

    protected function setFileSystemStorage(...$uris): void
    {
        $this->storage = $this->getMockBuilder(FileSystemStorage::class)->disableOriginalConstructor()->getMock();
        $this->storage
            ->method('resolveUri')
            ->will($this->onConsecutiveCalls(...$uris))
      ;
    }
}
