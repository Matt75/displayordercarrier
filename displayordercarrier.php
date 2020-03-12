<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DisplayOrderCarrier extends Module
{
    /**
     * @var array list of hooks used
     */
    public $hooks = [
        'actionAdminControllerSetMedia',
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
        $this->version = '1.0.2';
        $this->author = 'Matt75';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6.1.0',
            'max' => '1.7.7.0', // Because orders list should be migrate on 1.7.7
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
            && $this->registerHook($this->hooks)
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
     * Add CSS to fix carrier logo size in Order List page
     *
     * @param array $params
     */
    public function hookActionAdminControllerSetMedia(array $params)
    {
        if ('AdminOrders' === Tools::getValue('controller')
            && false === Tools::getIsset('addorder')
            && false === Tools::getIsset('vieworder')
            && Configuration::get(static::CONFIGURATION_KEY_SHOW_LOGO)
        ) {
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/displayordercarrier.css', 'all');
        }
    }

    /**
     * Append custom fields.
     *
     * @param array $params
     */
    public function hookActionAdminOrdersListingFieldsModifier(array $params)
    {
        // If hook is called in AdminController::processFilter() we have to check existence
        if (isset($params['select'])) {
            $params['select'] .= ', a.id_carrier, car.name AS carrier_name';
        }

        // If hook is called in AdminController::processFilter() we have to check existence
        if (isset($params['join'])) {
            $params['join'] .= 'LEFT JOIN ' . _DB_PREFIX_ . 'carrier AS car ON (a.id_carrier = car.id_carrier)';
        }

        $params['fields']['carrier_name'] = [
            'title' => $this->l('Carrier'),
            'align' => 'text-center',
            'class' => 'fixed-width-xs',
            'filter_key' => 'car!name',
            'order_key' => 'car!name',
        ];

        if (Configuration::get(static::CONFIGURATION_KEY_SHOW_LOGO)) {
            $params['fields']['carrier_name']['icon'] = true;
            $params['fields']['carrier_name']['class'] .= ' column-img-carrier';
        }
    }

    /**
     * Set additional order data.
     *
     * @param array $params
     */
    public function hookActionAdminOrdersListingResultsModifier(array $params)
    {
        if (Configuration::get(static::CONFIGURATION_KEY_SHOW_LOGO)) {
            foreach ($params['list'] as $key => $fields) {
                if (isset($fields['id_carrier'], $fields['carrier_name'])) {
                    $params['list'][$key]['carrier_name'] = [
                        'src' => '../s/' . (int) $fields['id_carrier'] . '.jpg',
                        'alt' => Tools::safeOutput($fields['carrier_name']),
                    ];
                }
            }
        }
    }
}
