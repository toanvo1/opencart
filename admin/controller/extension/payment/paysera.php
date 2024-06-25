<?php
/**
 * 2018 Paysera
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@paysera.com so we can send you a copy immediately.
 *
 *  @author    Paysera <plugins@paysera.com>
 *  @copyright 2018 Paysera
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Paysera
 */

require_once DIR_SYSTEM . 'library/paysera/payment/vendor/autoload.php';

use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;
use Paysera\Payment\Model\OrderStatusModelWrapper;
use Paysera\Payment\Factory\OrderStatusRepositoryFactory;
use Paysera\Payment\Repository\MessageRepository;
use Paysera\Payment\Repository\OrderStatusRepository;
use Paysera\Payment\Validator\PluginSettingsValidator;

/**
 * Class ControllerExtensionPaymentPaysera
 */
class ControllerExtensionPaymentPaysera extends Controller
{
    /**
     * Default currency
     */
    const PAYSERA_CURRENCY = 'EUR';

    /**
     * Default language code
     */
    const PAYSERA_DEFAULT_LANG = 'en';

    /**
     * Empty value
     */
    const PAYSERA_EMPTY_VALUE = '';

    /**
     * Default method
     */
    const REQUEST_METHOD_TYPE = 'POST';

    /**
     * Default project id
     */
    const PAYSERA_DEFAULT_PROJECT_ID = 1;

    /**
     * Paysera payment module name
     */
    const PAYSERA_PAYMENT = 'payment_paysera';

    /**
     * Success text
     */
    const PAYSERA_SUCCESS = 'text_success';

    /**
     * Paysera extension function
     */
    const PAYSERA_EXTENSION_PAYMENT = 'extension/payment/paysera';
    const PAYSERA_EXTENSION_PAYMENT_TEMPLATE = 'extension/payment/paysera/paysera';

    /**
     * Marketplace extension location
     */
    const PAYSERA_MARKETPLACE_EXTENSIONS = 'marketplace/extension';

    /**
     * Dashboard
     */
    const PAYSERA_COMMON_DASHBOARD = 'common/dashboard';

    /**
     * Prefix used with an error code
     */
    const PAYSERA_ERROR_PREFIX = 'error_';

    /**
     * Callback url
     */
    const PAYSERA_CALLBACK_URL = 'index.php?route=extension/payment/paysera/callback';

    /**
     * Token param
     */
    const PAYSERA_TOKEN_PARAM = 'user_token=';

    /**
     * Token value
     */
    const PAYSERA_TYPE_PAYMENT = '&type=payment';

    /**
     * Paysera header hook controller
     */
    const PAYSERA_HEADER_CONTROLER = 'extension/payment/paysera/paysera_header';

    /**
     * Paysera footer hook controller
     */
    const PAYSERA_FOOTER_CONTROLER = 'extension/payment/paysera/paysera_footer';

    /**
     * Paysera header hook event
     */
    const PAYSERA_EVENT_HEADER = 'catalog/view/common/header/before';

    /**
     * Paysera footer hook event
     */
    const PAYSERA_EVENT_FOOTER = 'catalog/view/common/footer/after';

    /**
     * Paysera header event name
     */
    const PAYSERA_EVENT_HEADER_NAME = 'paysera_header';

    /**
     * Paysera footer event name
     */
    const PAYSERA_EVENT_FOOTER_NAME = 'paysera_footer';

    const LANGUAGE_ABOUT = 'extension/payment/paysera/about';
    const LANGUAGE_NO_PLUGIN = 'extension/payment/paysera/no_delivery_plugin';

    const PAYSERA_EVENT_MENU_NAME = 'paysera_payment_menu';
    const PAYSERA_EVENT_MENU = 'admin/view/common/column_left/before';
    const PAYSERA_MENU_CONTROLLER = 'extension/payment/paysera/payseraPaymentMenuHandler';

    const PAYSERA_EXTENSION_ABOUT = 'extension/payment/paysera/about';
    const PAYSERA_EXTENSION = 'extension/payment/paysera';
    const PAYSERA_EXTENSION_NO_DELIVERY_PLUGIN = 'extension/payment/paysera/no_delivery_plugin';

