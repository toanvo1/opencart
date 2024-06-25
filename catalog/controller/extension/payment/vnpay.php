<?php
class ControllerExtensionPaymentVnpay extends Controller
{
    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['action'] = $this->url->link('extension/payment/vnpay/checkout', '', true);

        return $this->load->view('extension/payment/vnpay', $data);
    }

    public function checkout()
    {
        // Xử lý thanh toán và chuyển hướng đến VNPay
    }
}
?>