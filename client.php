
<?php

$options = array(
    'trace' => true,
    'exception' => true,
    'soap_version'=> SOAP_1_1,
    'uri' => 'http://tempuri.org/',
    'location' => 'http://www.ungmcasino.com/integrations/topgame/wsdl/topgame.wsdl',
    'style' => SOAP_DOCUMENT,
    'use'   => SOAP_LITERAL
);

try {
    //$client = new SOAPClient(null, $options);
    $client = new SOAPClient('http://www.ungmcasino.com/integrations/topgame/wsdl/topgame.wsdl');
    var_dump($client->__getFunctions());

    $request = new stdClass();
    $request->GetBalance = new stdClass();
    $request->GetBalance->request = 11;
    $client->GetBalance($request);

    /*var_dump($request);
    $response = $client->getUserInfo($request);
    var_dump($response);
    //var_dump($client->__getLastRequest());
    //var_dump($client->__getLastResponse());*/

} catch (\Exception $e) {
    echo $client->__getLastRequest();
    echo $client->__getLastResponse();
    echo $e->getMessage();
}
