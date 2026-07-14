<?php

declare(strict_types=1);

namespace zni\clients;

use RuntimeException;

class RegixClient
{
    private string $endpoint;
    private string $certPath;
    private string $keyPath;

    public function __construct()
    {
        $appDir = dirname(__DIR__, 4);

        $this->endpoint = 'https://service-regix.egov.bg/RegiXEntryPointV2.svc/basic';
        $this->certPath = $appDir . '/storage/regixCertificates/cert.crt';
        $this->keyPath  = $appDir . '/storage/regixCertificates/private.key';
    }


    protected function send(string $xml): string
    {
        $ch = curl_init($this->endpoint);

        if (!$ch) {
            throw new RuntimeException('Cannot init curl');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "http://egov.bg/RegiX/IRegiXEntryPointV2/Execute"',
            ],

            CURLOPT_SSLCERT        => $this->certPath,
            CURLOPT_SSLKEY         => $this->keyPath,

            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException($error);
        }

        curl_close($ch);


        return (string) $response;
    }

    protected function parse(string $xml): array
    {
        $simpleXml = @simplexml_load_string($xml, null, LIBXML_NOCDATA);

        if ($simpleXml === false) {
            return [];
        }

        $json = json_encode($simpleXml);

        if ($json === false) {
            return [];
        }

        return json_decode($json, true) ?: [];
    }

    public function getEmploymentContracts(string $id, string $type, string $date): array
    {
        // phpcs:disable Generic.Files.LineLength
        $xml = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
 xmlns:reg="http://egov.bg/RegiX" xmlns:sig="http://egov.bg/RegiX/SignedData">
   <soapenv:Header/>
   <soapenv:Body>
      <reg:Execute>
         <reg:request>
            <sig:ServiceRequestData>
               <sig:Operation>TechnoLogica.RegiX.NRAEmploymentContractsAdapter.APIService.INRAEmploymentContractsAPI.GetEmploymentContracts</sig:Operation> 
               <sig:Argument>
               <EmploymentContractsRequest xmlns="http://egov.bg/RegiX/NRA/EmploymentContracts/Request">
                    <Identity>
                        <ID>{$id}</ID>
                        <TYPE>{$type}</TYPE>
                    </Identity>
                    <ContractsFilter>All</ContractsFilter>
                    <DateTo>{$date}</DateTo>
                    <Page>0</Page>
                    <Size>0</Size>
                </EmploymentContractsRequest>
               </sig:Argument>
               <SignResult>true</SignResult>
               <ReturnAccessMatrix>false</ReturnAccessMatrix>
            </sig:ServiceRequestData>
         </reg:request>
      </reg:Execute>
   </soapenv:Body>
</soapenv:Envelope>
XML;
        // phpcs:enable Generic.Files.LineLength
        $responseXml = $this->send($xml);

        return [
            'raw'  => $responseXml,
            'data' => $this->parseEmploymentContractsResponse($responseXml),
        ];
    }

    protected function parseEmploymentContractsResponse(string $xml): array
    {

        $xml = trim($xml, '"');
        $xml = html_entity_decode($xml, ENT_QUOTES | ENT_XML1, 'UTF-8');

        if (!preg_match('/<EmploymentContractsResponse.*?<\/EmploymentContractsResponse>/s', $xml, $matches)) {
            throw new \RuntimeException('Cannot extract EmploymentContractsResponse');
        }

        $innerXml = $matches[0];


        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($innerXml);
        if (!$sx) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                echo "LibXML error: ", trim($error->message), "\n";
            }
            throw new \RuntimeException('Invalid XML inside EmploymentContractsResponse');
        }


        $json = json_encode($sx);
        if ($json === false) {
            return [];
        }

        return json_decode($json, true) ?: [];
    }

    protected function xmlNodeToArray(\SimpleXMLElement $node): array
    {
        $json = json_encode($node);
        if ($json === false) {
            return [];
        }

        return json_decode($json, true) ?: [];
    }
}
