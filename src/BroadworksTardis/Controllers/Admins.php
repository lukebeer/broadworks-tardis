<?php
require_once '../Broadworks_OCI-P/common.php';
Factory::getOCISchemaServiceProvider();
if (!isset($argv[1])) die("Provide service provider id as second argument.\n");

$mongo = new MongoClient('mongodb://pillock.net:27017');
$collection = $mongo->selectDB($argv[1])->selectCollection('ServiceProviderAdminGetListRequest14');

$client = CoreFactory::getOCIClient('http://bsews1.ipt.intechnology.co.uk/webservice/services/ProvisioningService');
$client->login('luke_script', 'waiodjjAWDOAWdlaiojhawiopjh4');
$client->send(OCISchemaServiceProvider::ServiceProviderAdminGetListRequest14($argv[1]));

if ($client->getResponse()) {
    $keys = $client->getResponse()->serviceProviderAdminTable['colHeading'];
    foreach ($client->getResponse()->serviceProviderAdminTable['row'] as $row) {
        $collection->insert(array_combine($keys, $row['col']));
    }
}