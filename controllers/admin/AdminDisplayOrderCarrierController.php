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
class AdminDisplayOrderCarrierController extends ModuleAdminController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        $list = [];
        foreach (DisplayOrderCarrier::ORDER_GRID_DEFINITIONS as $key => $value) {
            $list[] = ['id' => $key, 'name' => $this->trans($value, [], 'Admin.Global')];
        }

        $this->fields_options = [
            'products' => [
                'title' => $this->l('Display Carrier on Order list'),
                'fields' => [
                    DisplayOrderCarrier::CONFIGURATION_KEY_SHOW_LOGO => [
                        'title' => $this->l('Show logo instead of carrier name'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'required' => false,
                        'type' => 'bool',
                    ],
                    DisplayOrderCarrier::CONFIGURATION_KEY_COLUMN => [
                        'title' => $this->trans('After wich column you want to display the carrier'),
                        'validation' => 'isGenericName',
                        'required' => false,
                        'type' => 'select',
                        'list' => $list,
                        'identifier' => 'id',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }
}
