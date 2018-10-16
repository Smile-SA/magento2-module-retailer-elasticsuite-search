<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteRetailer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteRetailer\Helper;

use Smile\ElasticsuiteCore\Helper\AbstractConfiguration;

/**
 * Smile_ElasticsuiteRetailer search engine configuration default implementation.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteRetailer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 */
class Configuration extends AbstractConfiguration
{
    /**
     * Location of Elasticsuite retailer settings configuration.
     *
     * @var string
     */
    const CONFIG_XML_PREFIX = 'smile_elasticsuite_retailer/retailer_settings';

    /**
     * Retrieve a configuration value by its key
     *
     * @param string $key The configuration key
     *
     * @return mixed
     */
    public function getConfigValue($key)
    {
        return $this->scopeConfig->getValue(self::CONFIG_XML_PREFIX . "/" . $key);
    }
}
