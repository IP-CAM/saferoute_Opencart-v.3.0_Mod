<?php

class ModelExtensionShippingSaferoute extends Model
{
    const PICKUP  = 1;
    const COURIER = 2;
    const POST    = 3;

    /**
     * @param $data array
     * @param $order_id int|string
     * @return array
     */
    public function enrichData(array $data, $order_id)
    {
        $order = $this->getData($order_id);
        if (!$order) return $data;

        $data['saferouteDeliveryType'] = (!empty($order->row['saferoute_delivery_type']))
            ? $this->mapDeliveryType((int) $order->row['saferoute_delivery_type'])
            : '';

        $data['saferouteDeliveryCompany'] = (!empty($order->row['saferoute_delivery_company']))
            ? $order->row['saferoute_delivery_company']
            : '';

        return $data;
    }

    /**
     * @param $code int
     * @return string
     */
    public function mapDeliveryType($code)
    {
        $delivery_type_titles = [
            self::PICKUP  => 'Пункт выдачи',
            self::COURIER => 'Курьерская доставка',
            self::POST    => 'Почта РФ',
        ];

        return (array_key_exists($code, $delivery_type_titles))
            ? $delivery_type_titles[$code]
            : '';
    }

    /**
     * @param $order_id int|string
     * @return mixed
     */
    public function getData($order_id)
    {
        $query = $this->db->query(
            "SELECT saferoute_delivery_type, saferoute_delivery_company FROM `" . DB_PREFIX . "order` WHERE order_id = '" . $order_id . "'"
        );

        return $query->num_rows ? $query : false;
    }
}
