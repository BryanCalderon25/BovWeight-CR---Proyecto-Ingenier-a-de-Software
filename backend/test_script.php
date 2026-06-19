<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Mail\CorreoRecuperacion;

$email = 'test_reset@example.com';
$user = User::updateOrCreate(['email' => $email], [
    'name' => 'Test User',
    'password' => Hash::make('Password123')
]);

Mail::fake();

echo "---------------------------------\n";
echo "CASO 1 & 2: Testing forgotPassword\n";
$request = Request::create('/api/forgot-password', 'POST', ['email' => $email]);
$response = app()->handle($request);
echo "Valid email response: " . $response->getContent() . "\n";

$requestInvalid = Request::create('/api/forgot-password', 'POST', ['email' => 'doesnotexist@example.com']);
$responseInvalid = app()->handle($requestInvalid);
echo "Invalid email response: " . $responseInvalid->getContent() . "\n";

$token = null;
Mail::assertSent(CorreoRecuperacion::class, function ($mail) use (&$token) {
    $token = $mail->codigo;
    return true;
});

echo "Token captured: $token\n";

echo "---------------------------------\n";
echo "CASO VERIFY: Testing verifyCode con token válido\n";
$reqVerify = Request::create('/api/verify-reset-code', 'POST', [
    'email' => $email,
    'token' => $token,
]);
$resVerify = app()->handle($reqVerify);
echo "Verify token response: " . $resVerify->getContent() . "\n";


echo "---------------------------------\n";
echo "CASO 4: Token inválido (en verify)\n";
$reqInvalidToken = Request::create('/api/verify-reset-code', 'POST', [
    'email' => $email,
    'token' => '111111',
]);
$resInvalidToken = app()->handle($reqInvalidToken);
echo "Invalid token response: " . $resInvalidToken->getContent() . "\n";

echo "---------------------------------\n";
echo "CASO 3 & 6: Token válido y nueva contraseña\n";
$reqValid = Request::create('/api/reset-password', 'POST', [
    'email' => $email,
    'token' => $token,
    'password' => 'NewPassword123',
    'password_confirmation' => 'NewPassword123'
]);
$resValid = app()->handle($reqValid);
echo "Valid token response: " . $resValid->getContent() . "\n";

echo "---------------------------------\n";
echo "CASO 6 b: Token ya usado\n";
$reqUsed = Request::create('/api/reset-password', 'POST', [
    'email' => $email,
    'token' => $token,
    'password' => 'NewPassword123',
    'password_confirmation' => 'NewPassword123'
]);
$resUsed = app()->handle($reqUsed);
echo "Used token response: " . $resUsed->getContent() . "\n";

echo "---------------------------------\n";
echo "CASO 7 & 8: Inicio de sesión con nueva contraseña\n";
$reqLogin = Request::create('/api/login', 'POST', [
    'email' => $email,
    'password' => 'NewPassword123'
]);
$resLogin = app()->handle($reqLogin);
echo "Login response status: " . $resLogin->getStatusCode() . "\n";

echo "---------------------------------\n";
echo "CASO 9: Inicio de sesión con contraseña anterior no debe funcionar\n";
$reqLoginOld = Request::create('/api/login', 'POST', [
    'email' => $email,
    'password' => 'Password123'
]);
$resLoginOld = app()->handle($reqLoginOld);
echo "Login old response status: " . $resLoginOld->getStatusCode() . "\n";

echo "---------------------------------\n";
echo "CASO 5: Token expirado (simulado)\n";
$request2 = Request::create('/api/forgot-password', 'POST', ['email' => $email]);
app()->handle($request2);

$token2 = null;
Mail::assertSent(CorreoRecuperacion::class, function ($mail) use (&$token2) {
    $token2 = $mail->codigo;
    return true;
});

// Update created_at manually to simulate expiration
DB::table('password_reset_tokens')->where('email', $email)->update(['created_at' => now()->subMinutes(11)]);

$reqExpired = Request::create('/api/verify-reset-code', 'POST', [
    'email' => $email,
    'token' => $token2,
]);
$resExpired = app()->handle($reqExpired);
echo "Expired token response: " . $resExpired->getContent() . "\n";

// Cleanup
$user->delete();
