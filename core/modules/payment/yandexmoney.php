<?php

/**
 * @connect_module_class_name CYandexMoney
 *
 */
// YandexMoney method implementation
// see also
//                http://money.yandex.ru

class Tools
{
    public static function d($object, $kill = true)
    {
        return (Tools::dieObject($object, $kill));
    }

    public static function p($object)
    {
        return (Tools::dieObject($object, false));
    }

    public static function dieObject($object, $kill = true)
    {
        echo '<xmp style="text-align: left;">';
        print_r($object);
        echo '</xmp><br />';

        if ($kill)
            die('END');

        return $object;
    }
}

class CYandexMoney extends PaymentModule
{
    const YAVERSION = '1.2.1';

    const MODE_NONE = 0;
    const MODE_KASSA = 1;
    const MODE_MONEY = 2;
    const MODE_BILLING = 3;

    public $test_mode;
    private $mode;

    public $kassa_enable;
    public $money_enable;
    public $billing_enable;

    public $orderId;
    public $orderTotal;
    public $userId;

    public $successUrl;
    public $failUrl;

    public $reciver;
    public $formcomment;
    public $short_dest;
    public $writable_targets = 'false';
    public $comment_needed = 'true';
    public $label;
    public $quickpay_form = 'shop';
    public $payment_type = '';
    public $targets;
    public $sum;
    public $comment;
    public $need_fio = 'true';
    public $need_email = 'true';
    public $need_phone = 'true';
    public $need_address = 'true';

    public $shopid;
    public $scid;
    public $password;
    public $status;

    public $method_ym;
    public $method_cards;
    public $method_cash;
    public $method_mobile;
    public $method_wm;
    public $method_ab;
    public $method_sb;
    public $method_pb;
    public $method_ma;
    public $method_qw;
    public $method_qp;

    public $check;

    public $pay_method;

    public $account;
    public $money_password;
    public $money_status;

    public $billing_id;
    public $billing_purpose;
    public $billing_status;

    private $array_payments = array(
        'ym'    => array('PC', '������ �� �������� � ������.�������'),
        'cards' => array('AC', '������ � ������������ ���������� �����'),
        'cash'  => array('GP', '������ ��������� ����� ����� � ���������'),
        'phone' => array('MC', '������ �� ����� ���������� ��������'),
        'wm'    => array('WM', '������ �� �������� � ������� WebMoney'),
        'ab'    => array('AB', '������ ����� �����-����'),
        'sb'    => array('SB', '������ ����� ��������: ������ �� SMS ��� �������� ������'),
        'ma'    => array('MA', '������ ����� MasterPass'),
        'pb'    => array('PB', '������ ����� ��������-���� ��������������'),
        'qw'    => array('QW', '������ ����� QIWI Wallet'),
        'qp'    => array('QP', '������ ����� ������������� ������ (�����.��)')
    );

    public function _initVars()
    {
         $this->title = "YandexMoney";
         $this->description = "YandexMoney (money.yandex.ru).
             <br/> ������ �������� � ������ �������������� ������. ���� ������ ����� ������������ ��� �������������� ������� �������� �������.
             <br/>
             ����� ������������� ���� ��������� �������� ������ � �������������� �������� ���� ������� ������������� ��������, ������������ �� ������ <a href=\"https://money.yandex.ru/doc.xml?id=527132\">https://money.yandex.ru/doc.xml?id=527132</a> (����� � ������������� �������). ���� �� �� ���������� ������� ������������� �������� � ������ ������, �� �� ������ ����� ������������ ��������� � �����-���� �����.
             <br/>������ ������ ".self::YAVERSION;
         $this->sort_order = 0;

         $array_params = array(
             'desc',

             // kassa options
             'kassa_enable',
             'urls', 'testmode',  'shopid', 'scid', 'password', 'status',
             'payment_type_desc',
             'method_ym', 'method_cards', 'method_cash', 'method_phone', 'method_wm', 'method_ab', 'method_sb',
             'method_ma', 'method_pb', 'method_qw','method_qp',
             'check', 'taxes',
             'spacer_one',

             // money options
             'money_enable',
             'account', 'money_password', 'money_status',
             'spacer_two',

             // billing options
             'billing_enable',
             'billing_id', 'billing_purpose', 'billing_status',
         );

        foreach ($array_params as $key => $value) {
            $value2 = 'CONF_PAYMENTMODULE_YM_' . strtoupper($value);
            $this->Settings[] = $value2;
        }

        if (!empty($_POST['ym_method'])) {
            $_SESSION['ym_method'] = $_POST['ym_method'];
        }
        if (!empty($_POST['ym_billing_fio'])) {
            $_SESSION['ym_billing_fio'] = $_POST['ym_billing_fio'];
        }
    }

