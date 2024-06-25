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

use Paysera\Payment\PayseraPaymentMethods;

/**
 * Class ControllerExtensionPaymentPaysera
 */
class ControllerExtensionPaymentPaysera extends Controller
{
    /**
     * Plugin version
     */
    const PLUGIN_VERSION = '2.1.5';

    /**
     * Default code value
     */
    const CODE = 'code';

    /**
     * Certificate
     */
    const SSL = 'SSL';

    /**
     * Empty value
     */
    const EMPTY_CODE = '';

    /**
     * New int value
     */
    const NEW_VALUE = 1;

    /**
     * Decimal places
     */
    const DECIMALS_ROUND = 2;

    /**
     * Paysera project config name
     */
    const CONFIG_PAYSERA_PROJECT = 'payment_paysera_project';

    /**
     * Paysera project sign config name
     */
    const CONFIG_PAYSERA_PASS = 'payment_paysera_sign';

    /**
     * Paysera test config name
     */
    const CONFIG_PAYSERA_TEST = 'payment_paysera_test';

    /**
     * Paysera description config name
     */
    const CONFIG_PAYSERA_DESCRIPTION = 'payment_paysera_description';

    /**
     * Paysera list config name
     */
    const CONFIG_PAYSERA_LIST = 'payment_paysera_display_payments_list';

    /**
     * Paysera category config name
     */
    const CONFIG_PAYSERA_CATEGORY = 'payment_paysera_category';

    /**
     * Paysera gridview config name
     */
    const CONFIG_PAYSERA_GRID = 'payment_paysera_grid_view';

    /**
     * Paysera buyer consent config name
     */
    const CONFIG_PAYSERA_BUYER_CONSNET = 'payment_paysera_buyer_consent';

    /**
     * Paysera new order config name
     */
    const CONFIG_NEW_STATUS = 'payment_paysera_new_order_status_id';

    /**
     * Paysera pending order config name
     */
    const CONFIG_PENDING_STATUS = 'payment_paysera_pending_status_id';

    /**
     * Paysera paid order config name
     */
    const CONFIG_PAID_STATUS = 'payment_paysera_paid_status_id';

    /**
     * Paysera quality config name
     */
    const CONFIG_QUALITY = 'payment_paysera_quality';

    /**
     * Paysera owner config name
     */
    const CONFIG_OWNER = 'payment_paysera_owner';

    /**
     * Paysera owner code config name
     */
    const CONFIG_OWNER_CODE = 'payment_paysera_owner_code';

    /**
     * Display errors setter
     */
    const DISPLAY_ERROR = 'display_errors';

    /**
     * Template config name
     */
    const TEMPLATE_NAME = 'config_template';

    /**
     * Store config name
     */
    const STORE_NAME = 'config_store';

    /**
     * Paysera template function
     */
    const PAYSERA_TEMPLATE = '/template/extension/payment/paysera';

    /**
     * Paysera function
     */
    const PAYSERA_EXTENSION = 'extension/payment/paysera';

    /**
     * Paysera owner view
     */
    const PAYSERA_OWNER = 'extension/payment/paysera_owner';

    /**
     * Paysera quality sign view
     */
    const PAYSERA_QUALITY = 'extension/payment/paysera_quality';

    /**
     * accepturl
     */
    const ACCEPT_URL = 'index.php?route=extension/payment/paysera/accept';

    /**
     * cancelurl
     */
    const CANCEL_URL = 'index.php?route=extension/payment/paysera/cancel';

    /**
     * callbackurl
     */
    const CALLBACK_URL = 'index.php?route=extension/payment/paysera/callback';

    /**
     * Paysera confirm order
     */
    const EXTENSION_CONFIRM = 'extension/payment/paysera/confirm';


    /**
     * Guest
     */
    const PAYSERA_GUEST = 'checkout/guest/confirm';

    /**
     * Checkout page
     */
    const CHECKOUT_PAYMENT = 'index.php?route=checkout/payment';

    /**
     * Guest checkout
     */
    const CHECKOUT_GUEST = 'index.php?route=checkout/guest';

    /**
     * @var array
     */
    private $availableLang = array('lt', 'lv', 'ru', 'en', 'pl', 'bg', 'ee');

    /**
     * @var array
     */
    private $buyerConsentTranslations = array(
        'buyer_consent_text',
        'buyer_consent_link',
        'buyer_consent_rules',
    );

