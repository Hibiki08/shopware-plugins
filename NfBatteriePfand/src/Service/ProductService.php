<?php declare(strict_types=1);

namespace NfBatteriePfand\Service;

use NfBatteriePfand\NfBatteriePfand;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ProductService
{
    private EntityRepositoryInterface $productRepository;

    public function __construct(EntityRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param string $productId
     * @param Context $context
     * @return ProductEntity|null
     */
    public function getProductById(string $productId, Context $context): ?ProductEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $productId));

        return $this->productRepository->search($criteria, $context)->first();
    }

    public function hasBatteryDepositField(ProductEntity $productEntity): bool
    {
        $customFields = $productEntity->getCustomFields();
        if (!$customFields) {
            $parentProduct = $productEntity->getParent() ?? null;
            $customFields = $parentProduct ? $parentProduct->getCustomFields() : null;
        }

        return $customFields && isset($customFields[NfBatteriePfand::NF_CUSTOM_FIELDS_BATTERY_DEPOSIT_NAME]);
    }
}
