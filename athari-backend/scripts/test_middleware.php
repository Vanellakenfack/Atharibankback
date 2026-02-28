<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

try {
    $user = App\Models\User::first();
    if (! $user) {
        echo "NO_USER\n";
        exit(0);
    }

    Auth::loginUsingId($user->id);

    $request = Illuminate\Http\Request::create('/test', 'GET', ['agence_id' => 9]);

    $mw = new App\Http\Middleware\CheckAgenceOuverte();

    $res = $mw->handle($request, function ($r) {
        return response()->json(['next' => true]);
    });

    if (is_object($res) && method_exists($res, 'getContent')) {
        echo $res->getContent() . "\n";
    } elseif (is_string($res)) {
        echo $res . "\n";
    } else {
        var_export($res);
    }
} catch (Exception $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
}
