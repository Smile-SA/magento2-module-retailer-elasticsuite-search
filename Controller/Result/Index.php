<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Controller\Result;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Retailer results.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * Page Factory
     */
    protected PageFactory $resultPageFactory;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context      $context     Context.
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory Page factory.
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $pageFactory;
    }

    /**
     * Execute the action
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();

        return $resultPage;
    }
}
