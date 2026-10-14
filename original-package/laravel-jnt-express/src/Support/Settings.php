<?php

namespace JntExpress\Support;

use Illuminate\Support\Facades\Crypt;
use JntExpress\Models\JntSetting;

/**
 * Merchant settings: config defaults overridden by values saved on the settings
 * page. Secrets (API key, sign key, rate key) are stored encrypted.
 */
class Settings
{
    public const SECRETS = ['apikey', 'signkey', 'ratekey'];

    protected ?array $stored = null;

    public function all(): array
    {
        return array_merge(config('jnt.settings', []), $this->stored());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public function enabled(): bool
    {
        return filter_var($this->get('enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function insuranceAllowed(): bool
    {
        return filter_var($this->get('insurance', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function senderAddress(): string
    {
        return implode(' ', array_filter([
            $this->get('address'), $this->get('address_2'), $this->get('city'), $this->get('postcode'),
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            if ($value !== null && in_array($key, self::SECRETS, true)) {
                $value = Crypt::encryptString((string) $value);
            }
            JntSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->stored = null;
    }

    protected function stored(): array
    {
        if ($this->stored !== null) {
            return $this->stored;
        }

        try {
            $rows = JntSetting::query()->pluck('value', 'key')->all();
        } catch (\Throwable) {
            return $this->stored = []; // table not migrated yet
        }

        foreach ($rows as $key => $value) {
            if ($value !== null && in_array($key, self::SECRETS, true)) {
                try {
                    $rows[$key] = Crypt::decryptString($value);
                } catch (\Throwable) {
                    $rows[$key] = null;
                }
            }
        }

        return $this->stored = array_filter($rows, fn ($v) => $v !== null);
    }
}
