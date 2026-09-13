<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\HirePurchaseContract;
use App\Models\PaymentGatewayTransaction;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\WalletFundingRequest;
use App\Services\RemittanceSettlementService;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DriverPaymentController extends Controller
{
    public function __construct(
        private readonly RemittanceSettlementService $remittanceSettlement,
    ) {}

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'purpose' => ['required', Rule::in(['remittance', 'wallet_funding'])],
            'amount' => ['required', 'numeric', 'min:100', 'max:1000000'],
            'transaction_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $driver = Driver::where('user_id', $request->user()->id)->first();
        if (!$driver) {
            return response()->json(['success' => false, 'message' => 'Driver profile not found'], 404);
        }

        if (!$this->opayReady()) {
            return response()->json(['success' => false, 'message' => 'OPay payments are currently unavailable'], 503);
        }

        $environment = $this->opayEnvironment();
        $amount = round((float) $data['amount'], 2);
        $amountMinor = (int) round($amount * 100);
        $minimumAmount = $amount;
        $reference = 'ERIDE-' . strtoupper(Str::random(24));

        try {
            $checkout = DB::transaction(function () use ($data, $driver, $amount, $amountMinor, $reference, $environment, &$minimumAmount) {
                $transaction = null;
                $fundingRequest = null;

                if ($data['purpose'] === 'remittance') {
                    if (!empty($data['transaction_id'])) {
                        $transaction = Transaction::where('id', $data['transaction_id'])
                            ->where('driver_id', $driver->id)
                            ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                            ->whereIn('status', [Transaction::STATUS_PENDING, 'submitted'])
                            ->lockForUpdate()
                            ->first();

                        if (!$transaction) {
                            throw ValidationException::withMessages([
                                'transaction_id' => 'The selected remittance is not available for payment.',
                            ]);
                        }

                        $minimumAmount = round((float) $transaction->amount, 2);

                        $remainingBalance = null;
                        if ($transaction->is_hire_purchase_payment && $transaction->hire_purchase_contract_id) {
                            $contract = HirePurchaseContract::whereKey($transaction->hire_purchase_contract_id)
                                ->lockForUpdate()
                                ->first();

                            if (!$contract || $contract->status !== HirePurchaseContract::STATUS_ACTIVE || (float) $contract->total_balance <= 0) {
                                throw ValidationException::withMessages([
                                    'transaction_id' => 'This hire-purchase contract has already been completed or is no longer active.',
                                ]);
                            }

                            $remainingBalance = (float) $contract->total_balance;

                            if ($remainingBalance > 0) {
                                $minimumAmount = min($minimumAmount, round($remainingBalance, 2));
                            }
                        }

                        if ($amount < $minimumAmount) {
                            throw ValidationException::withMessages([
                                'amount' => 'The minimum payment for this remittance is ₦' . number_format($minimumAmount, 2) . '.',
                            ]);
                        }

                        if ($remainingBalance !== null) {
                            if ($remainingBalance > 0 && $amount > $remainingBalance) {
                                throw ValidationException::withMessages([
                                    'amount' => 'The payment cannot exceed the remaining hire-purchase balance of ₦' . number_format($remainingBalance, 2) . '.',
                                ]);
                            }
                        }

                        $activeGateway = PaymentGatewayTransaction::where('transaction_id', $transaction->id)
                            ->whereIn('status', ['INITIAL', 'PENDING'])
                            ->where('created_at', '>=', now()->subMinutes(35))
                            ->latest()
                            ->first();

                        if ($activeGateway?->checkout_url) {
                            return ['gateway' => $activeGateway, 'reused' => true];
                        }

                        if ($activeGateway) {
                            throw ValidationException::withMessages([
                                'transaction_id' => 'A checkout is already being created for this remittance. Please retry shortly.',
                            ]);
                        }
                    } else {
                        $transaction = $driver->transactions()->create([
                            'type' => Transaction::TYPE_DAILY_REMITTANCE,
                            'amount' => $amount,
                            'reference' => $reference,
                            'description' => $data['notes'] ?? 'Daily remittance payment via OPay',
                            'status' => Transaction::STATUS_PENDING,
                        ]);
                    }
                } else {
                    $fundingRequest = $driver->walletFundingRequests()->create([
                        'amount' => $amount,
                        'receipt_image' => 'opay:' . $reference,
                        'description' => $data['notes'] ?? 'Wallet funding via OPay',
                        'status' => WalletFundingRequest::STATUS_PENDING,
                    ]);
                }

                return [
                    'gateway' => PaymentGatewayTransaction::create([
                        'driver_id' => $driver->id,
                        'gateway' => 'opay',
                        'environment' => $environment,
                        'purpose' => $data['purpose'],
                        'reference' => $reference,
                        'amount' => $amount,
                        'minimum_amount' => $minimumAmount,
                        'amount_minor' => $amountMinor,
                        'currency' => $this->opay('currency', 'NGN', $environment),
                        'status' => 'INITIAL',
                        'transaction_id' => $transaction?->id,
                        'wallet_funding_request_id' => $fundingRequest?->id,
                    ]),
                    'reused' => false,
                ];
            });

            /** @var PaymentGatewayTransaction $gateway */
            $gateway = $checkout['gateway'];
            if ($checkout['reused']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Existing checkout resumed',
                    'data' => $this->serializeGateway($gateway),
                ]);
            }

            $payload = $this->cashierPayload($gateway, $driver, $request);
            $response = $this->opayCreate($payload, $gateway->environment);

            if (!$response->successful() || $response->json('code') !== '00000') {
                $gateway->update([
                    'status' => 'FAIL',
                    'gateway_response' => $response->json() ?: ['body' => $response->body()],
                ]);
                $this->rejectFailedWalletFunding($gateway, 'OPay checkout could not be initialized.');

                return response()->json([
                    'success' => false,
                    'message' => $response->json('message', 'Unable to initialize OPay checkout'),
                ], 502);
            }

            $opayData = $response->json('data', []);
            $gateway->update([
                'gateway_order_no' => $opayData['orderNo'] ?? null,
                'checkout_url' => $opayData['cashierUrl'] ?? null,
                'status' => $opayData['status'] ?? 'INITIAL',
                'gateway_response' => $response->json(),
            ]);

            if (empty($opayData['cashierUrl'])) {
                $this->rejectFailedWalletFunding($gateway, 'OPay did not return a checkout URL.');
                return response()->json(['success' => false, 'message' => 'OPay did not return a checkout URL'], 502);
            }

            return response()->json([
                'success' => true,
                'message' => 'Checkout created',
                'data' => $this->serializeGateway($gateway->fresh()),
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['success' => false, 'message' => 'Unable to initialize payment'], 500);
        }
    }

    public function status(Request $request, string $reference)
    {
        $driver = Driver::where('user_id', $request->user()->id)->first();
        $gateway = PaymentGatewayTransaction::where('driver_id', $driver?->id)
            ->where('reference', $reference)
            ->first();

        if (!$gateway) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        if (!in_array($gateway->status, ['SUCCESS', 'FAIL', 'CLOSE'], true)) {
            $response = $this->opayStatus($gateway);
            if ($response->successful() && $response->json('code') === '00000') {
                $status = $response->json('data.status');
                $gateway->update(['gateway_response' => $response->json()]);
                $this->applyStatus($gateway->fresh(), $status, $response->json('data'));
                $gateway->refresh();
            }
        }

        return response()->json(['success' => true, 'data' => $this->serializeGateway($gateway->fresh())]);
    }

    public function callback(Request $request)
    {
        $payload = $request->input('payload');
        $signature = (string) $request->input('sha512');

        if (!is_array($payload)) {
            return response()->json(['success' => false, 'message' => 'Invalid callback signature'], 401);
        }

        $gateway = PaymentGatewayTransaction::where('reference', $payload['reference'] ?? '')->first();
        if (!$gateway) {
            return response()->json(['success' => true, 'message' => 'Callback acknowledged']);
        }

        if (!$this->validCallbackSignature($payload, $signature, $gateway->environment)) {
            return response()->json(['success' => false, 'message' => 'Invalid callback signature'], 401);
        }

        if (($payload['currency'] ?? $this->opay('currency', 'NGN', $gateway->environment)) !== $gateway->currency) {
            return response()->json(['success' => false, 'message' => 'Currency mismatch'], 422);
        }

        $this->applyStatus($gateway, strtoupper((string) ($payload['status'] ?? '')), $payload, $request->all());
        return response()->json(['success' => true, 'message' => 'Callback acknowledged']);
    }

    private function cashierPayload(PaymentGatewayTransaction $gateway, Driver $driver, Request $request): array
    {
        $user = $driver->user;
        $environment = $gateway->environment;
        return [
            'country' => $this->opay('country', 'NG', $environment),
            'reference' => $gateway->reference,
            'amount' => [
                'total' => $gateway->amount_minor,
                'currency' => $gateway->currency,
            ],
            'returnUrl' => $this->opay('return_url', route('driver.payment.return'), $environment),
            'callbackUrl' => $this->opay('callback_url', url('/api/payments/opay/callback'), $environment),
            'cancelUrl' => $this->opay('cancel_url', route('driver.payment.cancel'), $environment),
            'displayName' => $this->opay('display_name', 'E-RIDE Nigeria', $environment),
            'customerVisitSource' => $request->header('X-Client-Platform', 'BROWSER') === 'ANDROID' ? 'ANDROID' : 'BROWSER',
            'evokeOpay' => false,
            'expireAt' => 30,
            'userInfo' => [
                'userEmail' => $user?->email,
                'userId' => (string) $user?->id,
                'userMobile' => $driver->phone_number ?: null,
                'userName' => $driver->full_name,
            ],
            'product' => [
                'name' => $gateway->purpose === 'remittance' ? 'E-RIDE daily remittance' : 'E-RIDE wallet funding',
                'description' => 'E-RIDE driver payment',
            ],
        ];
    }

    private function opayCreate(array $payload, ?string $environment = null): HttpResponse
    {
        return Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->opay('public_key', null, $environment),
                'MerchantId' => $this->opay('merchant_id', null, $environment),
            ])
            ->post(rtrim($this->opay('base_url', 'https://liveapi.opaycheckout.com', $environment), '/') . '/api/v1/international/cashier/create', $payload);
    }

    private function opayStatus(PaymentGatewayTransaction $gateway): HttpResponse
    {
        $environment = $gateway->environment;
        $payload = [
            'country' => $this->opay('country', 'NG', $environment),
            'reference' => $gateway->reference,
        ];
        ksort($payload);
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha512', $body, (string) $this->opay('secret_key', null, $environment));

        return Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $signature,
                'MerchantId' => $this->opay('merchant_id', null, $environment),
            ])
            ->post(rtrim($this->opay('base_url', 'https://liveapi.opaycheckout.com', $environment), '/') . '/api/v1/international/cashier/status', $payload);
    }

    private function validCallbackSignature(array $payload, string $signature, ?string $environment = null): bool
    {
        if ($signature === '' || !isset($payload['amount'], $payload['currency'], $payload['reference'], $payload['status'], $payload['timestamp'], $payload['transactionId'])) {
            return false;
        }

        $canonical = sprintf(
            '{Amount:"%s",Currency:"%s",Reference:"%s",Refunded:%s,Status:"%s",Timestamp:"%s",Token:"%s",TransactionID:"%s"}',
            $payload['amount'],
            $payload['currency'],
            $payload['reference'],
            !empty($payload['refunded']) ? 't' : 'f',
            $payload['status'],
            $payload['timestamp'],
            $payload['token'] ?? '',
            $payload['transactionId'],
        );

        return hash_equals(
            strtolower($signature),
            strtolower(hash_hmac('sha3-512', $canonical, (string) $this->opay('secret_key', null, $environment)))
        );
    }

    private function opayEnabled(): bool
    {
        $value = $this->opay('enabled', true);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ((string) $value === '1');
    }

    private function opayReady(): bool
    {
        return $this->opayEnabled()
            && filled($this->opay('public_key'))
            && filled($this->opay('secret_key'))
            && filled($this->opay('merchant_id'));
    }

    private function opayEnvironment(?string $environment = null): string
    {
        $environment = strtolower(trim((string) ($environment ?? SystemSetting::get('opay_environment', 'live'))));
        return in_array($environment, ['live', 'demo'], true) ? $environment : 'live';
    }

    private function opay(string $key, mixed $default = null, ?string $environment = null): mixed
    {
        $environmentScoped = [
            'public_key',
            'secret_key',
            'merchant_id',
            'base_url',
            'display_name',
            'country',
            'currency',
            'return_url',
            'cancel_url',
            'callback_url',
        ];
        $settingKey = in_array($key, $environmentScoped, true)
            ? 'opay_' . $this->opayEnvironment($environment) . '_' . $key
            : 'opay_' . $key;
        $configured = SystemSetting::get($settingKey);
        if ($configured !== null && $configured !== '') {
            return $configured;
        }

        // Preserve existing live credentials during the one-time migration if
        // an older installation has not populated the environment-specific
        // fields yet. This fallback is still database-only, never .env.
        if (in_array($key, $environmentScoped, true) && $this->opayEnvironment($environment) === 'live') {
            $legacy = SystemSetting::get('opay_' . $key);
            if ($legacy !== null && $legacy !== '') {
                return $legacy;
            }
        }

        // OPay credentials and checkout settings are managed from Admin >
        // Settings. Never fall back to .env values: that can make the admin UI
        // appear configured while payments use another merchant.
        return $default;
    }

    private function applyStatus(PaymentGatewayTransaction $gateway, string $status, array $payload, ?array $callback = null): void
    {
        $status = strtoupper($status);
        $mapped = match ($status) {
            'SUCCESS', 'SUCCESSFUL', 'PAID', 'COMPLETED' => 'SUCCESS',
            'FAIL', 'FAILED' => 'FAIL',
            'CLOSE', 'CANCELLED', 'CANCELED' => 'CLOSE',
            default => 'PENDING',
        };

        DB::transaction(function () use ($gateway, $mapped, $payload, $callback) {
            $locked = PaymentGatewayTransaction::whereKey($gateway->id)->lockForUpdate()->first();
            if (!$locked) {
                return;
            }

            $update = [
                'status' => $mapped,
                'gateway_transaction_id' => $payload['transactionId'] ?? $locked->gateway_transaction_id,
                'callback_payload' => $callback ?? $locked->callback_payload,
            ];

            if ($mapped === 'SUCCESS' && $locked->status !== 'SUCCESS') {
                if (!$this->amountMatches($locked, is_array($payload['amount'] ?? null)
                    ? data_get($payload, 'amount.total')
                    : ($payload['amount'] ?? null))) {
                    $update['status'] = 'FAIL';
                    $update['callback_payload'] = ['error' => 'amount_mismatch', 'payload' => $callback ?? $payload];
                } else {
                    $update['completed_at'] = now();
                    $this->completeBusinessPayment($locked);
                }
            }

            if (in_array($update['status'], ['FAIL', 'CLOSE'], true)) {
                $this->rejectFailedWalletFunding($locked, 'OPay payment was not completed.');
            }

            $locked->update($update);
        });
    }

    private function completeBusinessPayment(PaymentGatewayTransaction $gateway): void
    {
        if ($gateway->purpose === 'remittance' && $gateway->transaction_id) {
            $transaction = Transaction::lockForUpdate()->find($gateway->transaction_id);
            if ($transaction && $transaction->status !== Transaction::STATUS_SUCCESSFUL) {
                $this->remittanceSettlement->settle(
                    $transaction,
                    (float) $gateway->amount,
                    (float) ($gateway->minimum_amount ?? $transaction->amount),
                    $gateway->reference,
                );
            }
        }

        if ($gateway->purpose === 'wallet_funding' && $gateway->wallet_funding_request_id) {
            $funding = WalletFundingRequest::lockForUpdate()->find($gateway->wallet_funding_request_id);
            if ($funding && $funding->status === WalletFundingRequest::STATUS_PENDING) {
                $funding->update([
                    'status' => WalletFundingRequest::STATUS_APPROVED,
                    'approved_at' => now(),
                    'admin_notes' => 'Automatically confirmed by OPay.',
                ]);
                $wallet = $funding->driver->wallet()->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->increment('balance', $funding->amount);
                    $funding->driver->transactions()->create([
                        'type' => 'wallet_funding',
                        'amount' => $funding->amount,
                        'reference' => $gateway->reference,
                        'description' => 'Wallet funded via OPay',
                        'status' => 'successful',
                        'processed_at' => now(),
                    ]);
                }
            }
        }
    }

    private function amountMatches(PaymentGatewayTransaction $gateway, mixed $received): bool
    {
        if ($received === null || $received === '') {
            return false;
        }

        $received = (int) round((float) $received);
        return $received === (int) $gateway->amount_minor || $received === (int) round((float) $gateway->amount);
    }

    private function rejectFailedWalletFunding(PaymentGatewayTransaction $gateway, string $reason): void
    {
        if ($gateway->purpose !== 'wallet_funding' || !$gateway->wallet_funding_request_id) {
            return;
        }

        WalletFundingRequest::whereKey($gateway->wallet_funding_request_id)
            ->where('status', WalletFundingRequest::STATUS_PENDING)
            ->update([
                'status' => WalletFundingRequest::STATUS_REJECTED,
                'admin_notes' => $reason,
            ]);
    }

    private function serializeGateway(PaymentGatewayTransaction $gateway): array
    {
        return [
            'reference' => $gateway->reference,
            'purpose' => $gateway->purpose,
            'environment' => $gateway->environment,
            'amount' => (float) $gateway->amount,
            'minimum_amount' => (float) $gateway->minimum_amount,
            'currency' => $gateway->currency,
            'status' => strtolower($gateway->status),
            'checkout_url' => $gateway->checkout_url,
            'transaction_id' => $gateway->transaction_id,
            'wallet_funding_request_id' => $gateway->wallet_funding_request_id,
            'created_at' => $gateway->created_at?->toIso8601String(),
            'completed_at' => $gateway->completed_at?->toIso8601String(),
        ];
    }
}
