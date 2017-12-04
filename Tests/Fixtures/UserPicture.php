<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Henvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\Tests\Fixtures;

use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\Mapping as ORM;
use Fresh\VichUploaderSerializationBundle\Annotation as Fresh;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * UserPictures Entity.
 *
 * @ORM\Table(name="userPictures")
 * @ORM\Entity()
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
     * @var UserA
     *
     * @ORM\ManyToOne(targetEntity="Fresh\VichUploaderSerializationBundle\Tests\Fixtures\UserA", inversedBy="pictures")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

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
    public function __load()
    {
        $this->setPhotoName('photo.jpg')
             ->setCoverName('cover.jpg');
        $this->status = true;
    }

    /**
     * {@inheritdoc}
     */
    public function __isInitialized()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'New User Picture';
    }

    /**
     * @param UserA $user
     *
     * @return $this
     */
    public function setUser(UserA $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return UserA
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPhotoName()
    {
        return $this->photoName;
    }

    /**
     * @param string $photoName
     *
     * @return $this
     */
    public function setPhotoName($photoName)
    {
        $this->photoName = $photoName;

        return $this;
    }

    /**
     * @return File
     */
    public function getPhotoFile()
    {
        return $this->photoFile;
    }

    /**
     * @param File $photoFile
     *
     * @return $this
     */
    public function setPhotoFile(File $photoFile)
    {
        $this->photoFile = $photoFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getCoverName()
    {
        return $this->coverName;
    }

    /**
     * @param string $coverName
     *
     * @return $this
     */
    public function setCoverName($coverName)
    {
        $this->coverName = $coverName;

        return $this;
    }

    /**
     * @return File
     */
    public function getCoverFile()
    {
        return $this->coverFile;
    }

    /**
     * @param File $coverFile
     *
     * @return $this
     */
    public function setCoverFile(File $coverFile)
    {
        $this->coverFile = $coverFile;

        return $this;
    }

    /**
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
