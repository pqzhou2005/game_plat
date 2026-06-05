<?php
namespace App\Services;

use App\Models\User;

class RealNameService
{
    public function verify(string $realName, string $idCard): bool
    {
        if (!$this->validateIdCard($idCard)) {
            return false;
        }

        // TODO: Integrate with national real-name verification API
        return true;
    }

    public function validateIdCard(string $idCard): bool
    {
        $idCard = strtoupper($idCard);

        if (!preg_match('/^\d{17}[\dX]$/', $idCard)) {
            return false;
        }

        $coefficients = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $checkDigits = '10X98765432';
        $sum = 0;

        for ($i = 0; $i < 17; $i++) {
            $sum += intval($idCard[$i]) * $coefficients[$i];
        }

        return $checkDigits[$sum % 11] === $idCard[17];
    }

    public function markVerified(User $user): void
    {
        $user->update(['id_card_verified_at' => now()]);
    }
}
