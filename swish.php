<?php

/**
 * WHMCS Swish Payment Module
 * Author: https://lazyllama.xyz
 * Version: 1.0
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function swish_MetaData()
{
    return array(
        'DisplayName' => 'WHMCS Swish Payment Module',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function swish_config()
{
    return array(
        // Namnet på modulen
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'WHMCS Swish Payment Module',
        ),
        
        // Nummeret på den som ska ta emot pengarna
        'swishNumber' => array(
            'FriendlyName' => 'Mottagares Swish-nummer',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Skriv in det telefonnummer som är kopplat till ditt Swish-konto',
        ),
        
        // Meddelandet som visas i Swish-appen och för att identifiera betalningen som mottagare
        'swishMessage' => array(
            'FriendlyName' => 'Swish Meddelande',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'LlamaHost - Faktura #{invoice_num}',
            'Description' => 'Meddeladet som används för att identifiera betalningen. Placeholders: <em>{invoice_num}, {c_firstname}, {c_lastname}</em>.',
        ),
        
        // Test Mode
        /*
        'testMode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Ja/Nej inte så svårt',
        ),
        */
    );
}

function swish_link($params)
{
    // Module Configgen
    $testMode = $params['testMode'];
    $swishNumber = $params['swishNumber'];
    $swishMessage = $params['swishMessage'];

    // Klient Info för att byta ut placeholders
    $clientFirstname = $params['clientdetails']['firstname'];
    $clientLastname = $params['clientdetails']['lastname'];

    // Byter ut placeholders med motsvarande värden
    $firstSwishMessage = str_replace("{invoice_num}", $params['invoiceid'], $swishMessage);
    $secondSwishMessage = str_replace("{c_firstname}", $clientFirstname, $firstSwishMessage);
    $thirdSwishMessage = str_replace("{c_lastname}", $clientLastname, $secondSwishMessage);


    // Pris och fakturanummer
    $invoiceId = $params['invoiceid'];
    $amount = $params['amount'];

    // Swish Fina API
    $reqUrl = 'https://mpc.getswish.net/qrg-swish/api/v1/prefilled';

    // Json Arrayen som skickas till Swish
    $data = array(
        'format' => 'svg',
        'transparent' => true,
        'payee' => array(
            "value" => $swishNumber,
            "editable" => false
        ),
        'amount' => array(
            "value" => $amount,
            "editable" => false
        ),
        'message' => array(
            "value" => $thirdSwishMessage,
            "editable" => false
        ),
    );

    // Kombinerar ihop all data och skickar till Swish
    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => json_encode($data),
            'header' => "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n"
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($reqUrl, false, $context);

    // Ifall det går åt pipsvängen så dör den
    if (!$result) {
        die("Error: Failed to make the API request.");
    }

    $htmlOutput = $result;
    $htmlOutput .= '<br><span class="small-text"><em>Efter att betalningen har mottagits kommer din faktura att manuellt markeras som betald.</em></span>';

    // Mission Completed - Returnerar HTML
    return $htmlOutput;
}