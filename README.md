# VichUploaderSerializationBundle

Provides integration between [VichUploaderBundle](https://github.com/dustin10/VichUploaderBundle "VichUploaderBundle") and
[JMSSerializerBundle](https://github.com/dustin10/VichUploaderBundle "JMSSerializerBundle").
Allows to generate full or related URI to the file during the serialization.

[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/fre5h/VichUploaderSerializationBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/fre5h/VichUploaderSerializationBundle/)
[![Build Status](https://img.shields.io/travis/fre5h/VichUploaderSerializationBundle.svg?style=flat-square)](https://travis-ci.org/fre5h/VichUploaderSerializationBundle)
[![CodeCov](https://img.shields.io/codecov/c/github/fre5h/VichUploaderSerializationBundle.svg?style=flat-square)](https://codecov.io/github/fre5h/VichUploaderSerializationBundle)
[![License](https://img.shields.io/packagist/l/fresh/vich-uploader-serialization-bundle.svg?style=flat-square)](https://packagist.org/packages/fresh/vich-uploader-serialization-bundle)
[![Latest Stable Version](https://img.shields.io/packagist/v/fresh/vich-uploader-serialization-bundle.svg?style=flat-square)](https://packagist.org/packages/fresh/vich-uploader-serialization-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/fresh/vich-uploader-serialization-bundle.svg?style=flat-square)](https://packagist.org/packages/fresh/vich-uploader-serialization-bundle)
[![Dependency Status](https://img.shields.io/versioneye/d/php/fresh:vich-uploader-serialization-bundle.svg?style=flat-square)](https://www.versioneye.com/user/projects/565a0f4b036c32003d000008)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/a40e1ac6-3b2b-4405-b7c5-53d020a5cf93.svg?style=flat-square)](https://insight.sensiolabs.com/projects/a40e1ac6-3b2b-4405-b7c5-53d020a5cf93)
[![Gitter](https://img.shields.io/badge/gitter-join%20chat-brightgreen.svg?style=flat-square)](https://gitter.im/fre5h/VichUploaderSerializationBundle?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![knpbundles.com](http://knpbundles.com/fre5h/VichUploaderSerializationBundle/badge-short)](http://knpbundles.com/fre5h/VichUploaderSerializationBundle)

## Installation

```php composer.phar require fresh/vich-uploader-serialization-bundle='dev-master'```

### Register the bundle

To start using the bundle, register it in `app/AppKernel.php`:

```php
public function registerBundles()
{
    $bundles = [
        // Other bundles...
        new Fresh\VichUploaderSerializationBundle\FreshVichUploaderSerializationBundle(),
    ];
}
```

## Using

### Example

```php
<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fresh\VichUploaderSerializationBundle\Annotation as Fresh;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * User Entity
 *
 * @ORM\Table(name="candidates")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CandidateRepository")
 *
 * @Vich\Uploadable
 * @Fresh\VichSerializableClass
 */
class User
{
    /**
     * @var string $photoName Photo name
     *
     * @ORM\Column(type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\SerializedName("photo")
     *
     * @Fresh\VichSerializableField("photoFile", includeHost=true)
     */
    private $photoName;

    /**
     * @var File $photoFile Photo file
     *
     * @JMS\Exclude
     *
     * @Vich\UploadableField(mapping="user_photo_mapping", fileNameProperty="photoName")
     */
    private $photoFile;
}
```
