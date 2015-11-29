<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Genvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\EventListener;

use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Util\ClassUtils;
use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableField;
use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableClass;
use Fresh\VichUploaderSerializationBundle\Exception\IncompatibleUploadableAndSerializableFieldAnnotationException;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Monolog\Logger;
use Symfony\Component\Routing\RequestContext;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Class JmsPreSerializeListener
 *
 * @author Artem Genvald <genvaldartem@gmail.com>
 */
class JmsPreSerializeListener
{
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
     * Constructor
     *
     * @param StorageInterface $storage          Vich storage
     * @param RequestContext   $requestContext   Request context
     * @param CachedReader     $annotationReader Cached annotation reader
     * @param Logger           $logger           Logger
     */
    public function __construct(
        StorageInterface $storage,
        RequestContext $requestContext,
        CachedReader $annotationReader,
        Logger $logger
    ) {
        $this->storage          = $storage;
        $this->requestContext   = $requestContext;
        $this->annotationReader = $annotationReader;
        $this->logger           = $logger;
    }

    /**
     * On pre serialize
     *
     * @param ObjectEvent $event Event
     */
    public function onPreSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();

        $classAnnotation = $this->annotationReader->getClassAnnotation(
            new \ReflectionClass(ClassUtils::getClass($object)),
            VichSerializableClass::class
        );

        if ($classAnnotation instanceof VichSerializableClass) {
            $reflectionClass = ClassUtils::newReflectionClass($object);
            $this->logger->debug(sprintf('Found @VichSerializableClass annotation for the class "%s"', $reflectionClass->getName()));

            foreach ($reflectionClass->getProperties() as $property) {
                $vichSerializableAnnotation = $this->annotationReader->getPropertyAnnotation($property, VichSerializableField::class);

                if ($vichSerializableAnnotation instanceof VichSerializableField) {
                    $vichUploadableFileAnnotation = $this->annotationReader->getPropertyAnnotation($property, UploadableField::class);

                    if ($vichUploadableFileAnnotation instanceof UploadableField) {
                        throw new IncompatibleUploadableAndSerializableFieldAnnotationException(sprintf(
                            'The field "%s" in the class "%s" cannot have @UploadableField and @VichSerializableField annotations at the same moment.',
                            $property->getName(),
                            $reflectionClass->getName()
                        ));
                    }
                    $this->logger->debug(sprintf(
                        'Found @VichSerializableField annotation for the field "%s" in the class "%s"',
                        $property->getName(),
                        $reflectionClass->getName()
                    ));

                    $uri = null;
                    $property->setAccessible(true);

                    if ($property->getValue($event->getObject())) {
                        $uri = $this->storage->resolveUri($object, $vichSerializableAnnotation->getField());
                        if ($vichSerializableAnnotation->isIncludeHost()) {
                            $uri = $this->getSchemeAndHost().$uri;
                        }
                    }
                    $property->setValue($object, $uri);
                }
            }
        }
    }

    /**
     * Get scheme and host
     *
     * @return string Scheme and host
     */
    private function getSchemeAndHost()
    {
        return $this->requestContext->getScheme().'://'.$this->requestContext->getHost();
    }
}