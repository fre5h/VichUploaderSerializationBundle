<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Henvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\EventListener;

use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\Util\ClassUtils;
use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableClass;
use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableField;
use Fresh\VichUploaderSerializationBundle\Exception\IncompatibleUploadableAndSerializableFieldAnnotationException;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Monolog\Logger;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RequestContext;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * JmsPreSerializeListener Class.
 *
 * @author Artem Henvald <genvaldartem@gmail.com>
 */
class JmsSerializerSubscriber implements EventSubscriberInterface
{
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

    /** @var array */
    private $serializedObjects = [];

    /**
     * @param StorageInterface $storage
     * @param RequestContext   $requestContext
     * @param CachedReader     $annotationReader
     * @param PropertyAccessor $propertyAccessor
     * @param Logger           $logger
     */
    public function __construct(StorageInterface $storage, RequestContext $requestContext, CachedReader $annotationReader, PropertyAccessor $propertyAccessor, Logger $logger)
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
    public static function getSubscribedEvents()
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
    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getObject();

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
                        throw new IncompatibleUploadableAndSerializableFieldAnnotationException(\sprintf(
                            'The field "%s" in the class "%s" cannot have @UploadableField and @VichSerializableField annotations at the same moment.',
                            $property->getName(),
                            $reflectionClass->getName()
                        ));
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
                        if ($vichSerializableAnnotation->isIncludeHost()) {
                            $uri = $this->getHostUrl().$uri;
                        }
                    }
                    $property->setValue($object, $uri);
                    $this->serializedObjects[$objectUid][$property->getName()] = $property->getValue($event->getObject());
                }
            }
        }
    }

    /**
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();

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
    }

    /**
     * Get host url (scheme://host:port).
     *
     * @return string
     */
    private function getHostUrl()
    {
        $scheme = $this->requestContext->getScheme();
        $hostPort = $this->requestContext->getHttpPort();

        $url = $scheme.'://'.$this->requestContext->getHost();

        if ('http' === $scheme && $hostPort && 80 !== $hostPort) {
            $url .= ':'.$hostPort;
        } elseif ('https' === $scheme && $hostPort && 443 !== $hostPort) {
            $url .= ':'.$hostPort;
        }

        return $url;
    }
}
