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
     * Constructor.
     */
    public function __construct()
    {
        $this->name = 'displayordercarrier';
        $this->tab = 'administration';
        $this->version = '1.0.1';
        $this->author = 'Matt75';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6.1.0',
            'max' => '1.7.6.99', // Because orders list should be migrate on 1.7.7
        ];

        parent::__construct();

        $this->displayName = $this->l('Carrier logo on Orders list');
        $this->description = $this->l('Adds carrier logo on Order list');
    }

    /**
     * Install Module.
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook($this->hooks);
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

        $params['fields'] += [
            'carrier_name' => [
                'title' => $this->l('Carrier'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs column-img-carrier',
                'icon' => true,
                'filter_key' => 'car!name',
                'order_key' => 'car!name',
            ],
        ];
    }

    /**
     * Set additional order data.
     *
     * @param array $params
     */
    public function hookActionAdminOrdersListingResultsModifier(array $params)
    {
        foreach ($params['list'] as &$fields) {
            if (isset($fields['id_carrier'], $fields['carrier_name'])) {
                $fields['carrier_name'] = [
                    'src' => '../s/' . (int) $fields['id_carrier'] . '.jpg',
                    'alt' => Tools::safeOutput($fields['carrier_name']),
                ];
            }
        }
    }
}
