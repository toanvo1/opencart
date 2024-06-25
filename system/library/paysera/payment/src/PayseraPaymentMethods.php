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
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Paysera <plugins@paysera.com>
 * @copyright 2018 Paysera
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Paysera
 */

namespace Paysera\Payment;

use Paysera\Payment\PayseraHtmlForm;use WebToPay;use WebToPayException;

if (!class_exists('Paysera\Payment\PayseraHtmlForm')) {
    require_once 'PayseraHtmlForm.php';
}

/**
 * Build Paysera payment methods list
 */
class PayseraPaymentMethods
{
    /**
     * Code used for empty fields
     */
    const EMPTY_CODE = '';

    /**
     * HTML NewLine break
     */
    const LINE_BREAK = '<div style="clear:both"><br /></div>';

    /**
     * Min. number of countries in list
     */
    const COUNTRY_SELECT_MIN = 1;

    /**
     * Default language if not in the list
     */
    const DEFAULT_LANG = 'en';

    /**
     * Default bool answer
     */
    const DEFAULT_ANSWER = false;

    /**
     * Default total
     */
    const DEFAULT_TOTAL = 0;

    /**
     * Default currency
     */
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * Default project id
     */
    const DEFAULT_PROJECT_ID = 0;

    /**
     * @var int
     */
    protected $projectID;

    /**
     * @var string
     */
    protected $billingCountry;

    /**
     * @var string
     */
    protected $lang;

    /**
     * @var boolean
     */
    protected $displayList;

    /**
     * @var array
     */
    protected $countriesSelected;

    /**
     * @var boolean
     */
    protected $gridView;

    /**
     * @var boolean
     */
    protected $buyerConsent;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var double
     */
    protected $cartTotal;

    /**
     * @var string
     */
    protected $cartCurrency;

    /**
     * Available languages of payments
     */
    protected $availableLang;

    /**
     * @var array
     */
    protected $buyerConsentTranslations;

    /**
     * @return self
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Wc_Paysera_Payment_Methods constructor
     */
    public function __construct()
    {
        $this->projectID = $this::DEFAULT_PROJECT_ID;
        $this->lang = $this::DEFAULT_LANG;
        $this->billingCountry = $this::EMPTY_CODE;
        $this->displayList = $this::DEFAULT_ANSWER;
        $this->countriesSelected = $this::EMPTY_CODE;
        $this->gridView = $this::DEFAULT_ANSWER;
        $this->description = $this::DEFAULT_ANSWER;
        $this->cartTotal = $this::DEFAULT_TOTAL;
        $this->cartCurrency = $this::DEFAULT_CURRENCY;
    }

    /**
     * @param boolean [Optional] $print
     *
     * @return boolean|string
     */
    public function build($print = true)
    {
        $buildHtml = PayseraHtmlForm::create();

        $projectID = $this->getProjectID();
        if (empty($projectID)) {
            $this->setDescription('CONFIG ERROR: edit Paysera plugin configuration');
        }

        if ($this->isDisplayList() && !empty($projectID)) {
            $payseraCountries = $this->getPayseraCountries(
                $this->getProjectID(),
                $this->getCartTotal(),
                $this->getCartCurrency(),
                $this->listLang()
            );

            $countries = $this->getCountriesList($payseraCountries);

            if (count($countries) > $this::COUNTRY_SELECT_MIN) {
                $paymentsHtml = $buildHtml->buildCountriesList(
                    $countries,
                    $this->getBillingCountry()
                );
                $paymentsHtml .= $this::LINE_BREAK;
            } else {
                $paymentsHtml = $this::EMPTY_CODE;
            }

            $paymentsHtml .= $buildHtml->buildPaymentsList(
                $countries,
                $this->isGridView(),
                $this->getBillingCountry()
            );
            $paymentsHtml .= $this::LINE_BREAK;
        } else {
            $paymentsHtml = $this->getDescription();
        }

        $translations = $this->getBuyerConsentTranslations();

        if ($this->isBuyerConsent()) {
            $paymentsHtml .= $this::LINE_BREAK;
            $paymentsHtml .= sprintf(
                $translations['buyer_consent_text'],
                '<a href="' . $translations['buyer_consent_link'] . '"> ' . $translations['buyer_consent_rules'] . '</a>'
            );
            $paymentsHtml .= $this::LINE_BREAK;
        }

        if ($print) {
            print_r($paymentsHtml);
            return $print;
        } else {
            return $paymentsHtml;
        }
    }

