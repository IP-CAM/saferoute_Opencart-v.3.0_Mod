<?php

require('saferoute_data/DbModificator.php');

class ControllerExtensionShippingSaferoute extends Controller
{
    private $error = array();

    public function install()
    {
        (new DbModificator($this))->install();

        $this->load->language('extension/extension/shipping');

        $this->load->model('setting/extension');
        $this->model_setting_extension->install('shipping', $this->request->get['extension']);

        $this->load->model('setting/event');
        $this->model_setting_event->addEvent(
            'add_sr_data_on_order_create',
            'catalog/model/checkout/order/addOrderHistory/before',
            'catalog/model/extension/shipping/saferoute/onOrderCheckoutSuccess'
        );
    }

    public function uninstall()
    {
        (new DbModificator($this))->uninstall();
    }

    public function index()
    {
        $this->load->language('extension/shipping/saferoute');
        $this->load->model('setting/setting');
        $this->load->model('localisation/geo_zone');

        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate())
        {
            $this->model_setting_setting->editSetting('shipping_saferoute', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect(
                $this->url->link(
                    'marketplace/extension',
                    'user_token=' . $this->session->data['user_token'] . '&type=shipping',
                    true
                )
            );
        }

        $this->document->setTitle($this->language->get('heading_title'));

        $data = [];

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit']        = $this->language->get('text_edit');
        $data['text_enabled']     = $this->language->get('text_enabled');
        $data['text_disabled']    = $this->language->get('text_disabled');
        $data['text_all_zones']   = $this->language->get('text_all_zones');

        $data['entry_geo_zone']   = $this->language->get('entry_geo_zone');
        $data['entry_status']     = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_token']      = $this->language->get('entry_token');
        $data['entry_shop_id']    = $this->language->get('entry_shop_id');

        $data['button_save']      = $this->language->get('button_save');
        $data['button_cancel']    = $this->language->get('button_cancel');
        $data['error_warning']    = isset($this->error['warning']) ? $this->error['warning'] : '';


        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ],
            [
                'text' => $this->language->get( 'text_extensions'),
                'href' => $this->url->link(
                   'marketplace/extension',
                    'user_token=' . $this->session->data['user_token'] . '&type=shipping',
                    true
                ),
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link(
                   'extension/shipping/saferoute',
                    'user_token=' . $this->session->data['user_token'],
                    true
                ),
            ],
        ];

        $data['action'] = $this->url->link(
            'extension/shipping/saferoute',
            'user_token=' . $this->session->data['user_token'],
            true
        );
        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=shipping',
            true
        );
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['shipping_saferoute_status'])) {
            $data['shipping_saferoute_status'] = $this->request->post['shipping_saferoute_status'];
        } else {
            $data['shipping_saferoute_status'] = $this->config->get('shipping_saferoute_status');
        }


        $data['shipping_saferoute_token'] = isset($this->request->post['shipping_saferoute_token'])
            ? $this->request->post['shipping_saferoute_token']
            : $this->config->get('shipping_saferoute_token');

        $data['shipping_saferoute_shop_id'] = isset($this->request->post['shipping_saferoute_shop_id'])
            ? $this->request->post['shipping_saferoute_shop_id']
            : $this->config->get('shipping_saferoute_shop_id');

        $data['shipping_saferoute_status'] = isset($this->request->post['shipping_saferoute_status'])
            ? $this->request->post['shipping_saferoute_status']
            : $this->config->get('shipping_saferoute_status');

        $data['shipping_saferoute_sort_order'] = isset($this->request->post['shipping_saferoute_sort_order'])
            ? $this->request->post['shipping_saferoute_sort_order']
            : $this->config->get('shipping_saferoute_sort_order');


        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');


        $this->response->setOutput(
            $this->load->view('extension/shipping/saferoute', $data)
        );
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify',  'extension/shipping/saferoute'))
            $this->error['warning'] = $this->language->get('error_permission');

        return !$this->error;
    }
}