    private function _realInitVars()
    {
        $array_params = array(
            // kassa options
            'kassa_enable',
            'urls', 'testmode',  'shopid', 'scid', 'password', 'status',
            'method_ym', 'method_cards', 'method_cash', 'method_phone', 'method_wm', 'method_ab', 'method_sb',
            'method_ma', 'method_pb', 'method_qw','method_qp',
            'check', 'taxes',

            // money options
            'money_enable',
            'account', 'money_password', 'money_status',

            // billing options
            'billing_enable',
            'billing_id', 'billing_purpose', 'billing_status',
        );

        foreach ($array_params as $key => $value) {
            $value2 = 'CONF_PAYMENTMODULE_YM_' . strtoupper($value);
            $this->$value = $this->_getSettingValue($value2);
        }

        if ($this->kassa_enable == 1) {
            $this->mode = self::MODE_KASSA;
            $this->money_enable = false;
            $this->billing_enable = false;
        } elseif ($this->money_enable == 1) {
            $this->mode = self::MODE_MONEY;
            $this->billing_enable = false;
        } elseif ($this->billing_enable == 1) {
            $this->mode = self::MODE_BILLING;
        } else {
            $this->mode = self::MODE_NONE;
        }

        $this->test_mode = ($this->_getSettingValue('CONF_PAYMENTMODULE_YM_TESTMODE') == 1);
    }

    public function getMethodsHtml()
    {
        $html = "<br/><b>������ ������:</b><br/><select name=\"ym_method\">";
        if ($this->mode === self::MODE_KASSA) {
            foreach ($this->array_payments as $key => $value) {
                if ($this->_getSettingValue('CONF_PAYMENTMODULE_YM_METHOD_' . strtoupper($key))) {
                    $html .= '<option value="' . $value[0] . '">' . $value[1] . '</option>';
                }
            }
        } elseif ($this->mode === self::MODE_MONEY) {
            foreach (array('ym', 'cards') as $key => $value) {
                if ($this->_getSettingValue('CONF_PAYMENTMODULE_YM_METHOD_' . strtoupper($key))) {
                    $html .= '<option value="' . $value[0] . '">' . $value[1] . '</option>';
                }
            }
        }
        $html .= "</select><br/> <br/>";
        return $html;
    }

