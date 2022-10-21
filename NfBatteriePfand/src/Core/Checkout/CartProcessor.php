<?php declare(strict_types=1);

namespace NfBatteriePfand\Core\Checkout;

use NfBatteriePfand\NfBatteriePfand;
use NfBatteriePfand\Service\ProductService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CartProcessor implements CartProcessorInterface
{
    private AbsolutePriceCalculator $calculator;
    private ProductService $productService;
    private SystemConfigService $systemConfigService;

    public function __construct(
        AbsolutePriceCalculator $calculator,
        ProductService $productService,
        SystemConfigService $systemConfigService
    )
    {
        $this->calculator = $calculator;
        $this->productService = $productService;
        $this->systemConfigService = $systemConfigService;
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $salesChannelContext,
        CartBehavior $behavior
    ): void
    {
        $productItems = $toCalculate->getLineItems();
        if ($productItems->count() === 0) {
            return;
        }

        foreach ($productItems as $productItem) {
            $currentProduct = $this->productService->getProductById(
                $productItem->getId(),
                $salesChannelContext->getContext()
            );
            if ($currentProduct && $this->productService->hasBatteryDepositField($currentProduct)) {
                $batteryDepositItem = $this->createBatteryDepositItem($productItem);
                $batteryDepositItem->setPrice(
                    $this->calculator->calculate(
                        $this->systemConfigService->get('NfBatteriePfand.config.depositAmount'),
                        $productItems->getPrices(),
                        $salesChannelContext,
                        $batteryDepositItem->getQuantity()
                    )
                );

                $toCalculate->add($batteryDepositItem);
            }
        }
    }

    private function createBatteryDepositItem(LineItem $productItem): LineItem
    {
        $fieldId = $productItem->getId() . '_bd';
        $batteryDepositItem = new LineItem(
            $fieldId,
            LineItem::CUSTOM_LINE_ITEM_TYPE,
            $fieldId,
            $productItem->getQuantity()
        );

        $batteryDepositItem->setLabel(
            'customFields.' . NfBatteriePfand::NF_CUSTOM_FIELDS_BATTERY_DEPOSIT_NAME
        );
        $batteryDepositItem->setPayloadValue(
            'referredProductNumber',
            $productItem->getPayloadValue('productNumber')
        );
        $batteryDepositItem->setGood(false);

        return $batteryDepositItem;
    }
}
