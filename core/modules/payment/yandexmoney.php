<?php
/**
 * @connect_module_class_name CYandexMoney
 *
 */
// YandexMoney method implementation
// see also
//                http://money.yandex.ru

class CYandexMoney extends PaymentModule {
	const YAVERSION = '1.2.1';
	
	public $test_mode;
	public $org_mode;
	public $status;

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
	public $account;
	public $password;

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

	public $pay_method;
	private $array_payments = array(
				'ym'=>array('PC','Оплата из кошелька в Яндекс.Деньгах'),
				'cards'=>array('AC','Оплата с произвольной банковской карты'),
				'cash'=>array('GP','Оплата наличными через кассы и терминалы'),
				'phone'=>array('MC','Платеж со счета мобильного телефона'),
				'wm'=>array('WM','Оплата из кошелька в системе WebMoney'),
				'ab'=>array('AB','Оплата через Альфа-Клик'),
				'sb'=>array('SB','Оплата через Сбербанк: оплата по SMS или Сбербанк Онлайн'),
				'ma'=>array('MA','Оплата через MasterPass'),
				'pb'=>array('PB','Оплата через интернет-банк Промсвязьбанка'),
				'qw'=>array('QW','Оплата через QIWI Wallet'),
				'qp'=>array('QP','Оплата через доверительный платеж (Куппи.ру)'));
				
    function _initVars(){
			 
             $this->title                 = "YandexMoney";
             $this->description         = "YandexMoney (money.yandex.ru).
				 <br/> Модуль работает в режиме автоматической оплаты. Этот модуль можно использовать для автоматической продажи цифровых товаров.
				 <br/>
				 Любое использование Вами программы означает полное и безоговорочное принятие Вами условий лицензионного договора, размещенного по адресу <a href=\"https://money.yandex.ru/doc.xml?id=527132\">https://money.yandex.ru/doc.xml?id=527132</a> (далее – «Лицензионный договор»). Если Вы не принимаете условия Лицензионного договора в полном объёме, Вы не имеете права использовать программу в каких-либо целях.
				 <br/>Модуль версии ".self::YAVERSION;
             $this->sort_order         = 0;

			 $array_params = array('urls','testmode', 'mode', 'method_ym', 'method_cards', 'method_cash', 'method_phone', 'method_wm', 'method_ab', 'method_sb', 'method_ma', 'method_pb','method_qw','method_qp', 'password', 'shopid', 'scid', 'account', 'status');
             foreach ($array_params as $key => $value) {
				$value2 = 'CONF_PAYMENTMODULE_YM_' . strtoupper($value);
				$this->Settings[] = $value2;
				$this->$value = $this->_getSettingValue($value2);
			 }
			
			 $this->org_mode = ($this->_getSettingValue('CONF_PAYMENTMODULE_YM_MODE') == 2);
			 $this->test_mode = ($this->_getSettingValue('CONF_PAYMENTMODULE_YM_TESTMODE') == 1);

			 if (!empty($_POST['ym_method'])) {
				$_SESSION['ym_method'] = $_POST['ym_method'];
			 }			 
        }

		function getMethodsHtml(){
				
			$html = "<br/><b>Способ оплаты:</b><br/><select name=\"ym_method\">";
         foreach ($this->array_payments as $key => $value)	if ($this->_getSettingValue('CONF_PAYMENTMODULE_YM_METHOD_'. strtoupper($key))) $html .= '<option value="'.$value[0].'">'.$value[1].'</option>';
			$html .= "</select><br/> <br/>";
			return $html;
		}

		function payment_form_html()
        {
		   $text = '';
		   $payment_methods = payGetAllPaymentMethods(true);
		   foreach ($payment_methods as $method) {
			   if ($_GET['paymentMethodID'] == $method['PID']) {
				   $currentPaymentModule = modGetModuleObj($method['module_id'], PAYMENT_MODULE);
				   if ( $currentPaymentModule != null ){
					   if (get_class($currentPaymentModule) == 'CYandexMoney') { 
						   $currentPaymentModule->_initVars();
						   $text .= $currentPaymentModule->getMethodsHtml();
					   }
					}
			   }
		   }
		    return $text;
        }


