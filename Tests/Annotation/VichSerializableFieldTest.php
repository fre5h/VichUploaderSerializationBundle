<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Genvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\Tests\Annotation;

use Fresh\VichUploaderSerializationBundle\Annotation\VichSerializableField;

/**
 * VichSerializableFieldTest.
 *
 * @author Artem Genvald <genvaldartem@gmail.com>
 */
class VichSerializableFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testValueOption()
    {
        $annotation = new VichSerializableField(['value' => 'photoFile']);

        $this->assertEquals('photoFile', $annotation->getField());
        $this->assertTrue($annotation->isIncludeHost());
    }

    public function testFieldOption()
    {
        $annotation = new VichSerializableField(['field' => 'photoFile']);

        $this->assertEquals('photoFile', $annotation->getField());
        $this->assertTrue($annotation->isIncludeHost());
    }

    public function testValueAndIncludeHostOptions()
    {
        $annotation = new VichSerializableField(['value' => 'photoFile', 'includeHost' => false]);

        $this->assertEquals('photoFile', $annotation->getField());
        $this->assertFalse($annotation->isIncludeHost());
    }

    public function testAnnotationWithoutOptions()
    {
        new VichSerializableField([]);
    }

    /**
     * @expectedException \LogicException
     */
    public function testAnnotationWithoutIncludeHostOption()
    {
        new VichSerializableField(['includeHost' => false]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWrongTypeForValueOption()
    {
        new VichSerializableField(['value' => 123]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWrongTypeForFieldOption()
    {
        new VichSerializableField(['field' => 123]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWrongTypeForFieldIncludeHost()
    {
        new VichSerializableField(['value' => 'photoFile', 'includeHost' => 123]);
    }
}