    /**
     * @return mixed
     */
    public function index()
    {
        $this->load->language($this::PAYSERA_EXTENSION);

        $buyerConsentTranslations = array();

        foreach ($this->getBuyerConsentTranslations() as $translation) {
            $buyerConsentTranslations[$translation] = $this->language->get($translation);
        }

        $data['action'] = $this->url->link($this::EXTENSION_CONFIRM, $this::EMPTY_CODE, $this::SSL);

        if ($this->request->get['route'] != $this::PAYSERA_GUEST) {
            $data['back'] = HTTPS_SERVER . $this::CHECKOUT_PAYMENT;
        } else {
            $data['back'] = HTTPS_SERVER . $this::CHECKOUT_GUEST;
        }

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder(
            $this->session->data['order_id']
        );

        $projectID             = $this->config->get($this::CONFIG_PAYSERA_PROJECT);
        $displayPaymentMethods = $this->config->get($this::CONFIG_PAYSERA_LIST);
        $selectedCountries     = $this->config->get($this::CONFIG_PAYSERA_CATEGORY);
        $gridView              = $this->config->get($this::CONFIG_PAYSERA_GRID);
        $buyerConsent          = $this->config->get($this::CONFIG_PAYSERA_BUYER_CONSNET);
        $description           = $this->config->get($this::CONFIG_PAYSERA_DESCRIPTION);
        $langISO               = $this->language->get($this::CODE);
        $country               = strtolower($order['payment_iso_code_2']);
        $cartTotal             = $this->getAmountInCents($order['total'] , $order['currency_code']);
        $currency              = $order['currency_code'];
        $availableLang         = $this->getAvailableLang();

        $additionalInfo = PayseraPaymentMethods::create()
            ->setProjectID($projectID)
            ->setLang($langISO)
            ->setBillingCountry($country)
            ->setDisplayList($displayPaymentMethods)
            ->setCountriesSelected($selectedCountries)
            ->setGridView($gridView)
            ->setBuyerConsent($buyerConsent)
            ->setDescription($description)
            ->setCartTotal($cartTotal)
            ->setCartCurrency($currency)
            ->setAvailableLang($availableLang)
            ->setBuyerConsentTranslations($buyerConsentTranslations)
        ;

        $data['payment_methods'] = $additionalInfo->build(false);

        if (file_exists(DIR_TEMPLATE . $this->config->get($this::TEMPLATE_NAME) . $this::PAYSERA_TEMPLATE)) {
            return $this->load->view($this->config->get($this::TEMPLATE_NAME) . $this::PAYSERA_TEMPLATE, $data);
        } else {
            return $this->load->view($this::PAYSERA_EXTENSION, $data);
        }
    }

    public function confirm()
    {
        error_reporting(E_ALL);
        ini_set($this::DISPLAY_ERROR, $this::NEW_VALUE);

        $this->load->model('checkout/order');

        $orderID = $this->session->data['order_id'];
        $order = $this->model_checkout_order->getOrder($orderID);
        $this->model_checkout_order->addOrderHistory($orderID, $this->config->get($this::CONFIG_PENDING_STATUS));

        if (!isset($_SERVER['HTTPS'])) {
            $_SERVER['HTTPS'] = false;
        }

        $langISO = $this->language->get($this::CODE);
        $lang    = $this->getPayseraLangCode($langISO);

        if (isset($_REQUEST['paysera_payment_method'])) {
            $payment = $_REQUEST['paysera_payment_method'];
        } else {
            $payment = $this::EMPTY_CODE;
        }

        $paymentData = array(
            'projectid'      => $this->config->get($this::CONFIG_PAYSERA_PROJECT),
            'sign_password'  => $this->config->get($this::CONFIG_PAYSERA_PASS),
            'orderid'        => $order['order_id'],
            'amount'         => $this->getAmountInCents($order['total'] , $order['currency_code']),
            'currency'       => $order['currency_code'],
            'accepturl'      => HTTPS_SERVER . $this::ACCEPT_URL,
            'cancelurl'      => HTTPS_SERVER . $this::CANCEL_URL,
            'callbackurl'    => HTTPS_SERVER . $this::CALLBACK_URL,
            'payment'        => $payment,
            'country'        => $order['payment_iso_code_2'],
            'lang'           => $lang,
            'p_firstname'    => $order['payment_firstname'],
            'p_lastname'     => $order['payment_lastname'],
            'p_email'        => $order['email'],
            'p_street'       => $order['payment_address_1'] . ' ' . $order['payment_address_2'],
            'p_city'         => $order['payment_city'],
            'p_zip'          => $order['payment_postcode'],
            'p_countrycode'  => $order['payment_iso_code_2'],
            'test'           => (int)$this->config->get($this::CONFIG_PAYSERA_TEST),
            'buyer_consent'  => (int) $this->config->get($this::CONFIG_PAYSERA_BUYER_CONSNET),
            'plugin_name'    => 'OpenCart',
            'plugin_version' => $this::PLUGIN_VERSION,
            'php_version'    => phpversion(),
            'cms_version'    => VERSION,
        );

        try {
            WebToPay::redirectToPayment($paymentData, true);
        } catch (WebToPayException $e) {
            exit($e->getMessage());
        }
    }

    public function cancel()
    {
        $this->clearSessionParameters();
        $this->response->redirect($this->url->link('checkout/cart', $this::EMPTY_CODE, true));
    }

