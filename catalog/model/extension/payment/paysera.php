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

class ModelExtensionPaymentPaysera extends Model
{
    /**
     * Paysera module code
     */
    const PAYSERA_CODE = 'paysera';

    /**
     * Paysera module status config
     */
    const CONFIG_PAYSERA_ENABLE = 'payment_paysera_status';

    /**
     * Paysera title config
     */
    const CONFIG_PAYSERA_TITLE = 'payment_paysera_title';

    /**
     * Paysera geo config
     */
    const CONFIG_PAYSERA_GEO = 'payment_paysera_geo_zone_id';

    /**
     * Paysera total config
     */
    const CONFIG_PAYSERA_TOTAL = 'payment_paysera_total';

    /**
     * Paysera payment sort config
     */
    const CONFIG_PAYSERA_SORT = 'payment_paysera_sort_order';

    /**
     * Empty value
     */
    const EMPTY_VAL = '';

    /**
     * Zero value
     */
    const ZERO_VAL = 0;

    /**
     * @param object $address
     * @param double $total
     *
     * @return array
     */
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/paysera');

        if ($this->config->get($this::CONFIG_PAYSERA_ENABLE)) {
            $geoZoneID = (int)$this->config->get($this::CONFIG_PAYSERA_GEO);
            $countryID = (int)$address['country_id'];

            $zoneID    = (int)$address['zone_id'];
            $zones     = implode(',', array($zoneID, $this::ZERO_VAL));

            $query = $this->db->query(
                "SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone"
                . " WHERE geo_zone_id = '" . $geoZoneID
                . "' AND country_id = '" . $countryID
                . "' AND zone_id IN (" . $zones . ")"
            );

            if (
                $this->config->get($this::CONFIG_PAYSERA_TOTAL) > 0
                && $this->config->get($this::CONFIG_PAYSERA_TOTAL) > $total
            ) {
                $status = false;
            } elseif (
            !$this->config->get($this::CONFIG_PAYSERA_GEO)
            ) {
                $status = true;
            } elseif ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        $method_data = array();

        $title = $this->config->get($this::CONFIG_PAYSERA_TITLE);
        if (empty($title)) {
            $title = $this->language->get('text_title');
        }

        $sort = $this->config->get($this::CONFIG_PAYSERA_SORT);

        if ($status) {
            $method_data = array(
                'code'       => 'paysera',
                'title'      => $title,
                'terms'      => $this::EMPTY_VAL,
                'sort_order' => $sort
            );
        }

        return $method_data;
    }
}