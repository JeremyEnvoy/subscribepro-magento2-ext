<?php

namespace Swarming\SubscribePro\Observer\CheckoutCart;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor as SubscriptionOptionProcessor;

class CartUpdateItemsAfter extends CheckoutCartAbstract implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if (!$this->generalConfig->isEnabled()) {
            return;
        }

        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $observer->getData('cart');
        $quote = $cart->getQuote();

        /** @var \Magento\Framework\DataObject $infoDataObject */
        $infoDataObject = $observer->getData('info');

        foreach ($quote->getAllItems() as $quoteItem) {
            $subscriptionParams = $this->getSubscriptionParams($quoteItem, $infoDataObject);
            try {
                $this->updateQuoteItem($quoteItem, $subscriptionParams);
            } catch (LocalizedException $e) {
                $quoteItem->isDeleted(true);
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Magento\Framework\DataObject $infoDataObject
     * @return array
     */
    protected function getSubscriptionParams(QuoteItem $quoteItem, $infoDataObject)
    {
        $quoteItemId = $quoteItem->getParentItemId() ?: $quoteItem->getItemId();
        $quoteItemParams = $infoDataObject->getData($quoteItemId);

        return isset($quoteItemParams[SubscriptionOptionProcessor::KEY_SUBSCRIPTION_OPTION])
            ? $quoteItemParams[SubscriptionOptionProcessor::KEY_SUBSCRIPTION_OPTION]
            : [];
    }
}
