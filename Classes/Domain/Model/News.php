<?php
namespace NitsanAi\MyBlog\Domain\Model;

class News extends \GeorgRinger\News\Domain\Model\News 
{
    /**
     * @var string
     */
    protected $subtitle = '';

    /**
     * @var string
     */
    protected $descriptionNews = '';

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    protected $featureImage;

    /**
     * Returns the subtitle
     *
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Sets the subtitle
     *
     * @param string $subtitle
     * @return void
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Returns the descriptionNews
     *
     * @return string
     */
    public function getDescriptionNews()
    {
        return $this->descriptionNews;
    }

    /**
     * Sets the descriptionNews
     *
     * @param string $descriptionNews
     * @return void
     */
    public function setDescriptionNews($descriptionNews)
    {
        $this->descriptionNews = $descriptionNews;
    }

    /**
     * Returns the featureImage
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    public function getFeatureImage()
    {
        return $this->featureImage;
    }

    /**
     * Sets the featureImage
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $featureImage
     * @return void
     */
    public function setFeatureImage(\TYPO3\CMS\Extbase\Domain\Model\FileReference $featureImage)
    {
        $this->featureImage = $featureImage;
    }
}
