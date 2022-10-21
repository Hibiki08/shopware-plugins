<?php declare(strict_types=1);

namespace NfBatteriePfand\Tests\Integration;

use Netformic\BatteriumTheme\CustomFields;
use NfBatteriePfand\Core\Checkout\CartProcessor;
use NfBatteriePfand\NfBatteriePfand;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;

class CustomFieldTest extends TestCase
{
    use IntegrationTestBehaviour;

    /** @var EntityRepository */
    private $productRepository;

    /** @var AbstractSalesChannelContextFactory */
    private $factory;

    /** @var SalesChannelContext */
    private SalesChannelContext $context;

    private CartProcessor $cartProcessor;

    const PRODUCT_TEST_ID = 'product_test_id';
    const PRODUCT_WITH_FIELD_TEST_ID = 'product_with_field_test_id';

    public function setUp(): void
    {
        $this->productRepository = $this->getContainer()->get('product.repository');
        $this->factory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->context = $this->factory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $this->createTestProducts();
    }

    public function testFieldExists_pluginInstallationShouldCreateCustomField(): void
    {
        $context = new Context(new SystemSource());
        $container = $this->getContainer();
        $customFieldSetRepository = $container->get('custom_field_set.repository');

        NfBatteriePfand::createCustomField($context, $container);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', CustomFields::NF_CUSTOM_FIELDS_SET_NAME));
        $criteria->addAssociation('customFields');
        $customFieldSetSearchResult = $customFieldSetRepository->search($criteria, $context);

        $this->assertNotNull($customFieldSetSearchResult->first());
        $this->assertEquals(
            NfBatteriePfand::NF_CUSTOM_FIELDS_BATTERY_DEPOSIT_NAME,
            $customFieldSetSearchResult->first()->getCustomFields()->first()->getName()
        );
    }

    public function testDepositIsAdded_productWithFieldShouldBeAddedWithDeposit(): void
    {
        $productId = self::PRODUCT_WITH_FIELD_TEST_ID;
        $lineItems = $this->getProcessedItems($productId);

        $this->assertEquals(2, $lineItems->count());
        $this->assertNotNull($lineItems->getElements()[md5($productId) . '_bd']);
    }

    public function testDepositIsNotAdded_productWithoutFieldShouldBeAddedAlone(): void
    {
        $lineItems = $this->getProcessedItems(self::PRODUCT_TEST_ID);
        $this->assertEquals(1, $lineItems->count());
    }

    private function getProcessedItems(string $productId): LineItemCollection
    {
        $cart = new Cart('test', 'test');
        $cart->add($this->createProductItem($productId));

        /** @var CartProcessor $cartProcessor */
        $cartProcessor = $this->getContainer()->get(CartProcessor::class);
        $cartProcessor->process(
            new CartDataCollection(),
            $cart,
            $cart,
            $this->context,
            new CartBehavior()
        );

        return $cart->getLineItems();
    }

    private function createProductItem(string $productId): LineItem
    {
        return new LineItem(
            md5($productId),
            LineItem::PRODUCT_LINE_ITEM_TYPE,
            md5($productId),
            1
        );
    }

    private function createTestProducts(): void
    {
        $defaultProductData = [
            'stock' => 1,
            'name' => 'Test',
            'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
            'tax' => ['id' => md5('tax_id'), 'taxRate' => 17, 'name' => 'with id'],
            'active' => true,
        ];

        $this->productRepository->create([
            array_merge([
                'id' => md5(self::PRODUCT_TEST_ID),
                'productNumber' => 'number1',
            ], $defaultProductData),
            array_merge([
                'id' => md5(self::PRODUCT_WITH_FIELD_TEST_ID),
                'productNumber' => 'number2',
                'customFields' => [
                    NfBatteriePfand::NF_CUSTOM_FIELDS_BATTERY_DEPOSIT_NAME => true
                ]
            ], $defaultProductData),
        ], $this->context->getContext());
    }
}
