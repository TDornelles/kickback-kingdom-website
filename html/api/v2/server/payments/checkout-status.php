<?php
declare(strict_types=1);

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . "/../../../..") . "/Kickback/init.php");

use Kickback\Common\Exceptions\ThrowableOverrides;
use Kickback\Backend\Models\Response;
use Kickback\BackendV2\Controllers\PaymentController;
use Kickback\Services\ApiV2\Endpoint;

\header('Content-Type: application/json');

Endpoint::begin();
try
{
    $sessionAccount = Endpoint::requireAccountSession();
    $request_contents_json = Endpoint::file_get_contents('php://input');
    $response = null;
    $paymentController = new PaymentController();
    $response_code = $paymentController->getCheckoutStatus($sessionAccount, $request_contents_json, $response);
    if ( $response_code !== 0 ) {
        \http_response_code($response_code);
    }
}
catch( \Throwable $e )
{
    $code = ThrowableOverrides::code($e);
    $code = ($code === 0) ? 500 : $code;
    \http_response_code($code);
    $response = new Response(false,
        'Failed to get checkout status: ' . ThrowableOverrides::message($e),
        null);
}
finally {
    Endpoint::end();
}

echo \json_encode($response);
