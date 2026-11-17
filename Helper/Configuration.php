<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Helper;

use Smile\ElasticsuiteCore\Helper\AbstractConfiguration;

/**
 * Smile_ElasticsuiteRetailer search engine configuration default implementation.
 */
class Configuration extends AbstractConfiguration
{
    /**
     * Location of Elasticsuite retailer settings configuration.
     */
    public const CONFIG_XML_PREFIX = 'smile_elasticsuite_retailer/retailer_settings';

    /**
     * Location of Elasticsuite retailer field indexed configuration.
     */
    public const FIELD_INDEXED_XML_PREFIX = 'smile_elasticsuite_retailer/retailer_field_index';

    /**
     * Retrieve a configuration value by its key
     *
     * @param string $key The configuration key
     */
    public function getConfigValue(string $key): mixed
    {
        return $this->scopeConfig->getValue(self::CONFIG_XML_PREFIX . "/" . $key);
    }

    /**
     * Retrieve if field is indexed
     *
     * @param string $field The field
     */
    public function isIndexed(string $field): mixed
    {
        return $this->scopeConfig->getValue(self::FIELD_INDEXED_XML_PREFIX . "/" . $field);
    }
}
