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

namespace Fresh\VichUploaderSerializationBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\Proxy;
use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableClass;
use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableField;
use Fresh\VichUploaderSerializationBundle\Exception\IncompatibleUploadableAndSerializableFieldAnnotationException;
use Generator;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RequestContext;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * JmsSerializerSubscriber.
 *
 * @author Artem Henvald <genvaldartem@gmail.com>
 */
class JmsSerializerSubscriber implements EventSubscriberInterface
{
    private const HTTP_PORT = 80;

    private const HTTPS_PORT = 443;

    /** @var StorageInterface */
    private $storage;

    /** @var RequestContext */
    private $requestContext;

    /** @var Reader */
    private $annotationReader;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var LoggerInterface */
    private $logger;

    /** @var mixed[] */
    private $serializedObjects = [];

    /**
     * @param StorageInterface          $storage
     * @param RequestContext            $requestContext
     * @param Reader                    $annotationReader
     * @param PropertyAccessorInterface $propertyAccessor
     * @param LoggerInterface           $logger
     */
    public function __construct(StorageInterface $storage, RequestContext $requestContext, Reader $annotationReader, PropertyAccessorInterface $propertyAccessor, LoggerInterface $logger)
    {
        $this->storage = $storage;
        $this->requestContext = $requestContext;
        $this->annotationReader = $annotationReader;
        $this->propertyAccessor = $propertyAccessor;
        $this->logger = $logger;
    }

    /**
     * @return Generator
     */
    public static function getSubscribedEvents(): Generator
    {
        yield ['event' => Events::PRE_SERIALIZE, 'method' => 'onPreSerialize'];
        yield ['event' => Events::POST_SERIALIZE, 'method' => 'onPostSerialize'];
    }

    /**
     * @param PreSerializeEvent $event
     *
     * @throws IncompatibleUploadableAndSerializableFieldAnnotationException
     */
    public function onPreSerialize(PreSerializeEvent $event): void
    {
        $object = $event->getObject();
        if (!\is_object($object)) {
            return;
        }

        if ($object instanceof Proxy && !$object->__isInitialized()) {
            $object->__load();
        }

        $objectUid = \spl_object_hash($object);
        if (\array_key_exists($objectUid, $this->serializedObjects)) {
            return;
        }

        /** @var class-string<object> $className */
        $className = ClassUtils::getClass($object);

        $classAnnotation = $this->annotationReader->getClassAnnotation(
            new \ReflectionClass($className),
            VichSerializableClass::class
        );

        if ($classAnnotation instanceof VichSerializableClass) {
            $reflectionClass = ClassUtils::newReflectionClass(\get_class($object));
            $this->logger->debug(\sprintf(
                'Found @VichSerializableClass annotation for the class "%s"',
                $reflectionClass->getName()
            ));

            foreach ($reflectionClass->getProperties() as $property) {
                $vichSerializableAnnotation = $this->annotationReader->getPropertyAnnotation($property, VichSerializableField::class);

                if ($vichSerializableAnnotation instanceof VichSerializableField) {
                    $vichUploadableFileAnnotation = $this->annotationReader->getPropertyAnnotation($property, UploadableField::class);

                    if ($vichUploadableFileAnnotation instanceof UploadableField) {
                        $exceptionMessage = \sprintf(
                            'The field "%s" in the class "%s" cannot have @UploadableField and @VichSerializableField annotations at the same moment.',
                            $property->getName(),
                            $reflectionClass->getName()
                        );

                        throw new IncompatibleUploadableAndSerializableFieldAnnotationException($exceptionMessage);
                    }
                    $this->logger->debug(\sprintf(
                        'Found @VichSerializableField annotation for the field "%s" in the class "%s"',
                        $property->getName(),
                        $reflectionClass->getName()
                    ));

                    $uri = null;
                    $property->setAccessible(true);

                    if ($property->getValue($event->getObject())) {
                        $uri = $this->storage->resolveUri($object, $vichSerializableAnnotation->getField());
                        if ($vichSerializableAnnotation->isIncludeHost() && false === \filter_var($uri, \FILTER_VALIDATE_URL)) {
                            $uri = $this->getHostUrl().$uri;
                        }
                    }
                    $this->serializedObjects[$objectUid][$property->getName()] = $property->getValue($event->getObject());
                    $property->setValue($object, $uri);
                }
            }
        }
    }

    /**
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event): void
    {
        $object = $event->getObject();
        if (!\is_object($object)) {
            return;
        }

        if ($object instanceof Proxy && !$object->__isInitialized()) {
            $object->__load();
        }

        $objectUid = \spl_object_hash($object);
        if (!\array_key_exists($objectUid, $this->serializedObjects)) {
            return;
        }

        foreach ($this->serializedObjects[$objectUid] as $propertyName => $propertyValue) {
            $this->propertyAccessor->setValue($object, $propertyName, $propertyValue);
        }
        unset($this->serializedObjects[$objectUid]);
    }

    /**
     * Get host url (scheme://host:port).
     *
     * @return string
     */
    private function getHostUrl(): string
    {
        $scheme = $this->requestContext->getScheme();
        $url = $scheme.'://'.$this->requestContext->getHost();

        $httpPort = $this->requestContext->getHttpPort();
        if ('http' === $scheme && $httpPort && self::HTTP_PORT !== $httpPort) {
            return $url.':'.$httpPort;
        }

        $httpsPort = $this->requestContext->getHttpsPort();
        if ('https' === $scheme && $httpsPort && self::HTTPS_PORT !== $httpsPort) {
            return $url.':'.$httpsPort;
        }

        return $url;
    }
}
