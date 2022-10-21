<?php declare(strict_types=1);

namespace NfBatteriePfand;

use Netformic\BatteriumTheme\CustomFields;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NfBatteriePfand extends Plugin
{
    const NF_CUSTOM_FIELDS_BATTERY_DEPOSIT_NAME = 'nf_product_additional_info_battery_deposit';

    public function install(InstallContext $installContext): void
    {
        self::createCustomField($installContext->getContext(), $this->container);
    }

    public static function createCustomField(Context $context, ContainerInterface $container): void
    {
        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $container->get('custom_field_set.repository');

        $customFieldSetRepository->upsert([[
            'id' => md5(CustomFields::NF_CUSTOM_FIELDS_SET_NAME),
            'customFields' => [
                [
                    'id' => md5(self::NF_CUSTOM_FIELDS_BATTERY_DEPOSIT_NAME),
                    'name' => self::NF_CUSTOM_FIELDS_BATTERY_DEPOSIT_NAME,
                    'type' => CustomFieldTypes::BOOL,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Battery deposit',
                            'de-DE' => 'Batterie Pfand'
                        ],
                        'customFieldPosition' => 1,
                        'customFieldType' => 'checkbox',
                        'componentName' => 'sw-field',
                    ]
                ],
            ]
        ]], $context);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        self::removeCustomFields($uninstallContext->getContext(), $this->container);
    }

    public static function removeCustomFields(Context $context, ContainerInterface $container): void
    {
        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $container->get('custom_field_set.repository');

        /** @var CustomFieldSetEntity $customFieldSet */
        $customFieldSet = $customFieldSetRepository->search(
            (new Criteria())
                ->addFilter(new EqualsFilter('custom_field_set.name', CustomFields::NF_CUSTOM_FIELDS_SET_NAME)),
            $context
        )->first();

        if ($customFieldSet) {
            $customFieldSetRepository->delete(
                [['id' => $customFieldSet->getId()]],
                $context
            );
        }
    }
}
