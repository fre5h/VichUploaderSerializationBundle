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
use Fresh\VichUploaderSerializationBundle\Exception\InvalidArgumentException;
use Fresh\VichUploaderSerializationBundle\Exception\LogicException;
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

    public function testAnnotationWithoutOptions(): void
    {
        $this->expectException(LogicException::class);

        new VichSerializableField([]);
    }

    public function testAnnotationWithoutIncludeHostOption(): void
    {
        $this->expectException(LogicException::class);

        new VichSerializableField(['includeHost' => false]);
    }

    public function testWrongTypeForValueOption(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new VichSerializableField(['value' => 123]);
    }

    public function testWrongTypeForFieldOption(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new VichSerializableField(['field' => 123]);
    }

    public function testWrongTypeForFieldIncludeHost(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new VichSerializableField(['value' => 'photoFile', 'includeHost' => 123]);
    }
}
