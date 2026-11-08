<?php

namespace App\Http\Controllers\Soap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class LibrarySoapController extends Controller
{
    /**
     * 1. Menyajikan dokumen Kontrak WSDL (GET /soap/sipus.wsdl)
     */
    public function wsdl(): Response
    {
        $endpointUrl = url('/soap/sipus');

        $wsdlXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<definitions name="SipusLibrarySoapService"
             targetNamespace="http://sipus.del.ac.id/soap/clearance"
             xmlns:tns="http://sipus.del.ac.id/soap/clearance"
             xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
             xmlns:xsd="http://www.w3.org/2001/XMLSchema"
             xmlns="http://schemas.xmlsoap.org/wsdl/">

    <!-- 1. Message: Spesifikasi parameter input dan output -->
    <message name="CheckClearanceStatusRequest">
        <part name="nim_peminjam" type="xsd:string"/>
    </message>
    <message name="CheckClearanceStatusResponse">
        <part name="nim_peminjam" type="xsd:string"/>
        <part name="nama_peminjam" type="xsd:string"/>
        <part name="active_loans" type="xsd:int"/>
        <part name="is_cleared" type="xsd:boolean"/>
        <part name="status_message" type="xsd:string"/>
    </message>

    <!-- 2. PortType: Daftar operasi (method) yang disediakan -->
    <portType name="SipusClearancePortType">
        <operation name="CheckClearanceStatus">
            <input message="tns:CheckClearanceStatusRequest"/>
            <output message="tns:CheckClearanceStatusResponse"/>
        </operation>
    </portType>

    <!-- 3. Binding: Mengikat operasi ke protokol SOAP over HTTP -->
    <binding name="SipusClearanceSoapBinding" type="tns:SipusClearancePortType">
        <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
        <operation name="CheckClearanceStatus">
            <soap:operation soapAction="http://sipus.del.ac.id/soap/clearance#CheckClearanceStatus"/>
            <input><soap:body use="literal" namespace="http://sipus.del.ac.id/soap/clearance"/></input>
            <output><soap:body use="literal" namespace="http://sipus.del.ac.id/soap/clearance"/></output>
        </operation>
    </binding>

    <!-- 4. Service: Alamat URL fisik tempat endpoint SOAP berada -->
    <service name="SipusLibrarySoapService">
        <port name="SipusClearancePort" binding="tns:SipusClearanceSoapBinding">
            <soap:address location="{$endpointUrl}"/>
        </port>
    </service>
</definitions>
XML;

        return response($wsdlXml, 200)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * 2. Memproses Pesan Masuk SOAP Envelope (POST /soap/sipus)
     */
    public function handle(Request $request): Response
    {
        $rawXml = trim($request->getContent());

        if (empty($rawXml)) {
            return $this->soapFault('soap:Client', 'Body XML SOAP tidak boleh kosong.');
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rawXml);

        if ($xml === false) {
            return $this->soapFault('soap:Client', 'Format XML tidak valid (Malformed XML).');
        }

        // Ambil nilai tag <nim_peminjam> di dalam <CheckClearanceStatusRequest>
        $nimNodes = $xml->xpath('//*[local-name()="CheckClearanceStatusRequest"]/*[local-name()="nim_peminjam"]');

        if (empty($nimNodes)) {
            return $this->soapFault(
                'soap:Client',
                'Operasi tidak dikenali atau elemen <nim_peminjam> tidak ditemukan pada CheckClearanceStatusRequest.'
            );
        }

        $nim = strtoupper(trim((string) $nimNodes[0]));

        if ($nim === '') {
            return $this->soapFault('soap:Client', 'Elemen <nim_peminjam> tidak boleh kosong.');
        }

        // Hitung jumlah buku berstatus 'dipinjam' milik NIM tersebut di tabel peminjamans
        $activeLoans = DB::table('peminjamans')
            ->where('nim_peminjam', $nim)
            ->where('status', 'dipinjam')
            ->count();

        $lastRecord = DB::table('peminjamans')
            ->where('nim_peminjam', $nim)
            ->orderByDesc('id')
            ->first();

        $namaPeminjam = $lastRecord ? $lastRecord->nama_peminjam : 'Mahasiswa IT Del';
        $isCleared = ($activeLoans === 0) ? 'true' : 'false';
        $statusMessage = ($activeLoans === 0)
            ? 'BEBAS PUSTAKA: Tidak ada tanggungan pinjaman buku aktif.'
            : "BELUM BEBAS PUSTAKA: Masih memiliki {$activeLoans} buku berstatus dipinjam.";

        $safeNim  = htmlspecialchars($nim, ENT_XML1, 'UTF-8');
        $safeNama = htmlspecialchars((string) $namaPeminjam, ENT_XML1, 'UTF-8');
        $safeMsg  = htmlspecialchars($statusMessage, ENT_XML1, 'UTF-8');

        $responseXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
               xmlns:m="http://sipus.del.ac.id/soap/clearance">
    <soap:Header>
        <m:ProviderInfo>SIPUS-Del Legacy SOAP Engine v1.0</m:ProviderInfo>
    </soap:Header>
    <soap:Body>
        <m:CheckClearanceStatusResponse>
            <m:nim_peminjam>{$safeNim}</m:nim_peminjam>
            <m:nama_peminjam>{$safeNama}</m:nama_peminjam>
            <m:active_loans>{$activeLoans}</m:active_loans>
            <m:is_cleared>{$isCleared}</m:is_cleared>
            <m:status_message>{$safeMsg}</m:status_message>
        </m:CheckClearanceStatusResponse>
    </soap:Body>
</soap:Envelope>
XML;

        return response($responseXml, 200)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * Helper pembentuk pesan kesalahan standar protokol SOAP (<soap:Fault>)
     */
    private function soapFault(string $faultCode, string $faultMessage): Response
    {
        $safeMsg = htmlspecialchars($faultMessage, ENT_XML1, 'UTF-8');

        $faultXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <soap:Fault>
            <faultcode>{$faultCode}</faultcode>
            <faultstring>{$safeMsg}</faultstring>
        </soap:Fault>
    </soap:Body>
</soap:Envelope>
XML;

        return response($faultXml, 500)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}