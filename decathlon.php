<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Decathlon extends Module
{
    protected $config_form = false;

    const BASIC_AUTH_STRING = 'Q2JiNDE0YTZmMWRhYWNhNGUxNjc0OWE3NmUyNGZjOGVmMmJhOGFkZTU6U1dMQllYZkU4b00zZGVETEZ3Sk1hSFNTRHdNR2ZaYVBFRTdWd0Y5Vmpvb0pXZGFzQTB2UWZSNHVyWEVITlljSw==';

    public function __construct()
    {
        $this->name = 'decathlon';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Eduardo González';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Decathlon Product Service');
        $this->description = $this->l('Este módulo consulta información de un producto a la API de Decathlon para desplegar la información en la página de un producto');

        $this->confirmUninstall = $this->l('¿Desea desinstalar el módulo?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('DECATHLON_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayAfterProductThumbs');
        }


    public function uninstall()
    {
        Configuration::deleteByName('DECATHLON_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitDecathlonModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDecathlonModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'DECATHLON_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'DECATHLON_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'DECATHLON_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'DECATHLON_LIVE_MODE' => Configuration::get('DECATHLON_LIVE_MODE', true),
            'DECATHLON_ACCOUNT_EMAIL' => Configuration::get('DECATHLON_ACCOUNT_EMAIL', 'edlugome@gmail.com'),
            'DECATHLON_ACCOUNT_PASSWORD' => Configuration::get('DECATHLON_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    public function hookDisplayAfterProductThumbs() {
        $product = $this->getProductInfo('8551453');

        $this->context->smarty->assign(
            array(
                'decathlon_product_name'    => $product->name,
                'decathlon_product_image'   => $product->image,
                'decathlon_product_url'     => $product->url,
                'decathlon_product_price'   => $product->price
            )
        );

        return $this->display(__FILE__, 'product.tpl');
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
    
    /**
     * requestToken
     * Solicita el tooken de autorización para poder hacer peticiones a servicio de productos Decathlon
     * @return void
     */
    private function requestToken() {
        $postRequest = array(
            'grant_type' => 'client_credentials'
        );
        
        $auth_string = self::BASIC_AUTH_STRING;

        $options = array(
            'http' => array(
                'method'  => 'POST',
                'content' => http_build_query($postRequest),
                'header' => [
                    "Authorization: Basic $auth_string",
                    "Content-Type: application/x-www-form-urlencoded"
                ],
            )
        );

        $context  = stream_context_create($options);
        $result = file_get_contents('https://idpdecathlon.oxylane.com/as/token.oauth2', false, $context);
    
        return json_decode($result)->access_token;
    }
    
    /**
     * getProductInfo
     *
     * @param string $token
     * @param string $product_id
     * @return Product
     */
    private function getProductInfo(string $product_id) {
        $token = $this->requestToken();

        $product_id = '8551453';

        // $curl_connection = curl_init();
        // curl_setopt($curl_connection, CURLOPT_URL, " https://api-eu.subsidia.org/spid/v4/superModel/model/8551453?availabilityDate=2021-08-06&locales=es_MX");

        // curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);

        // curl_setopt($curl_connection, CURLOPT_HTTPHEADER, array(
        //     "Authorization: Bearer $token",
        //     "x-api-key: 4a39000a-e495-4d95-98fd-bd00bc4345bb"
        // ));

        // $result = curl_exec($curl_connection);
        // curl_close($curl_connection);

        $product = new stdClass();
        $product->name  = 'Botín senderismo de nieve hombre sh100 warm azul';
        $product->price = 48;
        $product->image = 'https://contents.mediadecathlon.com/p1647816/k$2479e9814c12758f5184356b0913cb33/botas-de-senderismo-nieve-hombre-sh100-warm-mid-azul.jpg?&f=452x452';
        $product->url   = 'https://www.decathlon.cl/bototos/133825-6613-botas-de-senderismo-nieve-hombre-sh100-warm-mid-azul.html#/425-demodelsize-27239/3281-demodelcolor-8367614';

        $this->insertIntoDecathlonTable(
            $product->name,
            $product->price,
            $product->image,
            $product->url
        );

        return $product;
    }
    
    /**
     * insertIntoDecathlonTable
     * Inserta registro de producto obtenido en base de datos
     * @param string $name
     * @param int $price
     * @param string $image
     * @param string $url
     * @return void
     */
    private function insertIntoDecathlonTable(string $name, int $price, string $image, string $url) {
        $db_name = '`' . _DB_PREFIX_ . 'decathlon`';

        $insert_sql = "INSERT INTO $db_name (`id_decathlon`, `product_name`, `product_price`, `product_image`, `product_url`, `created_at`) VALUES (NULL, '$name', $price, '$image', '$url', CURRENT_TIMESTAMP)";
        DB::getInstance()->execute($insert_sql);
    }
}