<?php
/**
 * DISCLAIMER
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
namespace Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;
use Smile\ElasticsuiteCore\Search\RequestInterface;

/**
 * Search engine product collection.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteRetailer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 */
class Collection extends \Smile\Retailer\Model\ResourceModel\Retailer\Collection
{
    /**
     * @var QueryResponse
     */
    private $queryResponse;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Builder
     */
    private $requestBuilder;

    /**
     * @var \Magento\Search\Model\SearchEngine
     */
    private $searchEngine;

    /**
     * @var string
     */
    private $queryText;

    /**
     * @var string
     */
    private $searchRequestName;

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var array
     */
    private $facets = [];

    /**
     * @var integer
     */
    private $storeId;

    /**
     * Collection constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) Parent construct already has 11 arguments.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory             $entityFactory     Entity Factory
     * @param \Psr\Log\LoggerInterface                                     $logger            Logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy     Fetch Strategy
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager      Event Manager
     * @param \Magento\Eav\Model\Config                                    $eavConfig         EAV Config
     * @param \Magento\Framework\App\ResourceConnection                    $resource          Resource Connection
     * @param \Magento\Eav\Model\EntityFactory                             $eavEntityFactory  EAV Entity Factory
     * @param \Magento\Eav\Model\ResourceModel\Helper                      $resourceHelper    Resource Helper
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager      The Store Manager
     * @param \Magento\Framework\Validator\UniversalFactory                $universalFactory  Universal Factory
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder               $requestBuilder    Search request
     *                                                                                        builder.
     * @param \Magento\Search\Model\SearchEngine                           $searchEngine      Search engine
     * @param string                                                       $searchRequestName Search request
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null          $connection        Database Connection
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Smile\ElasticsuiteCore\Search\Request\Builder $requestBuilder,
        \Magento\Search\Model\SearchEngine $searchEngine,
        $searchRequestName = 'retailer_search_container',
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $storeManager,
            $universalFactory,
            $connection
        );

        $this->requestBuilder    = $requestBuilder;
        $this->searchEngine      = $searchEngine;
        $this->searchRequestName = $searchRequestName;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize()
    {
        if ($this->_totalRecords === null) {
            $this->loadRetailersCounts();
        }

        return $this->_totalRecords;
    }

    /**
     * {@inheritDoc}
     */
    public function setOrder($attribute, $dir = \Zend_Db_Select::SQL_DESC)
    {
        throw new \LogicException("Sorting on multiple stores is not allowed in search engine collections.");
    }

    /**
     * Add filter by store
     *
     * @param int|array|\Magento\Store\Model\Store $storeId Store id
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * Returns current store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Add filter by store
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) Method is inherited
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) Method is inherited
     *
     * @param int|array|\Magento\Store\Model\Store $store     Store
     * @param bool                                 $withAdmin With admin
     *
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if (is_object($store)) {
            $store = $store->getId();
        }

        if (is_array($store)) {
            throw new \LogicException("Filtering on multiple stores is not allowed in search engine collections.");
        }

        return $this->setStoreId($store);
    }

    /**
     * Add search query filter
     *
     * @param string $query Search query text.
     *
     * @return \Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\Collection
     */
    public function addSearchFilter($query)
    {
        $this->queryText = $query;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $this->filters[$field] = $condition;

        return $this;
    }

    /**
     * Redefined to delete filter attribute_set_id
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_init('Smile\Retailer\Model\Retailer', 'Smile\Seller\Model\ResourceModel\Seller');
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritdoc}
     */
    protected function _renderFiltersBefore()
    {
        $searchRequest = $this->prepareRequest();

        $this->queryResponse = $this->searchEngine->search($searchRequest);

        // Update the product count.
        $this->_totalRecords = $this->queryResponse->count();

        // Filter search results. The pagination has to be resetted since it is managed by the engine itself.
        $docIds = array_map(
            function (\Magento\Framework\Api\Search\Document $doc) {
                return (int) $doc->getId();
            },
            $this->queryResponse->getIterator()->getArrayCopy()
        );

        if (empty($docIds)) {
            $docIds[] = 0;
        }

        $this->getSelect()->where('retailer_id IN (?)', ['in' => $docIds]);
        $this->_pageSize = false;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _afterLoad()
    {
        // Resort items according the search response.
        $orginalItems = $this->_items;
        $this->_items = [];

        foreach ($this->queryResponse->getIterator() as $document) {
            $documentId = $document->getId();
            if (isset($orginalItems[$documentId])) {
                $this->_items[$documentId] = $orginalItems[$documentId];
                $this->_items[$documentId]->setStoreId($this->storeId);
            }
        }

        return parent::_afterLoad();
    }

    /**
     * Prepare the search request before it will be executed.
     *
     * @return RequestInterface
     */
    private function prepareRequest()
    {
        // Store id and request name.
        $storeId           = $this->storeId;
        $searchRequestName = $this->searchRequestName;

        // Pagination params.
        $size = $this->_pageSize ? $this->_pageSize : 20;
        $from = $size * (max(1, $this->_curPage) - 1);

        // Query text.
        $queryText = $this->queryText;

        // Setup sort orders.
        $sortOrders = $this->prepareSortOrders();

        $searchRequest = $this->requestBuilder->create(
            $storeId,
            $searchRequestName,
            $from,
            $size,
            $queryText,
            $sortOrders,
            $this->filters,
            $this->facets
        );

        return $searchRequest;
    }

    /**
     * Prepare sort orders for the request builder.
     *
     * @return array()
     */
    private function prepareSortOrders()
    {
        $sortOrders = [];

        foreach ($this->_orders as $attribute => $direction) {
            $sortParams = ['direction' => $direction];
            $sortField = $this->mapFieldName($attribute);
            $sortOrders[$sortField] = $sortParams;
        }

        return $sortOrders;
    }

    /**
     * Convert standard field name to ES fieldname.
     * (eg. category_ids => category.category_id).
     *
     * @param string $fieldName Field name to be mapped.
     *
     * @return string
     */
    private function mapFieldName($fieldName)
    {
        if (isset($this->fieldNameMapping[$fieldName])) {
            $fieldName = $this->fieldNameMapping[$fieldName];
        }

        return $fieldName;
    }

    /**
     * Load retailer collection size.
     *
     * @return void
     */
    private function loadRetailersCounts()
    {
        $storeId     = $this->getStoreId();
        $requestName = $this->searchRequestName;

        // Query text.
        $queryText = $this->queryText;

        $searchRequest = $this->requestBuilder->create($storeId, $requestName, 0, 0, $queryText, [], $this->filters);

        $searchResponse = $this->searchEngine->search($searchRequest);

        $this->_totalRecords = $searchResponse->count();
    }
}
