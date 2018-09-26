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

namespace Fresh\VichUploaderSerializationBundle\Tests\Annotation;

use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableField;
use PHPUnit\Framework\TestCase;

/**
 * VichSerializableFieldTest.
 *
 * @author Artem Henvald <genvaldartem@gmail.com>
 */
class VichSerializableFieldTest extends TestCase
{
    public function testValueOption(): void
    {
        $annotation = new VichSerializableField(['value' => 'photoFile']);

        self::assertEquals('photoFile', $annotation->getField());
        self::assertTrue($annotation->isIncludeHost());
    }

    public function testFieldOption(): void
    {
        $annotation = new VichSerializableField(['field' => 'photoFile']);

        self::assertEquals('photoFile', $annotation->getField());
        self::assertTrue($annotation->isIncludeHost());
    }

    public function testValueAndIncludeHostOptions(): void
    {
        $annotation = new VichSerializableField(['value' => 'photoFile', 'includeHost' => false]);

        self::assertEquals('photoFile', $annotation->getField());
        self::assertFalse($annotation->isIncludeHost());
    }

    /**
     * @expectedException \LogicException
     */
    public function testAnnotationWithoutOptions(): void
    {
        new VichSerializableField([]);
    }

    /**
     * @expectedException \LogicException
     */
    public function testAnnotationWithoutIncludeHostOption(): void
    {
        new VichSerializableField(['includeHost' => false]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWrongTypeForValueOption(): void
    {
        new VichSerializableField(['value' => 123]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWrongTypeForFieldOption(): void
    {
        new VichSerializableField(['field' => 123]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWrongTypeForFieldIncludeHost(): void
    {
        new VichSerializableField(['value' => 'photoFile', 'includeHost' => 123]);
    }
}
