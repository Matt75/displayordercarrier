<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

class DisplayOrderCarrier extends Module
{
    /**
     * List of hooks used
     */
    const HOOKS = [
        'actionOrderGridDefinitionModifier',
        'actionOrderGridDataModifier',
        'actionOrderGridQueryBuilderModifier',
        'actionAdminOrdersListingFieldsModifier',
        'actionAdminOrdersListingResultsModifier',
    ];

    /**
     * Name of ModuleAdminController used for configuration
     */
    const MODULE_ADMIN_CONTROLLER = 'AdminDisplayOrderCarrier';

    /**
     * Configuration key used to store toggle for display logo
     */
    const CONFIGURATION_KEY_SHOW_LOGO = 'DISPLAYORDERCARRIER_SHOW_LOGO';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'displayordercarrier';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'Matt75';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6.1.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->l('Display Carrier on Orders list');
        $this->description = $this->l('Adds Carrier on Order list');
    }

    /**
     * Install Module.
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook(static::HOOKS)
            && $this->installTabs()
            && Configuration::updateValue(static::CONFIGURATION_KEY_SHOW_LOGO, false);
    }

    /**
     * Install Tabs
     *
     * @return bool
     */
    public function installTabs()
    {
        if (Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = true;
        $tab->id_parent = -1;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->displayName
        );

        return $tab->add();
    }

    /**
     * Uninstall Module
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTabs()
            && Configuration::deleteByName(static::CONFIGURATION_KEY_SHOW_LOGO);
    }

    /**
     * Uninstall Tabs
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return $tab->delete();
        }

        return true;
    }

    /**
     * Redirect to our ModuleAdminController when click on Configure button
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(static::MODULE_ADMIN_CONTROLLER));
    }

    /**
     * Hook allows to modify Order grid definition since 1.7.7.0
     *
     * @param array $params
     */
    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        if (empty($params['definition'])) {
            return;
        }

        /** @var PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface $definition */
        $definition = $params['definition'];

        if (Configuration::get(static::CONFIGURATION_KEY_SHOW_LOGO)) {
            $column = new PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ImageColumn('carrier_reference');
            $column->setName($this->l('Carrier'));
            $column->setOptions([
                'src_field' => 'carrier_logo',
                'clickable' => false,
            ]);
        } else {
            $column = new PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn('carrier_reference');
            $column->setName($this->l('Carrier'));
            $column->setOptions([
                'field' => 'carrier_name',
            ]);
        }

        $definition
            ->getColumns()
            ->addAfter(
                'payment',
                $column
            )
        ;

        /** @var PrestaShop\PrestaShop\Core\Form\ChoiceProvider\CarrierByReferenceChoiceProvider $carrierByReferenceChoiceProvider */
        $carrierByReferenceChoiceProvider = $this->get('prestashop.core.form.choice_provider.carrier_by_reference_id');

        $definition->getFilters()->add(
            (new PrestaShop\PrestaShop\Core\Grid\Filter\Filter('carrier_reference', Symfony\Component\Form\Extension\Core\Type\ChoiceType::class))
                ->setAssociatedColumn('carrier_reference')
                ->setTypeOptions([
                    'required' => false,
                    'choices' => $carrierByReferenceChoiceProvider->getChoices(),
                    'translation_domain' => false,
                ])
        );
    }

    /**
     * Hook allows to modify Order grid data since 1.7.7.0
     *
     * @param array $params
     */
    public function hookActionOrderGridDataModifier(array $params)
    {
        if (empty($params['data'])) {
            return;
        }

        /** @var PrestaShop\PrestaShop\Core\Grid\Data\GridData $gridData */
        $gridData = $params['data'];
        $modifiedRecords = $gridData->getRecords()->all();
        /** @var PrestaShop\PrestaShop\Core\Image\Parser\ImageTagSourceParserInterface $imageTagSourceParser */
        $imageTagSourceParser = $this->get('prestashop.core.image.parser.image_tag_source_parser');
        $carrierLogoThumbnailProvider = new \PrestaShop\Module\DisplayOrderCarrier\CarrierLogoThumbnailProvider($imageTagSourceParser);

        foreach ($modifiedRecords as $key => $data) {
            if (empty($data['carrier_name'])) {
                $modifiedRecords[$key]['carrier_name'] = Carrier::getCarrierNameFromShopName();
            }
            $modifiedRecords[$key]['carrier_logo'] = $carrierLogoThumbnailProvider->getPath($data['id_carrier']);
        }

        $params['data'] = new PrestaShop\PrestaShop\Core\Grid\Data\GridData(
            new PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection($modifiedRecords),
            $gridData->getRecordsTotal(),
            $gridData->getQuery()
        );
    }

    /**
     * Hook allows to modify Order query builder and add custom sql statements since 1.7.7.0
     *
     * @param array $params
     */
    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        if (empty($params['search_query_builder']) || empty($params['search_criteria'])) {
            return;
        }

        /** @var Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        /** @var PrestaShop\PrestaShop\Core\Search\Filters\OrderFilters $searchCriteria */
        $searchCriteria = $params['search_criteria'];

        $searchQueryBuilder->addSelect(
            'o.`id_carrier`, car.`id_reference` AS `carrier_reference`, car.`name` AS `carrier_name`'
        );

        $searchQueryBuilder->leftJoin(
            'o',
            '`' . _DB_PREFIX_ . 'carrier`',
            'car',
            'car.`id_carrier` = o.`id_carrier`'
        );

        if ('carrier_reference' === $searchCriteria->getOrderBy()) {
            $searchQueryBuilder->orderBy('car.`id_reference`', $searchCriteria->getOrderWay());
        }

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('carrier_reference' === $filterName) {
                $searchQueryBuilder->andWhere('car.`id_reference` = :carrier_reference');
                $searchQueryBuilder->setParameter('carrier_reference', $filterValue);
            }
        }
    }

    /**
     * Hook allows to modify Order grid data before 1.7.7.0
     *
     * @param array $params
     */
    public function hookActionAdminOrdersListingFieldsModifier(array $params)
    {
        // If hook is called in AdminController::processFilter() we have to check existence
        if (isset($params['select'])) {
            $params['select'] .= ', a.id_carrier, car.id_reference AS carrier_reference, car.name AS carrier_name';
        }

        // If hook is called in AdminController::processFilter() we have to check existence
        if (isset($params['join'])) {
            $params['join'] .= 'LEFT JOIN ' . _DB_PREFIX_ . 'carrier AS car ON (a.id_carrier = car.id_carrier)';
        }

        $list = [];
        $carriers = Carrier::getCarriers(
            (int) $this->context->employee->id_lang,
            false,
            false,
            false,
            null,
            Carrier::ALL_CARRIERS
        );

        if (false === empty($carriers)) {
            foreach ($carriers as $carrier) {
                $list[(int) $carrier['id_reference']] = $carrier['name'];
            }
        }

        $params['fields']['carrier_name'] = [
            'title' => $this->l('Carrier'),
            'align' => 'text-center',
            'class' => 'fixed-width-xs',
            'filter_key' => 'car!id_reference',
            'order_key' => 'car!id_reference',
            'type' => 'select',
            'list' => $list,
        ];

        if (Configuration::get(static::CONFIGURATION_KEY_SHOW_LOGO)) {
            $params['fields']['carrier_name']['callback'] = 'renderCarrierLogo';
            $params['fields']['carrier_name']['callback_object'] = $this;
        }
    }

    /**
     * Hook allows to modify Order grid data before 1.7.7.0
     *
     * @param array $params
     */
    public function hookActionAdminOrdersListingResultsModifier(array $params)
    {
        foreach ($params['list'] as $key => $fields) {
            if (empty($fields['carrier_name'])) {
                $params['list'][$key]['carrier_name'] = Carrier::getCarrierNameFromShopName();
            }
        }
    }

    /**
     * Callback function used by legacy HelperList to retrieve Carrier logo before 1.7.7.0
     *
     * @param string $echo Carrier name
     * @param array $tr Data
     *
     * @return string
     */
    public function renderCarrierLogo($echo, $tr)
    {
        $logoPath = _PS_SHIP_IMG_DIR_ . (int) $tr['id_carrier'] . '.jpg';

        if (false === Tools::file_exists_cache($logoPath)) {
            $logoPath = _PS_IMG_DIR_ . '404.gif';
        }

        return str_replace(
            'alt=""',
            'alt="' . $echo . '" title="' . $echo . '"',
            ImageManager::thumbnail(
                $logoPath,
                'carrier_mini_' . (int) $tr['id_carrier'] . '.jpg',
                34
            )
        );
    }
}
