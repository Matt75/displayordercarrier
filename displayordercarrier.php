<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class DisplayOrderCarrier extends Module
{
    /**
     * @var array List of hooks used.
     */
    public $hooks = [
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
        $this->version = '1.0.0';
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
                'class' => 'fixed-width-xs',
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
                    'src' => '../s/' . $fields['id_carrier'] . '.jpg',
                    'alt' => $fields['carrier_name'],
                ];
            }
        }
    }
}
