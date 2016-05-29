<?php
/**
 * API Meest Express
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   MeestExpress
 */
class MeestExpress_API {

    public function __construct($login, $password, $clientUID, $language = 'ru') {
        $this->_login = $login;
        $this->_password = $password;
        $this->_clientUID = $clientUID;
        $this->_language = strtoupper($language);
    }

    public function createShipment(MeestExpress_Shipment $shipment) {
        $request = '<Shipments>
        <CreateShipment>
        <ClientsShipmentRef>'.$this->_xmlEscape($shipment->getOrderID()).'</ClientsShipmentRef>
        <ClientUID>'.$this->_clientUID.'</ClientUID>

        <Sender>'.$this->_xmlEscape($shipment->getSenderName()).'</Sender>
        <SenderService>'.$shipment->getSenderService().'</SenderService>
        <SenderStreet_UID>'.$shipment->getSenderStreetUID().'</SenderStreet_UID>
        <SenderHouse>'.$this->_xmlEscape($shipment->getSenderHouse()).'</SenderHouse>
        <SenderFlat>'.$this->_xmlEscape($shipment->getSenderFlat()).'</SenderFlat>
        <SenderTel>'.$this->_xmlEscape($shipment->getSenderPhone()).'</SenderTel>

        <Receiver>'.$this->_xmlEscape($shipment->getReceiverName()).'</Receiver>
        <ReceiverService>'.$shipment->getReceiverService().'</ReceiverService>
        <ReceiverBranch_UID/>
        <ReceiverStreet_UID>'.$shipment->getReceiverStreetUID().'</ReceiverStreet_UID>
        <ReceiverHouse>'.$this->_xmlEscape($shipment->getReceiverHouse()).'</ReceiverHouse>
        <ReceiverFlat>'.$this->_xmlEscape($shipment->getReceiverFlat()).'</ReceiverFlat>
        <ReceiverFloor>'.$this->_xmlEscape($shipment->getReceiverFloor()).'</ReceiverFloor>
        <ReceiverTel>'.$this->_xmlEscape($shipment->getReceiverPhone()).'</ReceiverTel>

        <Notation>'.$this->_xmlEscape($shipment->getNotation()).'</Notation>
        <Receiver_Pay>'.$this->_xmlEscape($shipment->getPayReceiver()).'</Receiver_Pay>
        <TypePay>'.$this->_xmlEscape($shipment->getPayType()).'</TypePay>

        <Places_items>
            <SendingFormat>'.$this->_xmlEscape($shipment->getSendingFormat()).'</SendingFormat>
            <Quantity>'.$this->_xmlEscape($shipment->getSendingQuantity()).'</Quantity>
            <Weight>'.$this->_xmlEscape($shipment->getSendingWeight()).'</Weight>
            <Insurance>'.$this->_xmlEscape($shipment->getSendingInsurance()).'</Insurance>
        </Places_items>
        ';

        if ($shipment->getDeliveryDate()) {
            $date = date('d.m.Y', strtotime($shipment->getDeliveryDate()));
            $request .= '<PlanDeliveryDate>'.$date.'</PlanDeliveryDate>';
        }

        // $request .= '<PlanDeliveryTimeFrom>10:00</PlanDeliveryTimeFrom>';
        // $request .= '<PlanDeliveryTimeTo>14:00</PlanDeliveryTimeTo>';

        $request .= '</CreateShipment>
        </Shipments>';

        $result = $this->_requestDocument(
            'CreateShipments',
            $request,
            '', // requestID не передаем при wait=1
            1 // wait=1
        );

        return trim($result->result_table->items->BarCode.'');
    }

    public function createRegister(MeestExpress_Shipment $shipment) {
        $request = '<CreateRegister>
        <ClientUID>'.$this->_clientUID.'</ClientUID>
        <Shipments>
            <ClientsShipmentRef>'.$this->_xmlEscape($shipment->getOrderID()).'</ClientsShipmentRef>
        </Shipments>
        </CreateRegister>';

        $result = $this->_requestDocument(
            'CreateRegister',
            $request,
            '', // requestID не передаем при wait=1
            1 // wait=1
        );

        return trim($result->result_table->items->RegiterNumber.'');
    }

