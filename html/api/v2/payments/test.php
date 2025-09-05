<?php
declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/../../Kickback/init.php");
    
    use Kickback\Services\Session;
    use Kickback\Services\StripeService;
    
    // Test basic functionality
    $tests = [];
    
    // Test 1: Session
    Session::ensureSessionStarted();
    $tests['session_started'] = true;
    $tests['logged_in'] = Session::isLoggedIn();
    $tests['account_id'] = Session::getAccountId();
    
    // Test 2: Stripe Configuration
    $tests['stripe_configured'] = StripeService::isConfigured();
    if (StripeService::isConfigured()) {
        $tests['stripe_publishable_key'] = substr(StripeService::getPublishableKey(), 0, 20) . '...';
    }
    
    // Test 3: Request data
    $input = json_decode(file_get_contents('php://input'), true);
    $tests['request_method'] = $_SERVER['REQUEST_METHOD'];
    $tests['content_type'] = $_SERVER['CONTENT_TYPE'] ?? 'not set';
    $tests['input_data'] = $input;
    
    echo json_encode([
        'status' => 'success',
        'tests' => $tests,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}