    /**
     * @var string
     */
    private $projectID;

    /**
     * @var array
     */
    private $error = array();

    /**
     * @var array
     */
    private $errorFieldName = array(
        'warning',
        'account_tab',
        'payment_paysera_project',
        'payment_paysera_sign',
        'order_status_tab',
        'payment_paysera_new_order_status_id',
        'payment_paysera_paid_status_id',
        'payment_paysera_pending_status_id',
    );

    /**
     * @var array
     */
    private $breadcrumbFields = array(
        'text_home'      => 'common/dashboard',
        'text_extension' => 'marketplace/extension',
        'heading_title'  => 'extension/payment/paysera'
    );

    /**
     * @var array
     */
    private $payseraFieldsName = array(
        'payment_paysera_status',
        'payment_paysera_project',
        'payment_paysera_sign',
        'payment_paysera_test',
        'payment_paysera_total',
        'payment_paysera_title',
        'payment_paysera_description',
        'payment_paysera_display_payments_list',
        'payment_paysera_category',
        'paysera_selected_countries',
        'paysera_countries',
        'payment_paysera_grid_view',
        'payment_paysera_buyer_consent',
        'payment_paysera_default_country',
        'payment_paysera_geo_zone_id',
        'payment_paysera_sort_order',
        'payment_paysera_new_order_status_id',
        'payment_paysera_paid_status_id',
        'payment_paysera_pending_status_id',
        'payment_paysera_quality',
        'payment_paysera_owner',
        'payment_paysera_owner_code'
    );

    protected OrderStatusRepositoryFactory $orderStatusRepositoryFactory;
    private MessageRepository $messageRepository;

    public function __construct($registry)
    {
        parent::__construct($registry);


        $this->orderStatusRepositoryFactory = new OrderStatusRepositoryFactory($registry);
        $this->messageRepository = new MessageRepository($registry);
    }

