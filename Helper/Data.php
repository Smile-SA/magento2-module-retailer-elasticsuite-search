<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Elasticsuite Retailer helper.
 */
class Data extends AbstractHelper
{
    /**
     * Retrieve suggest url
     */
    public function getSuggestUrl(): string
    {
        return $this->_getUrl(
            'elasticsuite_retailer/ajax/suggest',
            ['_secure' => $this->_getRequest()->isSecure()]
        );
    }
}
