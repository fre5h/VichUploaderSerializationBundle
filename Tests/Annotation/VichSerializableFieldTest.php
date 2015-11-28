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
 * VichSerializableFieldTest
 *
 * @author Artem Genvald <genvaldartem@gmail.com>
 */
class VichSerializableFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test annotation with `value` option
     */
    public function testValueOption()
    {
        $annotation = new VichSerializableField(['value' => 'photoFile']);

        $this->assertEquals('photoFile', $annotation->getField());
        $this->assertTrue($annotation->isIncludeHost());
    }

    /**
     * Test annotation with `field` option
     */
    public function testFieldOption()
    {
        $annotation = new VichSerializableField(['field' => 'photoFile']);

        $this->assertEquals('photoFile', $annotation->getField());
        $this->assertTrue($annotation->isIncludeHost());
    }

    /**
     * Test annotation with `value` and `includeHost` options
     */
    public function testValueAndIncludeHostOptions()
    {
        $annotation = new VichSerializableField(['value' => 'photoFile', 'includeHost' => false]);

        $this->assertEquals('photoFile', $annotation->getField());
        $this->assertFalse($annotation->isIncludeHost());
    }

    /**
     * Test annotation without any option
     *
     * @expectedException \LogicException
     */
    public function testAnnotationWithoutOptions()
    {
        new VichSerializableField([]);
    }

    /**
     * Test annotation without `value` or `field` options
     *
     * @expectedException \LogicException
     */
    public function testAnnotationWithoutIncludeHostOption()
    {
        new VichSerializableField(['includeHost' => false]);
    }

    /**
     * Test annotation with wrong type for `value` option
     *
     * @expectedException \InvalidArgumentException
     */
    public function testWrongTypeForValueOption()
    {
        new VichSerializableField(['value' => 123]);
    }

    /**
     * Test annotation with wrong type for `field` option
     *
     * @expectedException \InvalidArgumentException
     */
    public function testWrongTypeForFieldOption()
    {
        new VichSerializableField(['field' => 123]);
    }

    /**
     * Test annotation with wrong type for `includeHost` option
     *
     * @expectedException \InvalidArgumentException
     */
    public function testWrongTypeForFieldIncludeHost()
    {
        new VichSerializableField(['value' => 'photoFile', 'includeHost' => 123]);
    }
}
