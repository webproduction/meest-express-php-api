<?php
require(dirname(__FILE__).'/../include.php');

$login = 'user1';
$password = 'pass1';
$clientUID = '8458f0b0-930f-11e2-a91e-003048d2b473'; // нужен только для create shipment

$api = new MeestExpress_API($login, $password, $clientUID);

//var_dump($countryUID = $api->getCountryUID('Украина'));
//var_dump($cityUID = $api->getCityUID('Чернигов', $countryUID));
//var_dump($steetID = $api->getSteetUID('Мира', $cityUID));

// генерируем номер заказа
$orderID = rand(2, 9999999);

// генерируем отправку
try {
    $shipment = new MeestExpress_Shipment($api);

    $shipment->setOrderID($orderID);

    // отправитель
    $shipment->setSenderName('Отправитель ФИО или название компании');
    $shipment->setSenderPhone('380504479530');
    $shipment->setSenderService(1); // 1 - от дверей, 0 - со склада
    $shipment->setSenderAddress('Украина', 'Киев', 'Большая Житомирская', 33, 1);

    // получатель
    $shipment->setReceiverName('Получатель ФИО');
    $shipment->setReceiverPhone('380504479531');
    $shipment->setReceiverService(1); // 1 - до дверей, 0 - до склада
    $shipment->setReceiverAddress('Украина', 'Чернигов', 'Мира', '53A', '4');

    // опции оплаты
    $shipment->setPayType(0); // тип платежа: 0 - безнал, 1 - нал
    $shipment->setPayReceiver(0); // 0 - оплачивает отправитель, 1 - получатель

    // опции груза
    $shipment->setSendingFormat('DOX'); // тип отправки: DOX - это конверт
    $shipment->setSendingInsurance(100); // сумма страховки груза
    $shipment->setSendingQuantity(1); // количество мест
    $shipment->setSendingWeight(0.5); // вес

    $shipment->setNotation('комментарий');

    // желаемый адрес доставки
    $shipment->setDeliveryDate(date('Y-m-d'));

    // регистрируем shipment
    // на выходе получим barcode - номер наклейки (нам с ним ничего делать не надо)
    var_dump($api->createShipment($shipment));

    // отправляем
    // на выходе получим номер накладной
    $deliveryNote = $api->createRegister($shipment);
    var_dump($deliveryNote);
} catch (Exception $e) {
    // если что-то будет не так - будет внятный exception
    print $e->getCode();
    print $e->getMessage();
    print_r($e);
}

// отслеживание нашего заказа
var_dump($api->shipmentTracking($orderID)); // НАШ orderID!

print "\n\ndone.\n\n";