<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteRetailer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteRetailer\Helper;

use Smile\Retailer\Api\Data\RetailerInterface;
use Smile\StoreLocator\Model\Url;

/**
 * Elasticsuite Retailer helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteRetailer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Smile\StoreLocator\Model\Url
     */
    private $urlModel;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context  Helper context.
     * @param \Smile\StoreLocator\Model\Url         $urlModel Retailer URL model.
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Smile\StoreLocator\Model\Url $urlModel
    ) {
        parent::__construct($context);
        $this->urlModel = $urlModel;
    }

    /**
     * Retrieve suggest url
     *
     * @return string
     */
    public function getSuggestUrl()
    {
        return $this->_getUrl(
            'elasticsuite_retailer/ajax/suggest',
            ['_secure' => $this->_getRequest()->isSecure()]
        );
    }
}
