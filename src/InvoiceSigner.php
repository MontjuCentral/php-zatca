<?php

namespace Montju\Zatca;

use Montju\Zatca\Exceptions\ZatcaStorageException;
use Montju\Zatca\Helpers\QRCodeGenerator;
use Montju\Zatca\Helpers\Certificate;
use Montju\Zatca\Helpers\InvoiceExtension;
use Montju\Zatca\Helpers\InvoiceSignatureBuilder;

class InvoiceSigner
{
    private $signedInvoice;  // Signed invoice XML string
    private $hash;           // Invoice hash (base64 encoded)
    private $qrCode;         // QR Code (base64 encoded)
    private $certificate;    // Certificate used for signing
    private $digitalSignature; // Digital signature (base64 encoded)

    // Private constructor to force usage of signInvoice method
    private function __construct() {}

    /**
     * Signs the invoice XML and returns an InvoiceSigner object.
     *
     * @param string      $xmlInvoice  Invoice XML as a string
     * @param Certificate $certificate Certificate for signing
     * @return self
     */
    public static function signInvoice(string $xmlInvoice, Certificate $certificate): self
    {
        $instance = new self();
        $instance->certificate = $certificate;

        // Convert XML string to DOM
        $xmlDom = InvoiceExtension::fromString($xmlInvoice);
        $invoiceElement = $xmlDom->getElement();
        $dom = $invoiceElement->ownerDocument;

        // Remove unwanted tags per guidelines
        $xmlDom->removeByXpath('ext:UBLExtensions');
        $xmlDom->removeByXpath('cac:Signature');
        $xmlDom->removeParentByXpath('cac:AdditionalDocumentReference/cbc:ID[. = "QR"]');

        // Compute hash using SHA-256
        $invoiceHashBinary = hash('sha256', $invoiceElement->C14N(false, false), true);
        $instance->hash = base64_encode($invoiceHashBinary);

        // Create digital signature using the private key
        $instance->digitalSignature = base64_encode(
            $certificate->getPrivateKey()->sign($invoiceHashBinary)
        );

        // Build UBL Extension XML (as string)
        $ublExtensionXml = (new InvoiceSignatureBuilder)
            ->setCertificate($certificate)
            ->setInvoiceDigest($instance->hash)
            ->setSignatureValue($instance->digitalSignature)
            ->buildSignatureXml();

        // Import UBL Extension as DOM
        $ublDom = new \DOMDocument();
        $ublDom->loadXML('<?xml version="1.0" encoding="UTF-8"?><ext:UBLExtensions xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">' . $ublExtensionXml . '</ext:UBLExtensions>');
        $ublExtensionsNode = $dom->importNode($ublDom->documentElement, true);

        // Insert UBL Extension before <cbc:ProfileID>
        $profileIdNode = $invoiceElement->getElementsByTagNameNS(
            'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
            'ProfileID'
        )->item(0);

        if ($profileIdNode) {
            $invoiceElement->insertBefore($ublExtensionsNode, $profileIdNode);
        }

        // Generate QR Code
        $instance->qrCode = QRCodeGenerator::createFromTags(
            $xmlDom->generateQrTagsArray($certificate, $instance->hash, $instance->digitalSignature)
        )->encodeBase64();

        // Get QR node as XML and import it
        $qrDom = new \DOMDocument();
        $qrNodeXml = $instance->getQRNode($instance->qrCode);
        $qrDom->loadXML('<?xml version="1.0" encoding="UTF-8"?><root xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">' . $qrNodeXml . '</root>');
        $qrNode = $dom->importNode($qrDom->documentElement->firstChild, true);

        // Insert QR before <cac:AccountingSupplierParty>
        $supplierNode = $invoiceElement->getElementsByTagNameNS(
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            'AccountingSupplierParty'
        )->item(0);

        if ($supplierNode) {
            $invoiceElement->insertBefore($qrNode, $supplierNode);
        }

        // Finalize signed invoice
        $instance->signedInvoice = $dom->saveXML();

        return $instance;
    }


    /**
     * Saves the signed invoice as an XML file.
     *
     * @param string $filename (Optional) File path to save the XML.
     * @param string|null $outputDir (Optional) Directory name. Set to null if $filename contains the full file path.
     * @return self
     * @throws ZatcaStorageException If the XML file cannot be saved.
     */
    public function saveXMLFile(string $filename = 'signed_invoice.xml', ?string $outputDir = 'output'): self
    {
        (new Storage($outputDir))->put($filename, $this->signedInvoice);
        return $this;
    }

    /**
     * Get the signed XML string.
     *
     * @return string
     */
    public function getXML(): string
    {
        return $this->signedInvoice;
    }

    /**
     * Returns the QR node string.
     *
     * @param string $QRCode
     * @return string
     */
    private function getQRNode(string $QRCode): string
    {
        return "<cac:AdditionalDocumentReference>
        <cbc:ID>QR</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode=\"text/plain\">$QRCode</cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    <cac:Signature>
        <cbc:ID>urn:oasis:names:specification:ubl:signature:Invoice</cbc:ID>
        <cbc:SignatureMethod>urn:oasis:names:specification:ubl:dsig:enveloped:xades</cbc:SignatureMethod>
    </cac:Signature>";
    }
    /**
     * Get signed invoice XML.
     *
     * @return string
     */
    public function getInvoice(): string
    {
        return $this->signedInvoice;
    }

    /**
     * Get invoice hash.
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Get QR Code.
     *
     * @return string
     */
    public function getQRCode(): string
    {
        return $this->qrCode;
    }

    /**
     * Get the certificate used for signing.
     *
     * @return Certificate
     */
    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }
}
