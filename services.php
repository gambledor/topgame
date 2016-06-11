<?php

require 'ProtocolTOPGAME.php';

//var_dump(file_get_contents('php://input'));

try {
    $options = array(
        'uri'           => '',
        'soap_version'  => SOAP_1_1,
        'cache_wsdl'    => WSDL_CACHE_NONE,
        //'features'      => SOAP_WAIT_ONE_WAY_CALLS,
        //'encondig'      => 'UTF-8',
        'style'         => SOAP_DOCUMENT,
        'use'           => SOAP_LITERAL
    );

    $server = new SOAPServer(__DIR__ . '/wsdl/topgame.wsdl', $options);
    $server->setObject(new ProtocolTOPGAME($debug));
    $server->handle();
} catch (SOAPFault $f) {
    print $f->getTrace();
}
/* End of file services.php */
