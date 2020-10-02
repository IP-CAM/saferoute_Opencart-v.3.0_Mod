<?php

/**
 * API-скрипт виджетов SafeRoute
 * v2.0
 */
class SafeRouteWidgetApi
{
    /**
     * @var string Токен авторизации
     */
    private $token;

    /**
     * @var string|int ID магазина
     */
    private $shopId;

    /**
     * @var array Данные запроса
     */
    private $data;

    /**
     * @var string HTTP-метод запроса
     */
    private $method = 'POST';

    /**
     * Возвращает IP-адрес пользователя
     *
     * @return string IP-адрес
     */
    private function getClientIP()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];

        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @param string $url URL запроса
     * @return bool
     */
    private function isHtmlRequest($url)
    {
        preg_match("/\.html$/", $url, $m);
        return (bool) $m;
    }


    /**
     * @param string $token Токен авторизации
     * @param string|int $shopId ID магазина
     */
    public function __construct($token = null, $shopId = null)
    {
        $this->setToken($token);
        $this->setShopId($shopId);
    }

    /**
     * Сеттер токена авторизации
     *
     * @param string $token Токен авторизации
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Сеттер ID магазина
     *
     * @param string|int $shopId ID магазина
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
    }

    /**
     * Сеттер данных запроса
     *
     * @param array $data Данные запроса
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Сеттер метода запроса
     *
     * @param string $method Метод запроса
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * Отправляет запрос
     *
     * @param string $url URL
     * @param array $headers Дополнительные заголовки запроса
     * @return string
     */
    public function submit($url, $headers = [])
    {
        // Загрузка кода виджета
        if ($this->isHtmlRequest($url)) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);
            curl_close($curl);

            return $response;
        // Запрос к API
        } else {
            $headers[] = 'Content-Type:application/json';
            $headers[] = "Authorization:Bearer $this->token";
            $headers[] = "shop-id:$this->shopId";
            $headers = array_unique($headers);

            if (isset($this->data['ip']) && !$this->data['ip']) {
                $ip = $this->getClientIP();
                if ($ip !== '::1' && $ip !== '127.0.0.1') $this->data['ip'] = $ip;
            }

            if ($this->method === 'GET') {
                $url .= '?' . http_build_query($this->data);
            }
            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);

            if ($this->method === 'POST' || $this->method === 'PUT')
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($this->data));

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $response = json_decode(curl_exec($curl));
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($status === 200)
                return json_encode(['status' => $status, 'data' => $response]);

            return json_encode([
                'status' => $status,
                'code' => isset($response->code) ? $response->code : null,
            ]);
        }
    }
}