    public function index()
    {
        $this->document->setTitle($this->messageRepository->get('heading_title'));

        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] == $this::REQUEST_METHOD_TYPE
            && $this->validate()) {
            $this->model_setting_setting->editSetting(
                $this::PAYSERA_PAYMENT,
                $this->request->post
            );

            $this->session->data['success'] = $this->generateData(
                $this::PAYSERA_SUCCESS,
                $this::PAYSERA_EMPTY_VALUE
            );

            $this->response->redirect($this->generateData(
                $this::PAYSERA_EMPTY_VALUE,
                $this::PAYSERA_MARKETPLACE_EXTENSIONS
            ));
        }

        foreach ($this->getErrorFieldName() as $fieldName) {
            $dataName = $this::PAYSERA_ERROR_PREFIX . $fieldName;
            $data[$dataName] = $this->errorValue($dataName);
        }

        foreach ($this->getBreadcrumbFields() as $key => $value) {
            $data['breadcrumbs'][] = $this->generateData($key, $value);
        }

        $data['action'] = $this->generateData(
            $this::PAYSERA_EMPTY_VALUE,
            $this::PAYSERA_EXTENSION_PAYMENT
        );
        $data['cancel'] = $this->generateData(
            $this::PAYSERA_EMPTY_VALUE,
            $this::PAYSERA_MARKETPLACE_EXTENSIONS
        );
        $data['callback'] = HTTP_CATALOG . $this::PAYSERA_CALLBACK_URL;

        foreach ($this->getPayseraFieldsName() as $fieldName) {
            $data[$fieldName] = $this->generateConfigField($fieldName);
        }

        $this->validateProject($this->config->get('payment_paysera_project'));

        $countries = $this->getCountries();

        if (count($countries) > 0) {
            $data['paysera_countries'] = $countries;

            if (is_array($data['payment_paysera_category'])) {
                $data['paysera_selected_countries'] = [];

                foreach ($data['payment_paysera_category'] as $isoCode) {
                    $data['paysera_selected_countries'][$isoCode] = $data['paysera_countries'][$isoCode];
                }
            }
        }

        $orderStatusRepository = $this->orderStatusRepositoryFactory->createInstance();
        $data['order_statuses'] = $orderStatusRepository->findAll();

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->document->addStyle('view/stylesheet/paysera/backoffice.css');
        $this->document->addScript('view/javascript/paysera/backoffice.js');

        $this->responseOutput($this::PAYSERA_EXTENSION_PAYMENT_TEMPLATE, $data);
    }

    public function about(): void
    {
        $this->messageRepository->loadLanguagePack(self::LANGUAGE_ABOUT);

        $this->document->setTitle($this->messageRepository->get('heading_title'));

        $this->responseOutput(self::PAYSERA_EXTENSION_ABOUT, []);
    }

    public function no_delivery_plugin(): void
    {
        $this->messageRepository->loadLanguagePack(self::LANGUAGE_NO_PLUGIN);

        $this->document->setTitle($this->messageRepository->get('heading_title'));

        $this->responseOutput(self::PAYSERA_EXTENSION_NO_DELIVERY_PLUGIN, []);
    }

    /**
     * @return bool
     * @throws IncorrectValidationRuleStructure
     */
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', $this::PAYSERA_EXTENSION_PAYMENT)) {
            $this->error[self::PAYSERA_ERROR_PREFIX . 'warning'] = $this->language->get('error_warning');
        } else {
            $pluginSettingsValidator = new PluginSettingsValidator(
                $this->orderStatusRepositoryFactory->createInstance(),
                new MessageRepository($this->registry)
            );

            $rules = [
                'payment_paysera_project' => 'required',
                'payment_paysera_sign' => 'required',
                'payment_paysera_new_order_status_id' => 'entity-exists',
                'payment_paysera_paid_status_id' => 'entity-exists',
                'payment_paysera_pending_status_id' => 'entity-exists',
            ];

            $validationResult = $pluginSettingsValidator->validate(
                $this->request->post,
                $rules
            );
            if (!$validationResult) {
                $validationErrors = $pluginSettingsValidator->getProcessedErrors();
                foreach ($validationErrors as $field => $fieldErrors) {
                    $this->error[self::PAYSERA_ERROR_PREFIX . $field] = $fieldErrors;
                }
            }
        }

        return ! count($this->error);
    }


    /**
     * @return array
     */
    private function getCountries()
    {
        $countries = [];

        $this->load->model('localisation/country');

        foreach ($this->model_localisation_country->getCountries() as $country) {
            $countries[strtolower($country['iso_code_2'])] = $country['name'];
        }

        return $countries;
    }

    private function errorValue(string $fieldName): string
    {
        return $this->error[$fieldName] ?? self::PAYSERA_EMPTY_VALUE;
    }

    /**
     * @param string $text
     * @param string $path
     *
     * @return array
     */
    private function generateData($text, $path)
    {
        if ($path == $this::PAYSERA_MARKETPLACE_EXTENSIONS) {
            $tokenParam = $this::PAYSERA_EMPTY_VALUE;
        } else {
            $tokenParam = $this::PAYSERA_TYPE_PAYMENT;
        }
        $token = $this::PAYSERA_TOKEN_PARAM . $this->session->data['user_token'] . $tokenParam;

        if (empty($text)) {
            $data = $this->url->link($path, $token, true);
        } elseif (empty($path)) {
            $data = $this->language->get($text);
        } else {
            $data = array(
                'text' => $this->language->get($text),
                'href' => $this->url->link($path, $token, true)
            );
        }

        return $data;
    }

    /**
     * @param string $fieldName
     *
     * @return mixed
     */
    private function generateConfigField($fieldName)
    {
        if (isset($this->request->post[$fieldName])) {
            $data = $this->request->post[$fieldName];
        } else {
            $data = $this->config->get($fieldName);
        }

        return $data;
    }

    /**
     * @return array
     */
    private function getErrorFieldName()
    {
        return $this->errorFieldName;
    }

    /**
     * @return array
     */
    private function getPayseraFieldsName()
    {
        return $this->payseraFieldsName;
    }

    /**
     * @param string $projectID
     */
    private function validateProject($projectID)
    {
        if (empty($projectID)) {
            $result = $this::PAYSERA_DEFAULT_PROJECT_ID;
        } else {
            $result = $projectID;
        }

        $this->setProjectID($result);
    }

    /**
     * @return int
     */
    private function getProjectID()
    {
        return $this->projectID;
    }

    /**
     * @param int $projectID
     */
    private function setProjectID($projectID)
    {
        $this->projectID = $projectID;
    }

    /**
     * @return array
     */
    public function getBreadcrumbFields()
    {
        return $this->breadcrumbFields;
    }

    public function payseraPaymentMenuHandler(string $eventRoute, array &$data): void
    {
        $this->messageRepository->loadLanguagePack('extension/payment/paysera');

        $payseraMenu = array_filter($data['menus'], function ($menu) {
            return $menu['id'] === 'menu-paysera';
        });

        if (!count($payseraMenu)) {
            $data['menus'][] = [
                'id' => 'menu-paysera',
                'name' => sprintf(
                    $this->messageRepository->get('menu_parent'),
                    '<img src="view/image/payment/paysera/paysera_menu_icon.svg" alt="paysera-logo" style="display: inline-block; margin-left: -27px; margin-right: 7px; width: 16px"/>'
                ),
                'href' => '',
                'children' => [
                    'menu-paysera-about' => [
                        'name' => $this->messageRepository->get('menu_about'),
                        'href' => $this->getUrl(self::PAYSERA_EXTENSION_ABOUT),
                        'children' => [],
                    ],
                    'menu-paysera-delivery-settings' => [
                        'name' => $this->messageRepository->get('menu_delivery_settings'),
                        'href' => $this->getUrl(self::PAYSERA_EXTENSION_NO_DELIVERY_PLUGIN),
                        'children' => [],
                    ],
                    'menu-paysera-payment-settings' => [
                        'name' => $this->messageRepository->get('menu_payment_settings'),
                        'href' => $this->getUrl(self::PAYSERA_EXTENSION),
                        'children' => [],
                    ],
                ],
            ];
        } else {
            $payseraMenuIndex = array_key_first($payseraMenu);
            $data['menus'][$payseraMenuIndex]['children']['menu-paysera-delivery-settings'] = [
                'name' => $this->messageRepository->get('menu_delivery_settings'),
                'href' => $this->getUrl(self::PAYSERA_EXTENSION),
                'children' => [],
            ];
        }
    }

    public function install() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode($this::PAYSERA_EVENT_MENU_NAME);
        $this->model_setting_event->deleteEventByCode($this::PAYSERA_EVENT_HEADER_NAME);
        $this->model_setting_event->deleteEventByCode($this::PAYSERA_EVENT_FOOTER_NAME);
        $this->model_setting_event->addEvent(
            self::PAYSERA_EVENT_MENU_NAME,
            self::PAYSERA_EVENT_MENU,
            self::PAYSERA_MENU_CONTROLLER
        );
        $this->model_setting_event->addEvent(
            $this::PAYSERA_EVENT_HEADER_NAME,
            $this::PAYSERA_EVENT_HEADER,
            $this::PAYSERA_HEADER_CONTROLER)
        ;
        $this->model_setting_event->addEvent(
            $this::PAYSERA_EVENT_FOOTER_NAME,
            $this::PAYSERA_EVENT_FOOTER,
            $this::PAYSERA_FOOTER_CONTROLER)
        ;
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting(
            $this::PAYSERA_PAYMENT,
            [
                'payment_paysera_display_payments_list' => true,
                'payment_paysera_new_order_status_id' => 2,
                'payment_paysera_paid_status_id' => 5,
                'payment_paysera_pending_status_id' => 1,
            ]
        );
    }

    public function uninstall() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode($this::PAYSERA_EVENT_MENU_NAME);
        $this->model_setting_event->deleteEventByCode($this::PAYSERA_EVENT_HEADER_NAME);
        $this->model_setting_event->deleteEventByCode($this::PAYSERA_EVENT_FOOTER_NAME);
    }

    /**
     * @param string $route
     * @param array $args
     * @return array
     */
    private function getUrl($route, $args = [])
    {
        return $this->generateData(self::PAYSERA_EMPTY_VALUE, $route, $args);
    }

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    private function responseOutput($template, $data)
    {
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $this->response->setOutput($this->load->view($template, $data));
    }
}
