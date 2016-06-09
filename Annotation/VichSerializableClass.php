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
 * VichSerializableClass Annotation Class.
 *
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 *
 * @author Artem Genvald <genvaldartem@gmail.com>
 */
final class VichSerializableClass implements Annotation
{
}
