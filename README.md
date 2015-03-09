#yandexmoney-shopcms

Модуль оплаты yandexmoney-shopcmsнеобходим для интеграции с сервисом [Яндекс.Касса](http://kassa.yandex.ru/) на базе CMS shopCMS. 

Доступные платежные методы, если вы работаете как юридическое лицо:
* **Банковские карты** -  Visa (включая Electron), MasterCard и Maestro любого банка мира
* **Электронные деньги** - Яндекс.Деньги и WebMoney
* **Наличные** - [Более 170 тысяч пунктов](https://money.yandex.ru/pay/doc.xml?id=526209) оплаты по России
* **Баланс телефона** - Билайн, МегаФон и МТС
* **Интернет банкинг** - Альфа-Клик и Сбербанк Онлайн

###Установка модуля
Для установки данного модуля необходимо;
* переместить папку `core` из [архива](https://github.com/yandex-money/yandex-money-cms-shopcms/archive/master.zip) в корень Вашего сайта
* инсталлировать YandexMoney (перейти в раздел `Модули` - `Модули оплаты` - `Инсталлировать`)
* перейти к редактированию установленного модуля (`Модули` - `Модули оплаты` - `YandexMoney` - `Редактировать`) и внести нужные настройки
* добавить новый вариант оплаты (`Настройки` - `Варианты оплаты`, модуль YandexMoney)
* в файле `core/includes/helper.php` добавить код:

```
// Helper for YandexMoney (result)
  if ($_REQUEST["yandexmoney"] == 'yes'){
        $orderID = (int) $_REQUEST["orderNumber"];
        $q = db_query( "select paymethod  from ".ORDERS_TABLE." where orderID=".$orderID);
        $order = db_fetch_row($q);
        if ( $order )
        {
            $paymentMethod = payGetPaymentMethodById( $order["paymethod"] );
            $currentPaymentModule = modGetModuleObj( $paymentMethod["module_id"], PAYMENT_MODULE );
            if ( $currentPaymentModule != null ) {
    $result = $currentPaymentModule->after_payment_php( $orderID, $_REQUEST);
   }
  }
  }
```

Пожалуйста, обязательно делайте бекапы!

###Нашли ошибку или у вас есть предложение по улучшению модуля?
Пишите нам cms@yamoney.ru
