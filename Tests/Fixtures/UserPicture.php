<?php
/*
 * This file is part of the FreshVichUploaderSerializationBundle
 *
 * (c) Artem Genvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\VichUploaderSerializationBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Fresh\VichUploaderSerializationBundle\Annotation as Fresh;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\Common\Persistence\Proxy;

/**
 * UserPictures Entity.
 *
 * @ORM\Table(name="userPictures")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 *
 * @Vich\Uploadable
 * @Fresh\VichSerializableClass
 */
class UserPicture implements Proxy
{
    /**
     * @var UserA $user User
     *
     * @ORM\ManyToOne(targetEntity="Fresh\VichUploaderSerializationBundle\Tests\Fixtures\UserA", inversedBy="pictures")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string $photoName Photo name
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
     * @var File $photoFile Photo file
     *
     * @JMS\Exclude
     *
     * @Vich\UploadableField(mapping="user_photo_mapping", fileNameProperty="photoName")
     */
    private $photoFile;

    /**
     * @var string $coverName Cover name
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
     * @var File $coverFile Cover file
     *
     * @JMS\Exclude
     *
     * @Vich\UploadableField(mapping="user_cover_mapping", fileNameProperty="coverName")
     */
    private $coverFile;

    /**
     * @var bool $status Status
     */
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
     * To string.
     *
     * @return string
     */
    public function __toString()
    {
        $result = 'New User Picture';

        return $result;
    }

    /**
     * Set user.
     *
     * @param UserA $user User
     *
     * @return $this
     */
    public function setUser(UserA $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return UserA User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get photo name.
     *
     * @return string Photo name
     */
    public function getPhotoName()
    {
        return $this->photoName;
    }

    /**
     * Set photo name.
     *
     * @param string $photoName Photo name
     *
     * @return $this
     */
    public function setPhotoName($photoName)
    {
        $this->photoName = $photoName;

        return $this;
    }

    /**
     * Get photo file.
     *
     * @return File Photo file
     */
    public function getPhotoFile()
    {
        return $this->photoFile;
    }

    /**
     * Set photo file.
     *
     * @param File $photoFile Photo file
     *
     * @return $this
     */
    public function setPhotoFile(File $photoFile)
    {
        $this->photoFile = $photoFile;

        return $this;
    }

    /**
     * Get cover name.
     *
     * @return string Cover name
     */
    public function getCoverName()
    {
        return $this->coverName;
    }

    /**
     * Set cover name.
     *
     * @param string $coverName Cover name
     *
     * @return $this
     */
    public function setCoverName($coverName)
    {
        $this->coverName = $coverName;

        return $this;
    }

    /**
     * Get cover file.
     *
     * @return File Cover file
     */
    public function getCoverFile()
    {
        return $this->coverFile;
    }

    /**
     * Set cover file.
     *
     * @param File $coverFile Cover file
     *
     * @return $this
     */
    public function setCoverFile(File $coverFile)
    {
        $this->coverFile = $coverFile;

        return $this;
    }
}