    protected function clearSessionParameters(): void
    {
        $checkoutSessionParameters = [
            'order_id',
            'payment_address',
            'payment_method',
            'payment_methods',
            'shipping_address',
            'shipping_method',
            'shipping_methods',
            'comment',
            'coupon',
            'reward',
            'voucher',
            'vouchers',
        ];

        foreach ($checkoutSessionParameters as $checkoutSessionParameter) {
            unset($this->session->data[$checkoutSessionParameter]);
        }
    }

    public function accept()
    {
        $projectID = $this->config->get($this::CONFIG_PAYSERA_PROJECT);
        $signPass  = $this->config->get($this::CONFIG_PAYSERA_PASS);
        $response = WebToPay::validateAndParseData($_REQUEST, $projectID, $signPass);

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($response['orderid']);

        $currentStatus = $order['order_status_id'];
        $this->session->data['order_status_id'] = $currentStatus;

        $paidStatus    = $this->config->get($this::CONFIG_PAID_STATUS);
        $newStatus     = $this->config->get($this::CONFIG_NEW_STATUS);
        if ($currentStatus !== $paidStatus) {
            $this->model_checkout_order->addOrderHistory(
                $response['orderid'], $newStatus
            );
            $this->session->data['order_status_id'] = $newStatus;
        }

        $this->response->redirect($this->url->link('checkout/success', $this::EMPTY_CODE, true));
    }

    public function callback()
    {
        $projectID = $this->config->get($this::CONFIG_PAYSERA_PROJECT);
        $signPass  = $this->config->get($this::CONFIG_PAYSERA_PASS);

        try {
            $response = WebToPay::validateAndParseData($_REQUEST, $projectID, $signPass);

            if ($response['status'] == 1) {
                $orderId = isset($response['orderid']) ? $response['orderid'] : null;

                $this->load->model('checkout/order');
                $order = $this->model_checkout_order->getOrder($orderId);
                if (empty($order)) {
                    throw new Exception('Order with this ID not found');
                }

                $amount = $this->getAmountInCents($order['total'], $order['currency_code']);
                if ($response['amount'] != $amount
                    || $response['currency'] != $order['currency_code']) {
                    $checkConvert = array_key_exists('payamount', $response);
                    if (!$checkConvert) {
                        throw new Exception(
                            'Wrong pay amount: ' . $response['amount'] / 100 . $response['currency']
                            . ', expected: ' . $amount / 100 . $order['currency_code']
                        );
                    } elseif ($response['payamount'] != $amount
                        || $response['paycurrency'] != $order['currency_code']) {
                        throw new Exception(
                            'Wrong pay amount: ' . $response['payamount'] / 100 . $response['paycurrency']
                            . ', expected: ' . $amount / 100 . $order['currency_code']
                        );
                    }
                }

                $paidOrder = $this->config->get($this::CONFIG_PAID_STATUS);
                $this->model_checkout_order->addOrderHistory($orderId, $paidOrder);

                exit('OK');
            }
        } catch (Exception $e) {
            exit(get_class($e) . ': ' . $e->getMessage());
        }
    }

    public function paysera_header(&$route, &$data, &$output)
    {
        $values['paysera_code'] = $this->config->get($this::CONFIG_OWNER_CODE);

        if ((bool) $this->config->get($this::CONFIG_OWNER)) {
            $data['analytics'][] = $this->load->view($this::PAYSERA_OWNER, $values, true);
        }
    }

    public function paysera_footer(&$route, &$data, &$output)
    {
        $this->load->language($this::PAYSERA_EXTENSION);

        $values['paysera_project'] = $this->config->get($this::CONFIG_PAYSERA_PROJECT);
        $values['paysera_lang']    = $this->language->get($this::CODE);

        if ((bool) $this->config->get($this::CONFIG_QUALITY)) {
            $output = $this->load->view($this::PAYSERA_QUALITY, $values, true) . $output;
        }
    }

    /**
     * @param string $currency
     *
     * @return double
     */
    private function getRate($currency)
    {
        return $this->currency->getvalue($currency);
    }

    /**
     * @param double $total
     * @param string $currency
     *
     * @return int
     */
    private function getAmountInCents($total, $currency)
    {
        return round($total * $this->getRate($currency) * 100);
    }

    /**
     * @return array
     */
    private function getAvailableLang()
    {
        return $this->availableLang;
    }

    /**
     * @return string
     */
    private function getPayseraLangCode($langISO)
    {
        switch ($langISO) {
            case 'lt':
                return 'LIT';
            case 'lv':
                return 'LAV';
            case 'ee':
                return 'EST';
            case 'ru':
                return 'RUS';
            case 'de':
                return 'GER';
            case 'pl':
                return 'POL';
            case 'bg':
                return 'BGR';
            default:
                return 'ENG';
        }
    }

    /**
     * @return array
     */
    private function getBuyerConsentTranslations()
    {
        return $this->buyerConsentTranslations;
    }
}