    public function getBillingFormHtml()
    {
        $fio = array();
        if (isset($_GET['billingAddressID'])) {
            $addressId = (int)$_GET['billingAddressID'];
            if ($addressId > 0) {
                $billingAddress = regGetAddress($addressId);
                if (!empty($billingAddress)) {
                    if (!empty($billingAddress['last_name']) && $billingAddress['last_name'] != '-') {
                        $fio[] = $billingAddress['last_name'];
                    }
                    if (!empty($billingAddress['first_name']) && $billingAddress['first_name'] != '-') {
                        $fio[] = $billingAddress['first_name'];
                    }
                }
            }
        }

        $html = '<br /><label for="ym-billing-fio">��� �����������</label><br />'
            . '<input type="text" name="ym_billing_fio" id="ym-billing-fio" value="' . htmlspecialchars(implode(' ', $fio)) . '" />'
            . '<div id="ym-billing-fio-error" style="display: none;">������� ������� ��� � �������� �����������</div>'
            . '<br /><br />'
            . '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var form = document.getElementById("MainForm");
            for (var i = 0; i < form.length; i++) {
                if (form[i].className == "btn btn-success") {
                    form[i].addEventListener("click", function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var field = document.getElementById("ym-billing-fio");
                        var error = document.getElementById("ym-billing-fio-error");
                        var parts = field.value.trim().split(/\s+/);
                        if (parts.length == 3) {
                            error.style.display = "none";
                            field.value = parts.join(" ");
                            form.submit();
                        } else {
                            error.style.display = "block";
                        }
                    }, false);
                }
            }
        }, false);
            </script>';
        return $html;
    }

    public function payment_form_html()
    {
        $payment_methods = payGetAllPaymentMethods(true);
        foreach ($payment_methods as $method) {
            /** @var self $currentPaymentModule */
            $currentPaymentModule = modGetModuleObj($method['module_id'], PAYMENT_MODULE);
            if ($_GET['paymentMethodID'] == $method['PID']) {
                if ($currentPaymentModule != null) {
                    $currentPaymentModule->_realInitVars();
                    switch ($currentPaymentModule->mode) {
                        case self::MODE_MONEY:
                        case self::MODE_KASSA:
                            $text .= $currentPaymentModule->getMethodsHtml();
                            break;
                        case self::MODE_BILLING:
                            $text .= $currentPaymentModule->getBillingFormHtml();
                            break;
                    }
                }
            }
        }
        return $text;
    }

    public function _initSettingFields()
    {
        $this->SettingsFields['CONF_PAYMENTMODULE_YM_DESC'] = array(
            'settings_value'         => '1',
            'settings_title'         => '�������� ������ ���� ������� ������ ��������:',
            'settings_description'   => '��������, ���� �� �������� �����, ��������� ������.����� � ������.�������� ��������� �� �����.',
            'settings_html_function' => '',
            'sort_order'             => 1,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_KASSA_ENABLE'] = array(
            'settings_value'         => '0',
            'settings_title'         => '������.�����',
            'settings_description'   => '',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 2,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_URLS'] = array(
            'settings_value'         => '1',
            'settings_title'         => '����� ������ ����������� (AvisoURL/checkURL)',
            'settings_description'   => '���������� � ������ ����� ������ �����, ����� ���������� �� � ��������� �� ������� ������.�����<br />https://���_�����/index.php?yandexmoney=yes',
            'settings_html_function' => '',
            'sort_order'             => 3,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_TESTMODE'] = array(
            'settings_value'         => '1',
            'settings_title'         => '�������� �����',
            'settings_description'   => '����������� �������� ����� ��� �������� ������',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 4,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_SHOPID'] = array(
            'settings_value'         => '',
            'settings_title'         => '������������� ������ �������� � ������.������� (ShopID)',
            'settings_description'   => '������ ��� ����������� ���',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 5,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_SCID'] = array(
            'settings_value'         => '',
            'settings_title'         => '������������� ������� ������ �������� � ������.������� (scid)',
            'settings_description'   => '������ ��� ����������� ���',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 6,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_PASSWORD'] = array(
            'settings_value'         => '',
            'settings_title'         => '��������� ����� (shopPassword) ��� ������ �����������',
            'settings_description'   => '',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 7,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_STATUS'] = array(
            'settings_value'         => '',
            'settings_title'         => '������ ������ ����� ������',
            'settings_description'   => '����������� ��������� ��� �� ������, ������� � ����� �������� �������� ���������� �����',
            'settings_html_function' => 'setting_ORDER_STATUS_SELECT(',
            'sort_order'             => 8,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_PAYMENT_TYPE_DESC'] = array(
            'settings_value'         => '1',
            'settings_title'         => '������� ������ ',
            'settings_description'   => '������� ������� ������, ������� �������� � �������� � ������',
            'settings_html_function' => '',
            'sort_order'             => 9,
        );

        foreach ($this->array_payments as $key => $value) {
            $this->SettingsFields['CONF_PAYMENTMODULE_YM_METHOD_'. strtoupper($key)] = array(
                'settings_value'         => '',
                'settings_title'         => $value[1],
                'settings_description'   => '',
                'settings_html_function' => 'setting_CHECK_BOX(',
                'sort_order'             => 10,
            );
        }

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_CHECK'] = array(
            'settings_value'         => '0',
            'settings_title'         => '���������� � ������.����� ������ ��� �����',
            'settings_description'   => '��������� ��� ���, ��� ��������� ������� ������.����� ��� 54-��',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 11,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_TAXES'] = array(
            'settings_value'         => '1',
            'settings_title'         => '������ ��� �� ���������',
            'settings_description'   => '��� ������ ����� ������������ � ������.�����, ���� � �������� ������ �� ������� ������ ������. ��� ����� ��� ������������ ����',
            'settings_html_function' => 'setting_SELECT_BOX(CYandexMoney::get_def_taxes(),',
            'sort_order'             => 12,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_SPACER_ONE'] = array(
            'settings_value'         => '1',
            'settings_title'         => '',
            'settings_description'   => '',
            'settings_html_function' => '',
            'sort_order'             => 13,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_MONEY_ENABLE'] = array(
            'settings_value'         => '0',
            'settings_title'         => '������.������',
            'settings_description'   => '',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 20,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_ACCOUNT'] = array(
            'settings_value'         => '',
            'settings_title'         => '����� �������� ������.�����',
            'settings_description'   => '� ���� ������� ����� ��������� ������',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 21,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_MONEY_PASSWORD'] = array(
            'settings_value'         => '',
            'settings_title'         => '��������� �����',
            'settings_description'   => '��� ����� �������� �� <a href="https://sp-money.yandex.ru/myservices/new.xml" target="blank">����� ������.�����</a>',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 22,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_MONEY_STATUS'] = array(
            'settings_value'         => '',
            'settings_title'         => '������ ������ ����� ������',
            'settings_description'   => '����������� ��������� ��� �� ������, ������� � ����� �������� �������� ���������� �����',
            'settings_html_function' => 'setting_ORDER_STATUS_SELECT(',
            'sort_order'             => 23,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_SPACER_TWO'] = array(
            'settings_value'         => '1',
            'settings_title'         => '',
            'settings_description'   => '',
            'settings_html_function' => '',
            'sort_order'             => 24,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_BILLING_ENABLE'] = array(
            'settings_value'         => '0',
            'settings_title'         => '������.��������',
            'settings_description'   => '',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 30,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_BILLING_ID'] = array(
            'settings_value'         => '',
            'settings_title'         => 'ID �����',
            'settings_description'   => '',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 31,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_BILLING_PURPOSE'] = array(
            'settings_value'         => '����� ������ %order_id% ������ ����� ������.��������',
            'settings_title'         => '���������� �������',
            'settings_description'   => '���������� ����� � ��������� ���������: �������� � ��� ��, ��� ������� �������� �����, ������� �������� ����� ��������',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 32,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_BILLING_STATUS'] = array(
            'settings_value'         => '',
            'settings_title'         => '������ ������',
            'settings_description'   => '������ ������ ��������, ��� ��������� ������� ����������: �������� ������ ��� ���, �� ������ ������ ������ �� ����������� �� ����������� ����� ��� � ����� �����',
            'settings_html_function' => 'setting_ORDER_STATUS_SELECT(',
            'sort_order'             => 33,
        );
    }

    public function getModes()
    {
        return array(
            array(
                'title' => '������.�����',
                'value' => '1',
            ),
            array(
                'title' => '������.������',
                'value' => '2',
            ),
            array(
                'title' => '������.��������',
                'value' => '3',
            )
        );
    }

    public function get_def_taxes()
    {
        return array(
            array('value' => '1', 'title' => '��� ���'),
            array('value' => '2', 'title' => '0%'),
            array('value' => '3', 'title' => '10%'),
            array('value' => '4', 'title' => '18%'),
            array('value' => '5', 'title' => '���������� ������ 11/110'),
            array('value' => '6', 'title' => '���������� ������ 18/118'),
        );
    }

    public function after_processing_html($orderID)
    {
        $order = ordGetOrder($orderID);

        $this->orderId = $orderID;
        $this->comment = $order['customers_comment'];
        $this->orderTotal = floatval($order["order_amount"] * $order["currency_value"]);

        $this->_realInitVars();

        $this->pay_method = $_SESSION['ym_method'];
        $this->userId = $order['customerID'];

        $this->createFormHtml($order);

        return $res;
    }

    public function before_payment_php($orderID, $OutSum, $merch)
    {
        $res  = '_before_payment_php_';
        return $res;
    }

    public function createFormHtml($orderDb)
    {
        $this->_realInitVars();

        if ($this->mode === self::MODE_KASSA && $this->check == 1) {
            $receipt = array(
               'customerContact' => $orderDb['customer_email'],
                'items' => array(),
            );

            $one = 0;
            if ($orderDb['order_discount'] > 0) {
                $one = $orderDb['order_discount'];
            }

            foreach (ordGetOrderContent($this->orderId) as $product) {
                $receipt['items'][] = array(
                    'quantity' => $product['Quantity'],
                    'text' => mb_substr($product['name'], 0, 128),
                    'tax' => $this->_getSettingValue('CONF_PAYMENTMODULE_YM_TAXES'),
                    'price' => array(
                        'amount' => number_format($product['Price'] - ($product['Price'] * $one / 100), 2, '.', ''),
                        'currency' => 'RUB'
                    ),
                );
            }

            if ($orderDb['shipping_type'] && $orderDb['shipping_cost'] > 0) {
                $receipt['items'][] = array(
                    'quantity' => 1,
                    'text' => mb_substr($orderDb['shipping_type'], 0, 128),
                    'tax' => $this->_getSettingValue('CONF_PAYMENTMODULE_YM_TAXES'),
                    'price' => array(
                        'amount' => number_format($orderDb['shipping_cost'], 2, '.', ''),
                        'currency' => 'RUB'
                    ),
                );
            }
        }

        if ($this->mode === self::MODE_KASSA) {
            $html = '
                <form method="POST" action="'.$this->getFormUrl().'"  id="paymentform" name = "paymentform" accept-charset="utf-8">
                   <input type="hidden" name="paymentType" value="'.$this->pay_method.'" />
                   <input type="hidden" name="shopid" value="'.$this->shopid.'">
                   <input type="hidden" name="scid" value="'.$this->scid.'">
                    <input type="hidden" name="shopSuccessURL" value="' . getTransactionResultURL('success') . '&InvId=' . $this->orderId  . '" >
                    <input type="hidden" name="shopFailURL" value="' . getTransactionResultURL('failure') . '&InvId=' . $this->orderId  . '" >
                   <input type="hidden" name="orderNumber" value="'.$this->orderId.'">
                   <input type="hidden" name="sum" value="'.$this->orderTotal.'" data-type="number" >
                   <input type="hidden" name="customerNumber" value="'.$this->userId.'" >
                   <input type="hidden" name="cms_name" value="shopcms" >
                ';
                if ($this->_getSettingValue("CONF_PAYMENTMODULE_YM_CHECK")) {
                    $html .= '<input type="hidden" name="ym_merchant_receipt" value=\''.JsHttpRequest::php2js($receipt).'\' >';
                }
                $html .= '</form>';
        } elseif ($this->mode === self::MODE_MONEY) {
            $html = '<form method="POST" action="'.$this->getFormUrl().'"  id="paymentform" name = "paymentform" accept-charset="utf-8">
                       <input type="hidden" name="receiver" value="'.$this->account.'">
                       <input type="hidden" name="formcomment" value="Order '.$this->orderId.'">
                       <input type="hidden" name="short-dest" value="Order '.$this->orderId.'">
                       <input type="hidden" name="writable-targets" value="'.$this->writable_targets.'">
                       <input type="hidden" name="comment-needed" value="'.$this->comment_needed.'">
                       <input type="hidden" name="label" value="'.$this->orderId.'">
                       <input type="hidden" name="quickpay-form" value="'.$this->quickpay_form.'">
                       <input type="hidden" name="paymentType" value="'.$this->pay_method.'">
                       <input type="hidden" name="targets" value="����� '.$this->orderId.'">
                       <input type="hidden" name="sum" value="'.$this->orderTotal.'" data-type="number" >
                       <input type="hidden" name="comment" value="'.$this->comment.'" >
                       <input type="hidden" name="need-fio" value="'.$this->need_fio.'">
                       <input type="hidden" name="need-email" value="'.$this->need_email.'" >
                       <input type="hidden" name="need-phone" value="'.$this->need_phone.'">
                       <input type="hidden" name="need-address" value="'.$this->need_address.'">
                        <input type="hidden" name="SuccessURL" value="' . getTransactionResultURL('success') . '&InvId=' . $this->orderId  . '" >
                        </form>';
        } elseif ($this->mode === self::MODE_BILLING) {
            $html = '<form method="POST" action="'.$this->getFormUrl().'"  id="paymentform" name = "paymentform" accept-charset="utf-8">
                       <input type="hidden" name="formId" value="'.$this->billing_id.'" />
                       <input type="hidden" name="narrative" value="Order '.htmlspecialchars($this->parsePlaceholders($this->billing_purpose, $orderDb)).'" />
                       <input type="hidden" name="sum" value="'.$this->orderTotal.'" data-type="number" />
                       <input type="hidden" name="fio" value="'.htmlspecialchars($_SESSION['ym_billing_fio']).'" />
                       <input type="hidden" name="quickPayVersion" value="2" />
                       <input type="hidden" name="cms_name" value="shopcms" />
                    </form>';
            ostSetOrderStatusToOrder($orderDb['orderID'], $this->billing_status);
        }
        $html .= '<script type="text/javascript">
                    document.getElementById("paymentform").submit();
                    </script>';
        echo $html; exit;
        return $html;
    }

    public function checkSign($callbackParams)
    {
        $string = $callbackParams['action'].';'.$callbackParams['orderSumAmount'].';'
            .$callbackParams['orderSumCurrencyPaycash'].';'.$callbackParams['orderSumBankPaycash'].';'
            .$callbackParams['shopId'].';'.$callbackParams['invoiceId'].';'.$callbackParams['customerNumber']
            .';'.$this->password;
        $md5 = strtoupper(md5($string));
        return (strtoupper($callbackParams['md5']) == $md5);
    }

    public function sendCode($callbackParams, $code)
    {
        header("Content-type: text/xml; charset=utf-8");
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <'.$callbackParams['action'].'Response performedDatetime="'.date("c").'" code="'.$code
            .'" invoiceId="'.$callbackParams['invoiceId'].'" shopId="'.$this->shopid.'"/>';
        echo $xml;
    }

    public function individualCheck($callbackParams)
    {
        $string = $callbackParams['notification_type'].'&'.$callbackParams['operation_id'].'&'
            .$callbackParams['amount'].'&'.$callbackParams['currency'].'&'.$callbackParams['datetime'] .'&'
            .$callbackParams['sender'].'&'.$callbackParams['codepro'].'&'.$this->money_password.'&'
            .$callbackParams['label'];
        $check = (sha1($string) == $callbackParams['sha1_hash']);
        if (!$check) {
            header('HTTP/1.0 401 Unauthorized');
            return false;
        }
        return true;
    }

    /* ���������� ����� */
    public function ProcessResult()
    {
        $callbackParams = $_POST;
        if ($this->mode === self::MODE_KASSA) {
            if ($this->checkSign($callbackParams)) {
                $order = ordGetOrder($callbackParams["orderNumber"]);
                if (number_format($callbackParams['orderSumAmount'], 2) == number_format(floatval($order["order_amount"] * $order["currency_value"]), 2)) {
                    $this->sendCode($callbackParams, 0);
                    return (int)$callbackParams["orderNumber"];
                } else {
                    $this->sendCode($callbackParams, 100);
                }
            } else {
                $this->sendCode($callbackParams, 1);
            }
        } else {
            if ($this->individualCheck($callbackParams)) {
                return (int)$callbackParams["label"];
            }
        }
        return false;
    }

    public function getFormUrl()
    {
        if ($this->mode === self::MODE_BILLING) {
            return 'https://money.yandex.ru/fatpay/confirm';
        }
        if ($this->mode !== self::MODE_KASSA) {
            return $this->individualGetFormUrl();
        } else {
            return $this->orgGetFormUrl();
        }
    }

    public function after_payment_php($orderID, $params)
    {
        $this->_realInitVars();
        $order_id = $this->ProcessResult();
        if ($order_id) {
            if ($this->mode === self::MODE_KASSA) {
                ostSetOrderStatusToOrder($order_id, $this->status);
            } elseif ($this->mode === self::MODE_MONEY) {
                ostSetOrderStatusToOrder($order_id, $this->money_status);
            }
        }
        exit;
    }

    private function individualGetFormUrl()
    {
        if ($this->test_mode) {
            return 'https://demomoney.yandex.ru/quickpay/confirm.xml';
        } else {
            return 'https://money.yandex.ru/quickpay/confirm.xml';
        }
    }

    private function orgGetFormUrl()
    {
        if ($this->test_mode) {
            return 'https://demomoney.yandex.ru/eshop.xml';
        } else {
            return 'https://money.yandex.ru/eshop.xml';
        }
    }

    private function parsePlaceholders($tpl, $order)
    {
        $replace = array(
            '%order_id%' => $order['orderID'],
        );
        foreach ($order as $key => $value) {
            if (is_scalar($value)) {
                $replace['%' . $value . '%'] = $value;
            }
        }
        return strtr($tpl, $replace);
    }
}
