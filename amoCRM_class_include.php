<?php
/**
 * Created by PhpStorm 9!!!
 * User: Dmitry P Rasegaev
 * skype: rsdimka
 * email: dr@tracebs.ru
 * Date: 21.12.2015
 * Time: 11:01
 */
class amoCRMclassV1 {
    //Общие параметры для работы класса
    var $Cookie = "";
    var $UserName = "";
    var $SubDomain = "";
    var $ApiKey = "";
    //init class авторизацией
    public $AuthStatus = FALSE;
    public $AuthError = "";

    public function amoCRMclassV1($strCookiefile,$strUserName,$strSubDomain,$strApiKey) {
        $this->Cookie = $strCookiefile;
        $this->UserName = $strUserName;
        $this->SubDomain = $strSubDomain;
        $this->ApiKey = $strApiKey;
        // почти copy-paste из документации (((
        //Массив с параметрами, которые нужно передать методом POST к API системы
        $user=array(
            'USER_LOGIN'=>$this->UserName, #Ваш логин (электронная почта)
            'USER_HASH'=>$this->ApiKey #Хэш для доступа к API (смотрите в профиле пользователя)
        );

        $subdomain=$this->SubDomain; #Наш аккаунт - поддомен

        #Формируем ссылку для запроса
        $link='https://'.$subdomain.'.amocrm.ru/private/api/auth.php?type=json';

        $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
        #Устанавливаем необходимые опции для сеанса cURL
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client');
        curl_setopt($curl,CURLOPT_URL,$link);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($user));
        curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_COOKIEFILE, $this->Cookie); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_COOKIEJAR, $this->Cookie); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

        $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
        $code=curl_getinfo($curl,CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
        curl_close($curl); #Завершаем сеанс cURL

        $code=(int)$code;
        $errors=array(
            301=>'Moved permanently',
            400=>'Bad request',
            401=>'Unauthorized',
            403=>'Forbidden',
            404=>'Not found',
            500=>'Internal server error',
            502=>'Bad gateway',
            503=>'Service unavailable'
        );

        try
        {
            #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
            if($code!=200 && $code!=204) {
                $this->AuthStatus = FALSE;
                $this->AuthError = 'AmoCRM error: ' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error ' . $code);
            }
        }
        catch(Exception $E)
        {
            $this->AuthStatus = FALSE;
            $this->AuthError = 'AmoCRM error: ' . $E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode();
        }

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
         */
        $Response=json_decode($out,true);

        $Response=$Response['response'];
        if(isset($Response['auth'])) {
            #Флаг авторизации доступен в свойстве "auth"
            $this->AuthStatus = TRUE;
        } else {
            $this->AuthStatus = FALSE;
            $this->AuthError = 'AmoCRM error: ' . 'Авторизация не удалась';
        }
        # ))) почти copy-paste из документации
    }
}
?>