<?php
declare(strict_types=1);

final class AdminSettings
{
    public function __construct(private PDO $db)
    {
    }

    public function get(string $key, string $default = '0'): string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM admin_settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, (string) $default);
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, (string) $default);
    }

    public function calculateFee(string $feeType, int $amount, string $currency = 'CDF'): int
    {
        return match ($feeType) {
            'transfer' => $this->percentFee('transfer_fee_percent', $amount),
            'mobile_money' => $this->percentFee('mobile_money_fee_percent', $amount),
            'bank_transfer' => $this->percentFee('bank_transfer_fee_percent', $amount),
            'atm_withdraw' => $this->getInt('atm_withdraw_fee_flat'),
            'agent_withdraw' => $this->percentFee('agent_withdraw_fee_percent', $amount),
            'exchange_markup' => $this->percentFee('exchange_rate_markup_percent', $amount),
            default => 0,
        };
    }

    private function percentFee(string $settingKey, int $amount): int
    {
        $percent = $this->getFloat($settingKey);
        if ($percent <= 0) {
            return 0;
        }
        return (int) round($amount * $percent / 100);
    }
}
