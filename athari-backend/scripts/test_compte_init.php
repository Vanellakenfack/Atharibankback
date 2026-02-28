<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

$user = App\Models\User::first();
if (!$user) {
    echo "NO_USER\n";
    exit(0);
}

Auth::loginUsingId($user->id);

// Test the full compte init endpoint
$request = Request::create('/api/comptes/init', 'GET', ['agence_id' => 3]);
$request->setUserResolver(fn() => $user);

try {
    $controller = app(App\Http\Controllers\CompteController::class);
    $response = $controller->initOuverture($request);
    
    if (is_object($response) && method_exists($response, 'getContent')) {
        echo $response->getContent() . "\n";
    } else {
        var_export($response);
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
