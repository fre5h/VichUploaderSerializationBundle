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
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\Util\ClassUtils;
use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableClass;
use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableField;
use Fresh\VichUploaderSerializationBundle\Exception\IncompatibleUploadableAndSerializableFieldAnnotationException;
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

    /** @var array */
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
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ['event' => Events::PRE_SERIALIZE, 'method' => 'onPreSerialize'],
            ['event' => Events::POST_SERIALIZE, 'method' => 'onPostSerialize'],
        ];
    }

    /**
     * @param PreSerializeEvent $event
     *
     * @throws IncompatibleUploadableAndSerializableFieldAnnotationException
     */
    public function onPreSerialize(PreSerializeEvent $event): void
    {
        $object = $event->getObject();
        if (!is_object($object)) {
            return;
        }

        if ($object instanceof Proxy && !$object->__isInitialized()) {
            $object->__load();
        }

        $objectUid = \spl_object_hash($object);
        if (\array_key_exists($objectUid, $this->serializedObjects)) {
            return;
        }

        $classAnnotation = $this->annotationReader->getClassAnnotation(
            new \ReflectionClass(ClassUtils::getClass($object)),
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
                        if ($vichSerializableAnnotation->isIncludeHost() && false === \filter_var($uri, FILTER_VALIDATE_URL)) {
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
        if (!is_object($object)) {
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
        if ('http' === $scheme && $httpPort && 80 !== $httpPort) {
            return $url.':'.$httpPort;
        }

        $httpsPort = $this->requestContext->getHttpsPort();
        if ('https' === $scheme && $httpsPort && 443 !== $httpsPort) {
            return $url.':'.$httpsPort;
        }

        return $url;
    }
}
