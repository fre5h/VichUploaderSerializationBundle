<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Genvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\Annotation;

use Doctrine\ORM\Mapping\Annotation;

/**
 * VichSerializableField Annotation Class.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 *
 * @author Artem Genvald <genvaldartem@gmail.com>
 */
final class VichSerializableField implements Annotation
{
    /**
     * @var string $field Field
     */
    private $field;

    /**
     * @var bool $includeHost Include host
     */
    private $includeHost = true;

    /**
     * Constructor
     *
     * @param array $options Options
     *
     * @throws \Exception
     */
    public function __construct(array $options)
    {
        if (!isset($options['value']) && !isset($options['field'])) {
            throw new \LogicException(sprintf('Either "value" or "field" option must be set.'));
        }

        if (isset($options['value'])) {
            if (!is_string($options['value'])) {
                throw new \InvalidArgumentException(sprintf('Option "value" must be a string.'));
            }
            $this->setField($options['value']);
        } elseif (isset($options['field'])) {
            if (!is_string($options['field'])) {
                throw new \InvalidArgumentException(sprintf('Option "field" must be a string.'));
            }
            $this->setField($options['field']);
        }

        if (isset($options['includeHost'])) {
            if (!is_bool($options['includeHost'])) {
                throw new \InvalidArgumentException(sprintf('Option "includeHost" must be a boolean.'));
            }
            $this->setIncludeHost($options['includeHost']);
        }
    }

    /**
     * Get field
     *
     * @return string Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set field
     *
     * @param string $field Field
     *
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get include host
     *
     * @return bool Include host
     */
    public function isIncludeHost()
    {
        return $this->includeHost;
    }

    /**
     * Set include host
     *
     * @param bool $includeHost Include host
     *
     * @return $this
     */
    public function setIncludeHost($includeHost)
    {
        $this->includeHost = $includeHost;

        return $this;
    }
}
