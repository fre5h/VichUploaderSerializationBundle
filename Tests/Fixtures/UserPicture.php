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

namespace Fresh\VichUploaderSerializationBundle\Tests\Fixtures;

use Doctrine\Persistence\Proxy;
use Fresh\VichUploaderSerializationBundle\Annotation as Fresh;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * UserPictures Entity.
 *
 * @JMS\ExclusionPolicy("all")
 *
 * @Vich\Uploadable
 *
 * @Fresh\VichSerializableClass
 */
class UserPicture implements Proxy
{
    /**
     * @var string
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
     * @JMS\Expose
     * @JMS\SerializedName("cover")
     *
     * @Fresh\VichSerializableField("coverFile")
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

    /** @var bool */
    private $status = false;

    /**
     * @inheritdoc
     */
    public function __load(): void
    {
        $this->setPhotoName('photo.jpg')
             ->setCoverName('cover.jpg');
        $this->status = true;
    }

    /**
     * {@inheritdoc}
     */
    public function __isInitialized(): bool
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getPhotoName(): ?string
    {
        return $this->photoName;
    }

    /**
     * @param string $photoName
     *
     * @return $this
     */
    public function setPhotoName(?string $photoName): self
    {
        $this->photoName = $photoName;

        return $this;
    }

    /**
     * @return File
     */
    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    /**
     * @param File $photoFile
     *
     * @return $this
     */
    public function setPhotoFile(File $photoFile): self
    {
        $this->photoFile = $photoFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getCoverName(): ?string
    {
        return $this->coverName;
    }

    /**
     * @param string $coverName
     *
     * @return $this
     */
    public function setCoverName(?string $coverName): self
    {
        $this->coverName = $coverName;

        return $this;
    }

    /**
     * @return File
     */
    public function getCoverFile(): ?File
    {
        return $this->coverFile;
    }

    /**
     * @param File $coverFile
     *
     * @return $this
     */
    public function setCoverFile(File $coverFile): self
    {
        $this->coverFile = $coverFile;

        return $this;
    }

    /**
     * @param bool $status
     *
     * @return $this
     */
    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }
}