        function _initSettingFields(){
		  $this->SettingsFields['CONF_PAYMENTMODULE_YM_URLS'] = array(
				'settings_value'                 => '1',
				'settings_title'                         => 'Адрес приема уведомлений (AvisoURL/checkURL)',
                'settings_description'         => 'https://ваш_домен/index.php?yandexmoney=yes',
                'settings_html_function'         => '',
                'sort_order'                         => 1,
              );
			$this->SettingsFields['CONF_PAYMENTMODULE_YM_TESTMODE'] = array(
				'settings_value'                 => '1',
				'settings_title'                         => 'Тестовый режим',
                'settings_description'         => 'Используйте тестовый режим для проверки модуля',
                'settings_html_function'         => 'setting_CHECK_BOX(',
                'sort_order'                         => 1,
              );
			 $this->SettingsFields['CONF_PAYMENTMODULE_YM_MODE'] = array(
                        'settings_value'                 => '1',
                        'settings_title'                         => 'Выберите способ оплаты',
                        'settings_description'         => '',
                        'settings_html_function'         => 'setting_SELECT_BOX(CYandexMoney::getModes(),',
                        'sort_order'                         => 2,
                );
					 
				foreach ($this->array_payments as $key => $value){
					$this->SettingsFields['CONF_PAYMENTMODULE_YM_METHOD_'. strtoupper($key)] = array(
                        'settings_value'                 => '',
                        'settings_title'                         => $value[1],
                        'settings_description'         => '',
                        'settings_html_function'         => 'setting_CHECK_BOX(',
                        'sort_order'                         => 3,
					);
				}
				$this->SettingsFields['CONF_PAYMENTMODULE_YM_ACCOUNT'] = array(
                        'settings_value'                 => '',
                        'settings_title'                         => 'Номер кошелька Яндекс',
                        'settings_description'         => 'Только для физических лиц',
                        'settings_html_function'         => 'setting_TEXT_BOX(0,',
                        'sort_order'                         => 7,
                );
				
				$this->SettingsFields['CONF_PAYMENTMODULE_YM_SHOPID'] = array(
                        'settings_value'                 => '',
                        'settings_title'                         => 'Идентификатор вашего магазина в Яндекс.Деньгах (ShopID)',
                        'settings_description'         => 'Только для юридических лиц',
                        'settings_html_function'         => 'setting_TEXT_BOX(0,',
                        'sort_order'                         => 7,
                );
				$this->SettingsFields['CONF_PAYMENTMODULE_YM_SCID'] = array(
                        'settings_value'                 => '',
                        'settings_title'                         => 'Идентификатор витрины вашего магазина в Яндекс.Деньгах (scid)',
                        'settings_description'         => 'Только для юридических лиц',
                        'settings_html_function'         => 'setting_TEXT_BOX(0,',
                        'sort_order'                         => 7,
                );

				$this->SettingsFields['CONF_PAYMENTMODULE_YM_PASSWORD'] = array(
                        'settings_value'                 => '',
                        'settings_title'                         => 'Секретное слово (shopPassword) для обмена сообщениями',
                        'settings_description'         => '',
                        'settings_html_function'         => 'setting_TEXT_BOX(0,',
                        'sort_order'                         => 7,
                );
				
                $this->SettingsFields['CONF_PAYMENTMODULE_YM_STATUS'] = array(
                        'settings_value'                 => '',
                        'settings_title'                         => 'Статус заказа после оплаты',
                        'settings_description'         => 'Укажите, какой статус присваивать заказу после совершения платежа. Рекомендуется установить тот же статус, что установлен в настройках магазина в качестве статуса завершенного заказа. Это позволит работать мгновенной доставке цифрового товара.',
                        'settings_html_function'         => 'setting_ORDER_STATUS_SELECT(',
                        'sort_order'                         => 1,
                );
        }

		 function getModes(){

                return array(
                        array(
                                'title' => 'На счет физического лица в электронной валюте Яндекс.Денег',
                                'value' => '1',
                                ),
                        array(
                                'title' => 'На расчетный счет организации с заключением договора с Яндекс.Деньгами',
                                'value' => '2',
                                ),
                        );
        }

        function after_processing_html( $orderID )
        {	
				$order = ordGetOrder( $orderID );
				
				$this->orderId = $orderID;
				$this->comment = $order['customers_comment'];
				$this->orderTotal = floatval($order["order_amount"] * $order["currency_value"]);
				
				$this->_initVars();
				
				$this->pay_method = $_SESSION['ym_method'];

				$this->userId = $order['customerID'];
				

				$this->createFormHtml();
				
				return $res;
        }

        function before_payment_php( $orderID, $OutSum, $merch)
		{
			$res  = '_before_payment_php_';
			return $res;
        }