    public function shipmentTracking($orderID) {
        $orderID = addslashes($orderID);

        $result = $this->_requestQuery(
            'ShipmentTracking',
            "ClientUID='{$this->_clientUID}' AND ClientShipmentRef = '{$orderID}'",
            ''
        );

        if (!isset($result->result_table->items[0])) {
            throw new MeestExpress_Exception('No result for '.$orderID);
        }

        return array(
            $result->result_table->items->StatusCode.'',
            $result->result_table->items->StatusUA.'',
        );
    }

    public function getCountryUID($country) {
        $country = addslashes($country);

        $result = $this->_requestQuery(
            'Country',
            "Description{$this->_language} LIKE '{$country}'",
            ''
        );

        if (isset($result->result_table->items[1])) {
            throw new MeestExpress_Exception('Too many results for '.$country);
        }

        if (!isset($result->result_table->items[0])) {
            throw new MeestExpress_Exception('No result for '.$country);
        }

        return trim($result->result_table->items->uuid.'');
    }

    public function getCityUID($city, $countryUID) {
        $city = addslashes($city);

        $result = $this->_requestQuery(
            'City',
            "Description{$this->_language} LIKE '{$city}' AND Countryuuid='{$countryUID}'",
            ''
        );

        if (isset($result->result_table->items[1])) {
            throw new MeestExpress_Exception('Too many results for '.$city);
        }

        if (!isset($result->result_table->items[0])) {
            throw new MeestExpress_Exception('No result for '.$city);
        }

        return trim($result->result_table->items->uuid.'');
    }

    public function getSteetUID($street, $cityUID) {
        $street = addslashes($street);

        $result = $this->_requestQuery(
            'Address',
            "Description{$this->_language} LIKE '{$street}' AND Cityuuid='{$cityUID}'",
            ''
        );

        /*if (isset($result->result_table->items[1])) {
            throw new MeestExpress_Exception('Too many results for '.$street);
        }*/

        if (!isset($result->result_table->items[0])) {
            throw new MeestExpress_Exception('No result for '.$street);
        }

        return trim($result->result_table->items->uuid.'');
    }