    /**
     * @param integer $project
     * @param float $amount
     * @param string $currency
     * @param string $lang
     *
     * @return array
     */
    public function getPayseraCountries($project, $amount, $currency, $lang)
    {
        try {
            $countries = WebToPay::getPaymentMethodList($project, $amount, $currency)
                ->setDefaultLanguage($lang)
                ->getCountries();
        } catch (WebToPayException $exception) {
            return [];
        }

        return $countries;
    }

    /**
     * @param array $countries
     *
     * @return array
     */
    public function getCountriesList($countries)
    {
        $countriesList = array();
        $selectedCountriesCodes = $this->getCountriesSelected();

        foreach ($countries as $country) {
            $checkForCountry = true;
            if ($selectedCountriesCodes[0]) {
                $checkForCountry = in_array($country->getCode(), $selectedCountriesCodes);
            }

            if ($checkForCountry) {
                $countriesList[] = array(
                    'code' => $country->getCode(),
                    'title' => $country->getTitle(),
                    'groups' => $country->getGroups()
                );
            }
        }

        if (count($countriesList) === 0 && array_key_exists('other', $countries)) {
            $other = $countries['other'];
            $countriesList[] = [
                'code' => $other->getCode(),
                'title' => $other->getTitle(),
                'groups' => $other->getGroups(),
            ];
        }

        return $countriesList;
    }

    /**
     * @return string
     */
    protected function listLang()
    {
        if (in_array($this->getLang(), $this->getAvailableLang())) {
            $listLang = $this->getLang();
        } else {
            $listLang = $this::DEFAULT_LANG;
        }

        return $listLang;
    }

    /**
     * @return string
     */
    public function getBillingCountry()
    {
        return $this->billingCountry;
    }


    /**
     * @param string $billingCountry
     *
     * @return self
     */
    public function setBillingCountry($billingCountry)
    {
        $this->billingCountry = $billingCountry;
        return $this;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     *
     * @return self
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDisplayList()
    {
        return $this->displayList;
    }

    /**
     * @param boolean $displayList
     *
     * @return self
     */
    public function setDisplayList($displayList)
    {
        $this->displayList = $displayList;
        return $this;
    }

    /**
     * @return array
     */
    public function getCountriesSelected()
    {
        return $this->countriesSelected;
    }

    /**
     * @param array|string $countriesSelected
     *
     * @return self
     */
    public function setCountriesSelected($countriesSelected)
    {
        if (!is_array($countriesSelected)) {
            $countriesSelected = array($countriesSelected);
        }
        $this->countriesSelected = $countriesSelected;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isGridView()
    {
        return $this->gridView;
    }

    /**
     * @param boolean $gridView
     *
     * @return self
     */
    public function setGridView($gridView)
    {
        $this->gridView = $gridView;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isBuyerConsent()
    {
        return $this->buyerConsent;
    }

    /**
     * @param boolean $buyerConsent
     *
     * @return self
     */
    public function setBuyerConsent($buyerConsent)
    {
        $this->buyerConsent = $buyerConsent;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return double
     */
    public function getCartTotal()
    {
        return $this->cartTotal;
    }

    /**
     * @param double $cartTotal
     *
     * @return self
     */
    public function setCartTotal($cartTotal)
    {
        $this->cartTotal = $cartTotal;
        return $this;
    }

    /**
     * @return string
     */
    public function getCartCurrency()
    {
        return $this->cartCurrency;
    }

    /**
     * @param string $cartCurrency
     *
     * @return self
     */
    public function setCartCurrency($cartCurrency)
    {
        $this->cartCurrency = $cartCurrency;
        return $this;
    }

    /**
     * @return int
     */
    public function getProjectID()
    {
        return $this->projectID;
    }

    /**
     * @param int $projectID
     *
     * @return self
     */
    public function setProjectID($projectID)
    {
        $this->projectID = $projectID;
        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableLang()
    {
        return $this->availableLang;
    }

    /**
     * @param array $availableLang
     *
     * @return self
     */
    public function setAvailableLang($availableLang)
    {
        $this->availableLang = $availableLang;
        return $this;
    }

    /**
     * @return array
     */
    public function getBuyerConsentTranslations()
    {
        return $this->buyerConsentTranslations;
    }

    /**
     * @param array $buyerConsentTranslations
     *
     * @return self
     */
    public function setBuyerConsentTranslations($buyerConsentTranslations)
    {
        $this->buyerConsentTranslations = $buyerConsentTranslations;
        return $this;
    }
}
