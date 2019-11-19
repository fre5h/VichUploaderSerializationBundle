# VichUploaderSerializationBundle

Provides integration between [VichUploaderBundle](https://github.com/dustin10/VichUploaderBundle "VichUploaderBundle") and
[JMSSerializerBundle](https://github.com/dustin10/VichUploaderBundle "JMSSerializerBundle").
Allows to generate full or relative URIs to entity fields mapped with `@Vich` and `@JMS` annotations during the serialization.

[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/fre5h/VichUploaderSerializationBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/fre5h/VichUploaderSerializationBundle/)
[![Build Status](https://img.shields.io/travis/fre5h/VichUploaderSerializationBundle.svg?style=flat-square)](https://travis-ci.org/fre5h/VichUploaderSerializationBundle)
[![CodeCov](https://img.shields.io/codecov/c/github/fre5h/VichUploaderSerializationBundle.svg?style=flat-square)](https://codecov.io/github/fre5h/VichUploaderSerializationBundle)
[![License](https://img.shields.io/packagist/l/fresh/vich-uploader-serialization-bundle.svg?style=flat-square)](https://packagist.org/packages/fresh/vich-uploader-serialization-bundle)
[![Latest Stable Version](https://img.shields.io/packagist/v/fresh/vich-uploader-serialization-bundle.svg?style=flat-square)](https://packagist.org/packages/fresh/vich-uploader-serialization-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/fresh/vich-uploader-serialization-bundle.svg?style=flat-square)](https://packagist.org/packages/fresh/vich-uploader-serialization-bundle)
[![StyleCI](https://styleci.io/repos/47037751/shield?style=flat-square)](https://styleci.io/repos/47037751)
[![Gitter](https://img.shields.io/badge/gitter-join%20chat-brightgreen.svg?style=flat-square)](https://gitter.im/fre5h/VichUploaderSerializationBundle)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a40e1ac6-3b2b-4405-b7c5-53d020a5cf93/small.png)](https://insight.sensiolabs.com/projects/a40e1ac6-3b2b-4405-b7c5-53d020a5cf93)
[![knpbundles.com](http://knpbundles.com/fre5h/VichUploaderSerializationBundle/badge-short)](http://knpbundles.com/fre5h/VichUploaderSerializationBundle)

## Requirements

* PHP 7.1 and *later*
* Symfony 3.4, 4.3 and *later*

## Installation

```composer req fresh/vich-uploader-serialization-bundle='~2.4'```

## Usage

Add the next class to the `use` section of your entity class.

```php
use Fresh\VichUploaderSerializationBundle\Annotation as Fresh;
```

Bundle provides two annotations which allow the serialization of `@Vich\UploadableField` fields in your entities.
At first you have to add `@Fresh\VichSerializableClass` to the entity class which has uploadable fields.
Then you have to add `@Fresh\VichSerializableField` annotation to the uploadable field you want to serialize.

Annotation `@Fresh\VichSerializableClass` does not have any option.  
Annotation `@Fresh\VichSerializableField` has one required option *value* (or *field*) which value should link to the field with `@Vich\UploadableField` annotation.
It can be set like this `@Fresh\VichSerializableField("photoFile")` or `@Fresh\VichSerializableField(field="photoFile")`.
Also there is another option `includeHost`, it is not required and by default is set to **true**.
But if you need, you can exclude the host from the generated URI, just use the next variant of the annotation `@Fresh\VichSerializableField("photoFile", includeHost=false)`.

And also don't forget that to serialize Vich uploadable fields they also should be marked with `@JMS` annotations to be serialized.

The generated URI by default:

```json
{
  "photo": "http://example.com/uploads/users/photos/5659828fa80a7.jpg",
  "cover": "http://example.com/uploads/users/covers/456428fa8g4a8.jpg",
}
```

The generated URI with `includeHost` set to `false`:

```json
{
  "photo": "/uploads/users/photos/5659828fa80a7.jpg",
  "cover": "/uploads/users/covers/456428fa8g4a8.jpg",
}
```

### Example of entity with serialized uploadable fields

```php
<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fresh\VichUploaderSerializationBundle\Annotation as Fresh;
use JMS\Serializer\Annotation as JMS;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity()
 *
 * @Vich\Uploadable
 * 
 * @Fresh\VichSerializableClass
 */
class User
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\SerializedName("photo")
     *
     * @Fresh\VichSerializableField("photoFile")
     */
    private $photoName;

    /**
     * @var File
     *
     * @JMS\Exclude
     *
     * @Vich\UploadableField(mapping="user_photo_mapping", fileNameProperty="photoName")
     */
    private $photoFile;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\SerializedName("cover")
     *
     * @Fresh\VichSerializableField("coverFile", includeHost=false)
     */
    private $coverName;

    /**
     * @var File
     *
     * @JMS\Exclude
     *
     * @Vich\UploadableField(mapping="user_cover_mapping", fileNameProperty="coverName")
     */
    private $coverFile;    
}
```

### Don't make a mistake!

Additional example of a **wrong usage** of provided annotations to attract your attention.
Use `@Fresh\VichSerializableField` **only with** the field which is mapped with `@ORM\Column`,
because this field is mapped to a database and keeps the name of the stored file.
Don't use the `@Fresh\VichSerializableField` with a field which also mapped with `@Vich\UploadableField`,
this is a wrong use case and will throw an exception!

So the next example is the **incorrect** usage of provided annotations! Don't do so!

```php
<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fresh\VichUploaderSerializationBundle\Annotation as Fresh;
use JMS\Serializer\Annotation as JMS;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity()
 *
 * @Vich\Uploadable
 * 
 * @Fresh\VichSerializableClass
 */
class User
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $photoName;

    /**
     * @var File
     *
     * !!! Next three annotations should be moved to the `photoName` property
     * @JMS\Expose
     * @JMS\SerializedName("photo")
     * @Fresh\VichSerializableField("photoFile")
     *
     * !!! This annotation should be left here
     * @Vich\UploadableField(mapping="user_photo_mapping", fileNameProperty="photoName")
     */
    private $photoFile;  
}
```

***

## Contributing

See [CONTRIBUTING](https://github.com/fre5h/VichUploaderSerializationBundle/blob/master/.github/CONTRIBUTING.md) file.
