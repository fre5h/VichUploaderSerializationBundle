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

namespace Fresh\VichUploaderSerializationBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * VichSerializableField Annotation Class.
 *
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD"})
 *
 * @author Artem Henvald <genvaldartem@gmail.com>
 */
final class VichSerializableField
{
    /** @var string */
    private $field;

    /** @var bool */
    private $includeHost = true;

    /**
     * @param array $options
     *
     * @throws \Exception
     */
    public function __construct(array $options)
    {
        if (!isset($options['value']) && !isset($options['field'])) {
            throw new \LogicException(\sprintf('Either "value" or "field" option must be set.'));
        }

        if (isset($options['value'])) {
            if (!\is_string($options['value'])) {
                throw new \InvalidArgumentException(\sprintf('Option "value" must be a string.'));
            }
            $this->setField($options['value']);
        } elseif (isset($options['field'])) {
            if (!\is_string($options['field'])) {
                throw new \InvalidArgumentException(\sprintf('Option "field" must be a string.'));
            }
            $this->setField($options['field']);
        }

        if (isset($options['includeHost'])) {
            if (!\is_bool($options['includeHost'])) {
                throw new \InvalidArgumentException(\sprintf('Option "includeHost" must be a boolean.'));
            }
            $this->setIncludeHost($options['includeHost']);
        }
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     *
     * @return $this
     */
    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIncludeHost(): bool
    {
        return $this->includeHost;
    }

    /**
     * @param bool $includeHost
     *
     * @return $this
     */
    public function setIncludeHost(bool $includeHost): self
    {
        $this->includeHost = $includeHost;

        return $this;
    }
}
