<?php

/**
 * @connect_module_class_name CYandexMoney
 *
 */
// YandexMoney method implementation
// see also
//                http://money.yandex.ru

class CYandexMoney extends PaymentModule
{
    const YAVERSION = '1.2.3';

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
    public $method_cr;

    public $check;

    public $pay_method;

    public $account;
    public $money_password;
    public $money_status;

    public $billing_id;
    public $billing_purpose;
    public $billing_status;
    public $payment_method;
    public $payment_subject;
    public $delivery_payment_method;
    public $delivery_payment_subject;

    private $array_payments = array(
        'ym'    => array('PC', 'Яндекс.Деньги'),
        'cards' => array('AC', 'Банковские карты'),
        'cash'  => array('GP', 'Наличные через терминалы'),
        'phone' => array('MC', 'Счёта мобильного телефона'),
        'wm'    => array('WM', 'WebMoney'),
        'ab'    => array('AB', 'Альфа-Клик'),
        'sb'    => array('SB', 'Сбербанк Онлайн'),
        'ma'    => array('MA', 'MasterPass'),
        'pb'    => array('PB', 'Промсвязьбанк'),
        'qw'    => array('QW', 'QIWI Wallet'),
        'cr'    => array('CR', 'Заплатить по частям'),
    );

    public function _initVars()
    {
        $this->title       = "YandexMoney";
        $this->description = "YandexMoney (money.yandex.ru).
             <br/> Модуль работает в режиме автоматической оплаты. Этот модуль можно использовать для автоматической продажи цифровых товаров.
             <br/>
             Любое использование Вами программы означает полное и безоговорочное принятие Вами условий лицензионного договора, размещенного по адресу <a href=\"https://money.yandex.ru/doc.xml?id=527132\">https://money.yandex.ru/doc.xml?id=527132</a> (далее – «Лицензионный договор»). Если Вы не принимаете условия Лицензионного договора в полном объёме, Вы не имеете права использовать программу в каких-либо целях.
             <br/>Модуль версии ".self::YAVERSION;
        $this->sort_order  = 0;

        $array_params = array(
            'desc',

            // kassa options
            'kassa_enable',
            'urls',
            'testmode',
            'shopid',
            'scid',
            'password',
            'status',
            'payment_type_desc',
            'method_ym',
            'method_cards',
            'method_cash',
            'method_phone',
            'method_wm',
            'method_ab',
            'method_sb',
            'method_ma',
            'method_pb',
            'method_qw',
            'method_cr',
            'check',
            'taxes',
            'spacer_one',
            'payment_method',
            'payment_subject',
            'delivery_payment_method',
            'delivery_payment_subject',

            // money options
            'money_enable',
            'account',
            'money_password',
            'money_status',
            'spacer_two',

            // billing options
            'billing_enable',
            'billing_id',
            'billing_purpose',
            'billing_status',
        );

        foreach ($array_params as $key => $value) {
            $value2           = 'CONF_PAYMENTMODULE_YM_'.strtoupper($value);
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
            'urls',
            'testmode',
            'shopid',
            'scid',
            'password',
            'status',
            'method_ym',
            'method_cards',
            'method_cash',
            'method_phone',
            'method_wm',
            'method_ab',
            'method_sb',
            'method_ma',
            'method_pb',
            'method_qw',
            'method_cr',
            'check',
            'taxes',
            'payment_method',
            'payment_subject',
            'delivery_payment_method',
            'delivery_payment_subject',

            // money options
            'money_enable',
            'account',
            'money_password',
            'money_status',

            // billing options
            'billing_enable',
            'billing_id',
            'billing_purpose',
            'billing_status',
        );

        foreach ($array_params as $key => $value) {
            $value2       = 'CONF_PAYMENTMODULE_YM_'.strtoupper($value);
            $this->$value = $this->_getSettingValue($value2);
        }

        if ($this->kassa_enable == 1) {
            $this->mode           = self::MODE_KASSA;
            $this->money_enable   = false;
            $this->billing_enable = false;
        } elseif ($this->money_enable == 1) {
            $this->mode           = self::MODE_MONEY;
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
        $html = "<br/><b>Способ оплаты:</b><br/><select name=\"ym_method\">";
        if ($this->mode === self::MODE_KASSA) {
            foreach ($this->array_payments as $key => $value) {
                if ($this->_getSettingValue('CONF_PAYMENTMODULE_YM_METHOD_'.strtoupper($key))) {
                    $html .= '<option value="'.$value[0].'">'.$value[1].'</option>';
                }
            }
        } elseif ($this->mode === self::MODE_MONEY) {
            foreach (array('ym', 'cards') as $key => $value) {
                if ($this->_getSettingValue('CONF_PAYMENTMODULE_YM_METHOD_'.strtoupper($key))) {
                    $html .= '<option value="'.$value[0].'">'.$value[1].'</option>';
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

        $html = '<br /><label for="ym-billing-fio">ФИО плательщика</label><br />'
                .'<input type="text" name="ym_billing_fio" id="ym-billing-fio" value="'.htmlspecialchars(implode(' ',
                $fio)).'" />'
                .'<div id="ym-billing-fio-error" style="display: none;">Укажите фамилию имя и отчество плательщика</div>'
                .'<br /><br />'
                .'<script>
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
            'settings_title'         => 'Выберите только одну систему приема платежей:',
            'settings_description'   => 'например, если вы выберете Кассу, настройки Яндекс.Денег и Яндекс.Платежки заполнять не нужно.',
            'settings_html_function' => '',
            'sort_order'             => 1,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_KASSA_ENABLE'] = array(
            'settings_value'         => '0',
            'settings_title'         => 'Яндекс.Касса',
            'settings_description'   => '',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 2,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_URLS'] = array(
            'settings_value'         => '1',
            'settings_title'         => 'Адрес приема уведомлений (AvisoURL/checkURL)',
            'settings_description'   => 'Подставьте в ссылку адрес вашего сайта, затем скопируйте ее в настройки на стороне Яндекс.Кассы<br />https://ваш_домен/index.php?yandexmoney=yes',
            'settings_html_function' => '',
            'sort_order'             => 3,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_TESTMODE'] = array(
            'settings_value'         => '1',
            'settings_title'         => 'Тестовый режим',
            'settings_description'   => 'Используйте тестовый режим для проверки модуля',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 4,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_SHOPID'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Идентификатор вашего магазина в Яндекс.Деньгах (ShopID)',
            'settings_description'   => 'Только для юридических лиц',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 5,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_SCID'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Идентификатор витрины вашего магазина в Яндекс.Деньгах (scid)',
            'settings_description'   => 'Только для юридических лиц',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 6,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_PASSWORD'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Секретное слово (shopPassword) для обмена сообщениями',
            'settings_description'   => '',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 7,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_STATUS'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Статус заказа после оплаты',
            'settings_description'   => 'Рекомендуем поставить тот же статус, который в вашем магазине означает оплаченный заказ',
            'settings_html_function' => 'setting_ORDER_STATUS_SELECT(',
            'sort_order'             => 8,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_PAYMENT_TYPE_DESC'] = array(
            'settings_value'         => '1',
            'settings_title'         => 'Способы оплаты ',
            'settings_description'   => 'Укажите способы оплаты, которые отмечены в договоре с Кассой',
            'settings_html_function' => '',
            'sort_order'             => 9,
        );

        foreach ($this->array_payments as $key => $value) {
            $this->SettingsFields['CONF_PAYMENTMODULE_YM_METHOD_'.strtoupper($key)] = array(
                'settings_value'         => '',
                'settings_title'         => $value[1],
                'settings_description'   => '',
                'settings_html_function' => 'setting_CHECK_BOX(',
                'sort_order'             => 10,
            );
        }

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_CHECK'] = array(
            'settings_value'         => '0',
            'settings_title'         => 'Отправлять в Яндекс.Кассу данные для чеков',
            'settings_description'   => 'Настройка для тех, кто подключил решение Яндекс.Кассы для 54-ФЗ',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 11,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_TAXES'] = array(
            'settings_value'         => '1',
            'settings_title'         => 'Ставка НДС по умолчанию',
            'settings_description'   => 'Эта ставка будет передаваться в Яндекс.Кассу, если в карточке товара не указана другая ставка. НДС нужен для формирования чека',
            'settings_html_function' => 'setting_SELECT_BOX(CYandexMoney::get_def_taxes(),',
            'sort_order'             => 12,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_PAYMENT_METHOD'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Признак способа расчета',
            'settings_description'   => '',
            'settings_html_function' => 'setting_SELECT_BOX(CYandexMoney::getPaymentModeEnum(),',
            'sort_order'             => 13,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_PAYMENT_SUBJECT'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Признак предмета расчета',
            'settings_description'   => '',
            'settings_html_function' => 'setting_SELECT_BOX(CYandexMoney::getPaymentSubjectEnum(),',
            'sort_order'             => 14,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_DELIVERY_PAYMENT_METHOD'] = array(
            'settings_value'         => '1',
            'settings_title'         => 'Признак способа рачета для доставки',
            'settings_description'   => '',
            'settings_html_function' => 'setting_SELECT_BOX(CYandexMoney::getPaymentModeEnum(),',
            'sort_order'             => 15,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_DELIVERY_PAYMENT_SUBJECT'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Признак предмета расчета для доставки',
            'settings_description'   => '',
            'settings_html_function' => 'setting_SELECT_BOX(CYandexMoney::getPaymentSubjectEnum(),',
            'sort_order'             => 16,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_SPACER_ONE'] = array(
            'settings_value'         => '1',
            'settings_title'         => '',
            'settings_description'   => '',
            'settings_html_function' => '',
            'sort_order'             => 17,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_MONEY_ENABLE'] = array(
            'settings_value'         => '0',
            'settings_title'         => 'Яндекс.Деньги',
            'settings_description'   => '',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 20,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_ACCOUNT'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Номер кошелька Яндекс.Денег',
            'settings_description'   => 'В этот кошелек будут поступать деньги',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 21,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_MONEY_PASSWORD'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Секретное слово',
            'settings_description'   => 'Его нужно получить на <a href="https://sp-money.yandex.ru/myservices/new.xml" target="blank">сайте Яндекс.Денег</a>',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 22,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_MONEY_STATUS'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Статус заказа после оплаты',
            'settings_description'   => 'Рекомендуем поставить тот же статус, который в вашем магазине означает оплаченный заказ',
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
            'settings_title'         => 'Яндекс.Платежка',
            'settings_description'   => '',
            'settings_html_function' => 'setting_CHECK_BOX(',
            'sort_order'             => 30,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_BILLING_ID'] = array(
            'settings_value'         => '',
            'settings_title'         => 'ID формы',
            'settings_description'   => '',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 31,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_BILLING_PURPOSE'] = array(
            'settings_value'         => 'Номер заказа %order_id% Оплата через Яндекс.Платежку',
            'settings_title'         => 'Назначение платежа',
            'settings_description'   => 'Назначение будет в платежном поручении: напишите в нем всё, что поможет отличить заказ, который оплатили через Платежку',
            'settings_html_function' => 'setting_TEXT_BOX(0,',
            'sort_order'             => 32,
        );

        $this->SettingsFields['CONF_PAYMENTMODULE_YM_BILLING_STATUS'] = array(
            'settings_value'         => '',
            'settings_title'         => 'Статус заказа',
            'settings_description'   => 'Статус должен показать, что результат платежа неизвестен: заплатил клиент или нет, вы можете узнать только из уведомления на электронной почте или в своем банке',
            'settings_html_function' => 'setting_ORDER_STATUS_SELECT(',
            'sort_order'             => 33,
        );
    }

    public function getModes()
    {
        return array(
            array(
                'title' => 'Яндекс.Касса',
                'value' => '1',
            ),
            array(
                'title' => 'Яндекс.Деньги',
                'value' => '2',
            ),
            array(
                'title' => 'Яндекс.Платежка',
                'value' => '3',
            ),
        );
    }

    public function get_def_taxes()
    {
        return array(
            array('value' => '1', 'title' => 'Без НДС'),
            array('value' => '2', 'title' => '0%'),
            array('value' => '3', 'title' => '10%'),
            array('value' => '4', 'title' => '18%'),
            array('value' => '5', 'title' => 'Рассчётная ставка 11/110'),
            array('value' => '6', 'title' => 'Рассчётная ставка 18/118'),
        );
    }

    public function getPaymentModeEnum()
    {
        return array(
            array('value' => 'full_prepayment', 'title' => 'Полная предоплата (full_prepayment)'),
            array('value' => 'partial_prepayment', 'title' => 'Частичная предоплата (partial_prepayment)'),
            array('value' => 'advance', 'title' => 'Аванс (advance)'),
            array('value' => 'full_payment', 'title' => 'Полный расчет (full_payment)'),
            array('value' => 'partial_payment', 'title' => 'Частичный расчет и кредит (partial_payment)'),
            array('value' => 'credit', 'title' => 'Кредит (credit)'),
            array('value' => 'credit_payment', 'title' => 'Выплата по кредиту (credit_payment)'),
        );
    }

    public function getPaymentSubjectEnum()
    {
        return array(
            array('value' => 'commodity', 'title' => 'Товар (commodity)'),
            array('value' => 'excise', 'title' => 'Подакцизный товар (excise)'),
            array('value' => 'job', 'title' => 'Работа (job)'),
            array('value' => 'service', 'title' => 'Услуга (service)'),
            array('value' => 'gambling_bet', 'title' => 'Ставка в азартной игре (gambling_bet)'),
            array('value' => 'gambling_prize', 'title' => 'Выигрыш в азартной игре (gambling_prize)'),
            array('value' => 'lottery', 'title' => 'Лотерейный билет (lottery)'),
            array('value' => 'lottery_prize', 'title' => 'Выигрыш в лотерею (lottery_prize)'),
            array(
                'value' => 'intellectual_activity',
                'title' => 'Результаты интеллектуальной деятельности (intellectual_activity)',
            ),
            array('value' => 'payment', 'title' => 'Платеж (payment)'),
            array('value' => 'agent_commission', 'title' => 'Агентское вознаграждение (agent_commission)'),
            array('value' => 'composite', 'title' => 'Несколько вариантов (composite)'),
            array('value' => 'another', 'title' => 'Другое (another)'),
        );
    }

    public function after_processing_html($orderID)
    {
        $order = ordGetOrder($orderID);

        $this->orderId    = $orderID;
        $this->comment    = $order['customers_comment'];
        $this->orderTotal = floatval($order["order_amount"] * $order["currency_value"]);

        $this->_realInitVars();

        $this->pay_method = $_SESSION['ym_method'];
        $this->userId     = $order['customerID'];

        $this->createFormHtml($order);

        return $res;
    }

    public function before_payment_php($orderID, $OutSum, $merch)
    {
        $res = '_before_payment_php_';

        return $res;
    }

    public function createFormHtml($orderDb)
    {
        $this->_realInitVars();

        $receipt = null;
        if ($this->mode === self::MODE_KASSA && $this->check == 1) {
            $receipt = new YandexMoneyReceipt($this->_getSettingValue('CONF_PAYMENTMODULE_YM_TAXES'));
            $receipt->setCustomerContact($orderDb['customer_email']);
            $paymentMethod          = $this->_getSettingValue('CONF_PAYMENTMODULE_YM_PAYMENT_METHOD');
            $paymentSubject         = $this->_getSettingValue('CONF_PAYMENTMODULE_YM_PAYMENT_SUBJECT');
            $paymentDeliveryMethod  = $this->_getSettingValue('CONF_PAYMENTMODULE_YM_DELIVERY_PAYMENT_METHOD');
            $paymentDeliverySubject = $this->_getSettingValue('CONF_PAYMENTMODULE_YM_DELIVERY_PAYMENT_SUBJECT');

            foreach (ordGetOrderContent($this->orderId) as $product) {
                $receipt->addItem(mb_convert_encoding($product['name'], 'utf-8', 'cp-1251'), $product['Price'],
                    $product['Quantity'], null, $paymentMethod, $paymentSubject);
            }
            if ($orderDb['shipping_type'] && $orderDb['shipping_cost'] > 0) {
                $receipt->addShipping(mb_convert_encoding($orderDb['shipping_type'], 'utf-8', 'cp-1251'),
                    $orderDb['shipping_cost'], null, $paymentDeliveryMethod, $paymentDeliverySubject);
            }
            $receipt->normalize($this->orderTotal);
        }

        if ($this->mode === self::MODE_KASSA) {
            $html = '
                <form method="POST" action="'.$this->getFormUrl().'"  id="paymentform" name = "paymentform" accept-charset="utf-8">
                    <input type="hidden" name="paymentType" value="'.$this->pay_method.'" />
                    <input type="hidden" name="shopid" value="'.$this->shopid.'">
                    <input type="hidden" name="scid" value="'.$this->scid.'">
                    <input type="hidden" name="shopSuccessURL" value="'.getTransactionResultURL('success').'&InvId='.$this->orderId.'" >
                    <input type="hidden" name="shopFailURL" value="'.getTransactionResultURL('failure').'&InvId='.$this->orderId.'" >
                    <input type="hidden" name="orderNumber" value="'.$this->orderId.'">
                    <input type="hidden" name="sum" value="'.$this->orderTotal.'" data-type="number" >
                    <input type="hidden" name="customerNumber" value="'.$this->userId.'" >
                    <input type="hidden" name="cms_name" value="shopcms" >
                ';
            if ($this->_getSettingValue("CONF_PAYMENTMODULE_YM_CHECK") && !empty($receipt)) {
                $html .= '<textarea name="ym_merchant_receipt" style="display:none;">'.$receipt->getJson().'</textarea>';
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
                       <input type="hidden" name="targets" value="Заказ '.$this->orderId.'">
                       <input type="hidden" name="sum" value="'.$this->orderTotal.'" data-type="number" >
                       <input type="hidden" name="comment" value="'.$this->comment.'" >
                       <input type="hidden" name="need-fio" value="'.$this->need_fio.'">
                       <input type="hidden" name="need-email" value="'.$this->need_email.'" >
                       <input type="hidden" name="need-phone" value="'.$this->need_phone.'">
                       <input type="hidden" name="need-address" value="'.$this->need_address.'">
                        <input type="hidden" name="SuccessURL" value="'.getTransactionResultURL('success').'&InvId='.$this->orderId.'" >
                        </form>';
        } elseif ($this->mode === self::MODE_BILLING) {
            $html = '<form method="POST" action="'.$this->getFormUrl().'"  id="paymentform" name = "paymentform" accept-charset="utf-8">
                       <input type="hidden" name="formId" value="'.$this->billing_id.'" />
                       <input type="hidden" name="narrative" value="Order '.htmlspecialchars($this->parsePlaceholders($this->billing_purpose,
                    $orderDb)).'" />
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
        echo $html;
        exit;

        return $html;
    }

    public function checkSign($callbackParams)
    {
        $string = $callbackParams['action'].';'.$callbackParams['orderSumAmount'].';'
                  .$callbackParams['orderSumCurrencyPaycash'].';'.$callbackParams['orderSumBankPaycash'].';'
                  .$callbackParams['shopId'].';'.$callbackParams['invoiceId'].';'.$callbackParams['customerNumber']
                  .';'.$this->password;
        $md5    = strtoupper(md5($string));

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
                  .$callbackParams['amount'].'&'.$callbackParams['currency'].'&'.$callbackParams['datetime'].'&'
                  .$callbackParams['sender'].'&'.$callbackParams['codepro'].'&'.$this->money_password.'&'
                  .$callbackParams['label'];
        $check  = (sha1($string) == $callbackParams['sha1_hash']);
        if (!$check) {
            header('HTTP/1.0 401 Unauthorized');

            return false;
        }

        return true;
    }

    /* оплачивает заказ */
    public function ProcessResult()
    {
        $callbackParams = $_POST;
        if ($this->mode === self::MODE_KASSA) {
            if ($this->checkSign($callbackParams)) {
                $order = ordGetOrder($callbackParams["orderNumber"]);
                if (number_format($callbackParams['orderSumAmount'],
                        2) == number_format(floatval($order["order_amount"] * $order["currency_value"]), 2)
                ) {
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
                $replace['%'.$value.'%'] = $value;
            }
        }

        return strtr($tpl, $replace);
    }
}


if (!interface_exists('JsonSerializable', false)) {
    interface JsonSerializable
    {
        function jsonSerialize();
    }
}

/**
 * Класс чека
 */
class YandexMoneyReceipt implements JsonSerializable
{
    /** @var string Код валюты - рубли */
    const CURRENCY_RUB = 'RUB';

    /** @var string Используемая по умолчанию валюта */
    const DEFAULT_CURRENCY = self::CURRENCY_RUB;

    /** @var int Идентификатор ставки НДС по умолчанию */
    const DEFAULT_TAX_RATE_ID = 1;

    /** @var YandexMoneyReceiptItem[] Массив с информацией о покупаемых товарах */
    private $items;

    /** @var string Контакт покупателя, куда будет отправлен чек - либо имэйл, либо номер телефона */
    private $customerContact;

    /** @var int Идентификатор ставки НДС по умолчанию */
    private $taxRateId;

    /** @var string Валюта в которой производится платёж */
    private $currency;

    /** @var YandexMoneyReceiptItem|null Айтем в котором хранится информация о доставке как о товаре */
    private $shipping;

    /**
     * @param int $taxRateId
     * @param string $currency
     */
    public function __construct($taxRateId = self::DEFAULT_TAX_RATE_ID, $currency = self::DEFAULT_CURRENCY)
    {
        $this->taxRateId = $taxRateId;
        $this->items     = array();
        $this->currency  = $currency;
    }

    /**
     * Добавляет в чек товар
     *
     * @param string $title Название товара
     * @param float $price Цена товара
     * @param float $quantity Количество покупаемого товара
     * @param int|null $taxId Идентификатор ставки НДС для товара или null
     *
     * @param string $paymentMethodType
     * @param string $paymentSubjectType
     *
     * @return YandexMoneyReceipt
     */
    public function addItem(
        $title,
        $price,
        $quantity = 1.0,
        $taxId = null,
        $paymentMethodType = '',
        $paymentSubjectType = ''
    ) {
        $this->items[] = new YandexMoneyReceiptItem($title, $quantity, $price, false, $taxId, $paymentMethodType,
            $paymentSubjectType);

        return $this;
    }

    /**
     * Добавляет в чек доставку
     *
     * @param string $title Название способа доставки
     * @param float $price Цена доставки
     * @param int|null $taxId Идентификатор ставки НДС для доставки или null
     *
     * @param string $paymentMethodType
     * @param string $paymentSubjectType
     *
     * @return YandexMoneyReceipt
     */
    public function addShipping(
        $title,
        $price,
        $taxId = null,
        $paymentMethodType = '',
        $paymentSubjectType = ''
    ) {
        $this->shipping = new YandexMoneyReceiptItem($title, 1.0, $price, true, $taxId, $paymentMethodType,
            $paymentSubjectType);
        $this->items[]  = $this->shipping;

        return $this;
    }

    /**
     * Устанавливает адрес доставки чека - или имейл или номер телефона
     *
     * @param string $value Номер телефона или имэйл получателя
     *
     * @return YandexMoneyReceipt
     */
    public function setCustomerContact($value)
    {
        $this->customerContact = $value;

        return $this;
    }

    /**
     * Возвращает стоимость заказа исходя из состава чека
     *
     * @param bool $withShipping Добавить ли к стоимости заказа стоимость доставки
     *
     * @return float Общая стоимость заказа
     */
    public function getAmount($withShipping = true)
    {
        $result = 0.0;
        foreach ($this->items as $item) {
            if ($withShipping || !$item->isShipping()) {
                $result += $item->getAmount();
            }
        }

        return $result;
    }

    /**
     * Преобразует чек в массив для дальнейшей его отправки в JSON формате
     * @return array Ассоциативный массив с чеком, готовый для отправки в JSON формате
     */
    public function jsonSerialize()
    {
        $items = array();

        foreach ($this->items as $item) {
            if ($item->getPrice() >= 0.0) {
                $items[] = array(
                    'quantity'           => (string)$item->getQuantity(),
                    'price'              => array(
                        'amount'   => number_format($item->getPrice(), 2, '.', ''),
                        'currency' => $this->currency,
                    ),
                    'tax'                => $item->hasTaxId() ? $item->getTaxId() : $this->taxRateId,
                    'text'               => $this->escapeString($item->getTitle()),
                    'paymentMethodType'  => $item->getPaymentMethodType(),
                    'paymentSubjectType' => $item->getPaymentSubjectType(),
                );
            }
        }

        return array(
            'items'           => $items,
            'customerContact' => $this->escapeString($this->customerContact),
        );
    }

    /**
     * Сериализует чек в JSON формат
     * @return string Чек в JSON формате
     */
    public function getJson()
    {
        if (defined('JSON_UNESCAPED_UNICODE')) {
            return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $json = json_encode($this->jsonSerialize());
            // для версий PHP которые не поддерживают передачу параметров в json_encode
            // заменяем в полученной при сериализации строке уникод последовательности
            // вида \u1234 на их реальное значение в utf-8
            return preg_replace_callback('/\\\\u(\w{4})/', array($this, 'legacyReplaceUnicodeMatches'), $json);
        }
    }

    public function legacyReplaceUnicodeMatches($matches)
    {
        return html_entity_decode('&#x'.$matches[1].';', ENT_COMPAT, 'UTF-8');
    }

    /**
     * Подгоняет стоимость товаров в чеке к общей цене заказа
     *
     * @param float $orderAmount Общая стоимость заказа
     * @param bool $withShipping Поменять ли заодно и цену доставки
     *
     * @return YandexMoneyReceipt
     */
    public function normalize($orderAmount, $withShipping = false)
    {
        if (!$withShipping) {
            if ($this->shipping !== null) {
                $orderAmount -= $this->shipping->getAmount();
            }
        }
        $realAmount = $this->getAmount($withShipping);
        if ($realAmount != $orderAmount) {
            $coefficient = $orderAmount / $realAmount;
            $realAmount  = 0.0;
            $aloneId     = null;
            foreach ($this->items as $index => $item) {
                if ($withShipping || !$item->isShipping()) {
                    $item->applyDiscountCoefficient($coefficient);
                    $realAmount += $item->getAmount();
                    if ($aloneId === null && $item->getQuantity() === 1.0) {
                        $aloneId = $index;
                    }
                }
            }
            if ($aloneId === null) {
                $aloneId = 0;
            }
            $diff = $orderAmount - $realAmount;
            if (abs($diff) >= 0.001) {
                if ($this->items[$aloneId]->getQuantity() === 1.0) {
                    $this->items[$aloneId]->increasePrice($diff);
                } else {
                    $item = $this->items[0]->fetchItem(1);
                    $item->increasePrice($diff);
                    array_splice($this->items, $aloneId + 1, 0, array($item));
                }
            }
        }

        return $this;
    }

    /**
     * Деэскейпирует строку для вставки в JSON
     *
     * @param string $string Исходная строка
     *
     * @return string Строка с эскейпированными "<" и ">"
     */
    private function escapeString($string)
    {
        // JSON отправляем в utf-8
        $string = iconv('windows-1251', 'utf-8', $string);

        return str_replace(array('<', '>'), array('&lt;', '&gt;'), html_entity_decode($string));
    }
}

/**
 * Класс товара в чеке
 */
class YandexMoneyReceiptItem
{
    /** @var string Название товара */
    private $title;

    /** @var float Количество покупаемого товара */
    private $quantity;

    /** @var float Цена товара */
    private $price;

    /** @var bool Является ли наименование доставкой товара */
    private $shipping;

    /** @var int|null Идентификатор ставки НДС для конкретного товара */
    private $taxId;
    private $paymentMethodType;
    private $paymentSubjectType;

    /**
     * YandexMoneyReceiptItem constructor.
     *
     * @param string $title
     * @param float $quantity
     * @param float $price
     * @param bool $isShipping
     * @param int|null $taxId
     * @param $paymentMethodType
     * @param $paymentSubjectType
     */
    public function __construct(
        $title,
        $quantity,
        $price,
        $isShipping,
        $taxId,
        $paymentMethodType,
        $paymentSubjectType
    ) {
        $this->title              = mb_substr($title, 0, 60, 'windows-1251');
        $this->quantity           = (float)$quantity;
        $this->price              = round($price, 2);
        $this->shipping           = $isShipping;
        $this->taxId              = $taxId;
        $this->paymentMethodType  = $paymentMethodType;
        $this->paymentSubjectType = $paymentSubjectType;
    }

    /**
     * Возвращает цену товара
     * @return float Цена товара
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Возвращает общую стоимость позиции в чеке
     * @return float Стоимость покупаемого товара
     */
    public function getAmount()
    {
        return round($this->price * $this->quantity, 2);
    }

    /**
     * Возвращает название товара
     * @return string Название товара
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Возвращает количество покупаемого товара
     * @return float Количество товара
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Проверяет, установлена ли для товара ставка НДС
     * @return bool True если ставка НДС для товара установлена, false если нет
     */
    public function hasTaxId()
    {
        return $this->taxId !== null;
    }

    /**
     * Возвращает ставку НДС товара
     * @return int|null Идентификатор ставки НДС или null если он не был установлен
     */
    public function getTaxId()
    {
        return $this->taxId;
    }

    /**
     * Привеняет для товара скидку
     *
     * @param float $value Множитель скидки
     */
    public function applyDiscountCoefficient($value)
    {
        $this->price = round($value * $this->price, 2);
    }

    /**
     * Увеличивает цену товара на указанную величину
     *
     * @param float $value Сумма на которую цену товара увеличиваем
     */
    public function increasePrice($value)
    {
        $this->price = round($this->price + $value, 2);
    }

    /**
     * Уменьшает количество покупаемого товара на указанное, возвращает объект позиции в чеке с уменьшаемым количеством
     *
     * @param float $count Количество на которое уменьшаем позицию в чеке
     *
     * @return YandexMoneyReceiptItem Новый инстанс позиции в чеке
     */
    public function fetchItem($count)
    {
        if ($count > $this->quantity) {
            throw new BadMethodCallException();
        }
        $result         = new YandexMoneyReceiptItem($this->title, $count, $this->price, false, $this->taxId);
        $this->quantity -= $count;

        return $result;
    }

    /**
     * Проверяет является ли текущая позиция доставкой товара
     * @return bool True если доставка товара, false если нет
     */
    public function isShipping()
    {
        return $this->shipping;
    }

    /**
     * @return mixed
     */
    public function getPaymentMethodType()
    {
        return $this->paymentMethodType;
    }

    /**
     * @return mixed
     */
    public function getPaymentSubjectType()
    {
        return $this->paymentSubjectType;
    }
}
