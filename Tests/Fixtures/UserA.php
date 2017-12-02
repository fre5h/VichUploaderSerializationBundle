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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Fresh\VichUploaderSerializationBundle\Annotation as Fresh;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * UserA Entity.
 *
 * @ORM\Table(name="users")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 *
 * @Vich\Uploadable
 *
 * @Fresh\VichSerializableClass
 */
class UserA
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

    /**
     * @var UserPicture[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Fresh\VichUploaderSerializationBundle\Tests\Fixtures\UserPictures", mappedBy="user")
     */
    protected $userPictures;

    /**
     * @return string
     */
    public function __toString()
    {
        return 'New User';
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
     * @param UserPicture $userPicture
     *
     * @return $this
     */
    public function addUserPictures(UserPicture $userPicture)
    {
        $this->userPictures[] = $userPicture;

        return $this;
    }

    /**
     * @param UserPicture $userPictures
     */
    public function removeUserPictures(UserPicture $userPictures)
    {
        $this->userPictures->removeElement($userPictures);
    }

    /**
     * @return UserPicture[]|ArrayCollection
     */
    public function getUserPictures()
    {
        return $this->userPictures;
    }
}