    public function formatStreetMeest($string) {
        $string = preg_replace('/Аллея|Аллея\,|Аллея\.|Аллея\-/ius', '', $string);
        $string = preg_replace(
            '/Бульвар|Бульвар\,|Бульвар\.|Бульвар\-|бул\s|бул\.|бул\,|б-р|б-р\,|б-р\./ius',
            '',
            $string
        );
        $string = preg_replace('/Вал\s|Вал\,|Вал\.|Вал\-/ius', '', $string);
        $string = preg_replace('/Взвоз\s|Взвоз\,|Взвоз\.|Взвоз\-/ius', '', $string);
        $string = preg_replace('/Въезд\s|Въезд\,|Въезд\.|Въезд\-/ius', '', $string);
        $string = preg_replace('/Дорога\s|Дорога\,|Дорога\.|Дорога\-|дор\s|дор\,|дор\./ius', '', $string);
        $string = preg_replace('/Заезд\s|Заезд\,|Заезд\.|Заезд\-/ius', '', $string);
        $string = preg_replace('/Кольцо\s|Кольцо\,|Кольцо\.|Кольцо\-/ius', '', $string);
        $string = preg_replace('/Линия\s|Линия\,|Линия\.|Линия\-/ius', '', $string);
        $string = preg_replace('/линнея\s|линнея\,|линнея\.|линнея\-/ius', '', $string);
        $string = preg_replace('/Луч\s|Луч\,|Луч\.|Луч\-/ius', '', $string);
        $string = preg_replace(
            '/Магистраль\s|Магистраль\,|Магистраль\.|Магистраль\-|маг\s|маг\,|маг\./ius',
            '',
            $string
        );
        $string = preg_replace('/Переулок\s|Переулок\,|Переулок\.|Переулок\-|пер\s|пер\,|пер\./ius', '', $string);
        $string = preg_replace('/Площадь\s|Площадь\,|Площадь\.|Площадь\-|пл\s|пл\,|пл\./ius', '', $string);
        $string = preg_replace(
            '/Проезд\s|Проезд\,|Проезд\.|Проезд\-|пр-д\s|пр-д\.|пр-д\,|пр\s|пр\,|пр\./ius',
            '',
            $string
        );
        $string = preg_replace(
            '/Проспект\s|Проспект\,|Проспект\.|Проспект\-|просп\s|просп\.|просп\,|пр-кт\s|пр-кт\,|пр-кт\./ius',
            '',
            $string
        );
        $string = preg_replace('/Проулок\s|Проулок\,|Проулок\.|Проулок\-/ius', '', $string);
        $string = preg_replace('/Разъезд\s|Разъезд\,|Разъезд\.|Разъезд\-/ius', '', $string);
        $string = preg_replace('/Ряд\s|Ряд\,|Ряд\.|Ряд\-/ius', '', $string);
        $string = preg_replace('/Спуск\s|Спуск\,|Спуск\.|Спуск\-/ius', '', $string);
        $string = preg_replace('/Съезд\s|Съезд\,|Съезд\.|Съезд\-/ius', '', $string);
        $string = preg_replace('/Территория\s|Территория\,|Территория\.|Территория\-/ius', '', $string);
        $string = preg_replace('/Тракт\s|Тракт\,|Тракт\.|Тракт\-/ius', '', $string);
        $string = preg_replace('/Тупик\s|Тупик\,|Тупик\.|Тупик\-|туп\s|туп\,|туп\./ius', '', $string);
        $string = preg_replace('/Вулиця\s|Вулиця\,|Вулиця\.|Вулиця\-|вул\s|вул\,|вул\./ius', '', $string);
        $string = preg_replace('/Улица\s|Улица\,|Улица\.|Улица\-|ул\s|ул\,|ул\./ius', '', $string);
        $string = preg_replace('/Шоссе\s|Шоссе\,|Шоссе\.|Шоссе\-|ш\s|ш\,|ш\./ius', '', $string);

        return trim($string);
    }

    private function _requestDocument($function, $request, $requestID, $wait) {
        // формируем подпись
        $sign = md5($this->_login.$this->_password.$function.$request.$requestID.$wait);

        // формируем XML
        $xml = '<?xml version="1.0" encoding="utf-8"?>
        <param>
        <login>'.$this->_login.'</login>
        <function>'.$function.'</function>
        <request>'.$request.'</request>
        <request_id>'.$requestID.'</request_id>
        <wait>'.$wait.'</wait>
        <sign>'.$sign.'</sign>
        </param>';

        // отправляем запрос
        return $this->_requestXML($xml, 'http://api1c.meest-group.com/services/1C_Document.php');
    }

    private function _requestQuery($function, $where, $order) {
        // формируем подпись
        $sign = md5($this->_login.$this->_password.$function.$where.$order);

        // формируем XML
        $xml = '<?xml version="1.0" encoding="utf-8"?>
        <param>
        <login>' . $this->_login .'</login>
        <function>' . $function .'</function>
        <where>' . $where .'</where>
        <order>' . $order .'</order>
        <sign>' . $sign .'</sign>
        </param>';

        // отправляем запрос
        return $this->_requestXML($xml, 'http://api1c.meest-group.com/services/1C_Query.php');
    }

    private function _requestXML($xmlString, $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            throw new MeestExpress_Exception('Empty response');
        }

        $xml = simplexml_load_string($response);

        $resultCode = trim($xml->errors->code.'');
        $resultMessage = trim($xml->errors->name.'');

        if ($resultCode == '000') {
            return $xml;
        }

        throw new MeestExpress_Exception($resultMessage, $resultCode);
    }

    private function _xmlEscape($string) {
        return htmlspecialchars($string);
    }

    private $_login;

    private $_password;

    private $_clientUID;

    private $_language;

}