		public function createFormHtml(){
			
			if ($this->org_mode){
				$html = '
					<form method="POST" action="'.$this->getFormUrl().'"  id="paymentform" name = "paymentform">
					   <input type="hidden" name="paymentType" value="'.$this->pay_method.'" />
					   <input type="hidden" name="shopid" value="'.$this->shopid.'">
					   <input type="hidden" name="scid" value="'.$this->scid.'">
					    <input type="hidden" name="shopSuccessURL" value="' . getTransactionResultURL('success') . '&InvId=' . $this->orderId  . '" >
					    <input type="hidden" name="shopFailURL" value="' . getTransactionResultURL('failure') . '&InvId=' . $this->orderId  . '" >
					   <input type="hidden" name="orderNumber" value="'.$this->orderId.'">
					   <input type="hidden" name="sum" value="'.$this->orderTotal.'" data-type="number" >
					   <input type="hidden" name="customerNumber" value="'.$this->userId.'" >	
					   <input type="hidden" name="cms_name" value="shopcms" >	
					</form>';
			}else{
				$html = '<form method="POST" action="'.$this->getFormUrl().'"  id="paymentform" name = "paymentform">
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
							<input type="hidden" name="SuccessURL" value="' . getTransactionResultURL('success') . '&InvId=' . $this->orderId  . '" >
							</form>';
			}
			$html .= '<script type="text/javascript">
						document.getElementById("paymentform").submit();
						</script>';
			echo $html; exit;
			return $html;
		}

		public function checkSign($callbackParams){
			$string = $callbackParams['action'].';'.$callbackParams['orderSumAmount'].';'.$callbackParams['orderSumCurrencyPaycash'].';'.$callbackParams['orderSumBankPaycash'].';'.$callbackParams['shopId'].';'.$callbackParams['invoiceId'].';'.$callbackParams['customerNumber'].';'.$this->password;
			$md5 = strtoupper(md5($string));
			return (strtoupper($callbackParams['md5'])==$md5);
		}

		public function sendCode($callbackParams, $code){
			header("Content-type: text/xml; charset=utf-8");
			$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<'.$callbackParams['action'].'Response performedDatetime="'.date("c").'" code="'.$code.'" invoiceId="'.$callbackParams['invoiceId'].'" shopId="'.$this->shopid.'"/>';
			echo $xml;
		}

		public function individualCheck($callbackParams){
			$string = $callbackParams['notification_type'].'&'.$callbackParams['operation_id'].'&'.$callbackParams['amount'].'&'.$callbackParams['currency'].'&'.$callbackParams['datetime'].'&'.$callbackParams['sender'].'&'.$callbackParams['codepro'].'&'.$this->password.'&'.$callbackParams['label'];
			$check = (sha1($string) == $callbackParams['sha1_hash']);
			if (!$check){
				header('HTTP/1.0 401 Unauthorized');
				return false;
			}
			return true;
		
		}

		/* оплачивает заказ */
		public function ProcessResult()
		{
			$callbackParams = $_POST;
			if ($this->org_mode){
			    if ($this->checkSign($callbackParams)){
                    $order = ordGetOrder($callbackParams["orderNumber"]);
                    if(number_format($callbackParams['orderSumAmount'], 2) == number_format(floatval($order["order_amount"] * $order["currency_value"]), 2)){
                        $this->sendCode($callbackParams, 0);
                        return (int)$callbackParams["orderNumber"];
                    }else{
                        $this->sendCode($callbackParams, 100);
                    }
                }else{
                    $this->sendCode($callbackParams, 1);
                }
			}else{
				if ($this->individualCheck($callbackParams)){
					return (int)$callbackParams["label"];
				}
			}
            return false;
		}
		
		public function getFormUrl(){
			if (!$this->org_mode){
				return $this->individualGetFormUrl();
			}else{
				return $this->orgGetFormUrl();
			}
		}

		public function individualGetFormUrl(){
			if ($this->test_mode){
				return 'https://demomoney.yandex.ru/quickpay/confirm.xml';
			}else{
				return 'https://money.yandex.ru/quickpay/confirm.xml';
			}
		}

		public function orgGetFormUrl(){
			if ($this->test_mode){
				return 'https://demomoney.yandex.ru/eshop.xml';
			} else {
				return 'https://money.yandex.ru/eshop.xml';
			}
		}

        function after_payment_php( $orderID, $params){
				$this->_initVars();
				 $order = ordGetOrder( $orderID );
				$order_id = $this->ProcessResult();
				if ($order_id) {
					ostSetOrderStatusToOrder($order_id, $this->_getSettingValue('CONF_PAYMENTMODULE_YM_STATUS'));
				}
				exit;
        }
}
?>