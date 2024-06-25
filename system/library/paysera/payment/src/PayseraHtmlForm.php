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
/**
 * Build HTML code for Paysera Payments
 */
class PayseraHtmlForm
{
    /**
     * Code used for empty fields
     */
    const EMPTY_CODE = '';

    /**
     * Selected field name
     */
    const FIELD_SELECTED = 'selected';

    /**
     * ID of other payments group
     */
    const CODE_OTHER = 'other';

    /**
     * @return self
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @param array $countries
     * @param string $billingCountryCode
     *
     * @return string
     */
    public function buildCountriesList(
        $countries,
        $billingCountryCode
    )
    {
        $defaultLangCode = $this->getDefaultLangCode(
            $countries,
            $billingCountryCode
        );

        $selectionField = '<select id="paysera_country" 
                                   class="payment-country-select" >';

        foreach ($countries as $country) {
            if ($country['code'] == $defaultLangCode) {
                $selected = $this::FIELD_SELECTED;
            } else {
                $selected = $this::EMPTY_CODE;
            }

            $selectionField .= '<option value="'
                . $country['code'] . '" '
                . $selected
                . '>';
            $selectionField .= $country['title'];
            $selectionField .= '</option>';
        }

        $selectionField .= '</select>';

        return $selectionField;
    }

    /**
     * @param array $countries
     * @param boolean $gridViewIsActive
     * @param string $billingCountryCode
     *
     * @return string
     */
    public function buildPaymentsList(
        $countries,
        $gridViewIsActive,
        $billingCountryCode
    )
    {
        $paymentsCode = $this::EMPTY_CODE;

        $defaultLangCode = $this->getDefaultLangCode(
            $countries,
            $billingCountryCode
        );

        foreach ($countries as $country) {
            $paymentsCode .= '<div id="' . $country['code'] . '"';
            if ($gridViewIsActive) {
                $paymentsCode .= ' class="payment-countries paysera-payments grid"';
            } else {
                $paymentsCode .= ' class="payment-countries paysera-payments"';
            }
            $paymentsCode .= ' style="display:';
            if (($country['code'] == $defaultLangCode)) {
                $paymentsCode .= 'block';
            } else {
                $paymentsCode .= 'none';
            }
            $paymentsCode .= '">';

            $paymentsCode .= $this->buildGroupList(
                $country['groups'],
                $country['code']
            );

            $paymentsCode .= '</div>';
        }

        return $paymentsCode;
    }

    /**
     * @param array $methods
     * @param string $countryCode
     *
     * @return string
     */
    protected function buildMethodsList(
        $methods,
        $countryCode
    )
    {
        $paymentMethodCode = $this::EMPTY_CODE;
        foreach ($methods as $method) {
            $paymentMethodCode .= '<div id="' . $method->getKey() . '" class="payment">';

            $paymentMethodCode .= '<label>';
            $paymentMethodCode .= '<input class="rd_pay" ';
            $paymentMethodCode .= 'type="radio" ';
            $paymentMethodCode .= 'rel="r' . $countryCode . $method->getKey() . '" ';
            $paymentMethodCode .= 'name="payment[pay_type]" ';
            $paymentMethodCode .= 'value="' . $method->getKey() . '" /> ';

            $paymentMethodCode .= '<span class="paysera-text">';
            $paymentMethodCode .= $method->getTitle();
            $paymentMethodCode .= '</span>';

            $paymentMethodCode .= '<div class="paysera-image">';
            $paymentMethodCode .= '<img src="' . $method->getLogoUrl() . '" ';
            $paymentMethodCode .= 'alt="' . $method->getTitle() . '" />';
            $paymentMethodCode .= '</div>';

            $paymentMethodCode .= '</label>';
            $paymentMethodCode .= '</div>';
        }

        return $paymentMethodCode;
    }

    /**
     * @param array $groups
     * @param string $countryCode
     *
     * @return string
     */
    protected function buildGroupList(
        $groups,
        $countryCode
    )
    {
        $paymentGroupCode = $this::EMPTY_CODE;
        foreach ($groups as $group) {
            $paymentGroupCode .= '<div class="payment-group-wrapper">';

            $paymentGroupCode .= '<div class="payment-group-title">';
            $paymentGroupCode .= $group->getTitle();
            $paymentGroupCode .= '</div>';

            $paymentGroupCode .= $this->buildMethodsList(
                $group->getPaymentMethods(),
                $countryCode
            );
            $paymentGroupCode .= '</div>';
        }

        return $paymentGroupCode;
    }

    /**
     * @param array $countries
     * @param string $countryCode
     *
     * @return string
     */
    public function getDefaultLangCode(
        $countries,
        $countryCode
    )
    {
        $countryCodes = array();

        foreach ($countries as $country) {
            $countryCodes[] = $country['code'];
        }

        if (in_array($countryCode, $countryCodes)) {
            $defaultLang = $countryCode;
        } elseif (in_array($this::CODE_OTHER, $countryCodes)) {
            $defaultLang = $this::CODE_OTHER;
        } else {
            $defaultLang = isset(reset($countries)['code'])
                ? reset($countries)['code']
                : $this::CODE_OTHER;
        }

        return $defaultLang;
    }
}
