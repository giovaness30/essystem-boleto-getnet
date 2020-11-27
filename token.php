<?php
/* Está linha é necessaria para importamos tudo o que está na pasta VENDOR (são nossos pacotes do packagist) */
require __DIR__ . '/vendor/autoload.php';

// IMPORTA AS CHAVES COLOCADO NO PAINEL DA GATEWAY
$vendedorClientId = $this->client_id;
$vendedorClientSecret = $this->client_secret;

$concatenation = $vendedorClientId. ":". $vendedorClientSecret;

/* ESSE TOKEN É DA AUTENTICAÇÃO OAUTH */
$token = base64_encode($concatenation);

/* Criando um client (Ele é a classe que faz as requisições ) */
/* base_uri URL BASE */
$client = new \GuzzleHttp\Client([
    'base_uri' => $this->api_url, 
    'headers' => [
        'Authorization' => 'Basic ' . $token,        
        'Content-Type' => 'application/x-www-form-urlencoded',
    ]
]);

/*  TIPO METODO E NOME DO METODO */
$response = $client->post('/auth/oauth/v2/token', [
'form_params' => ['scope'=>'oob', 'grant_type'=>'client_credentials']
]);

$codigoRetorno = $response->getStatusCode();

$corpoRetorno = json_decode($response->getBody()->getContents('access_token'));

$accessToken = $corpoRetorno->{'access_token'};