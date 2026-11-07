<?php

namespace App\Services;

class EscrowService
{
    public function fund(int $milestoneId, string $amount, string $idempotencyKey): never
    {
        throw new \LogicException('Escrow funding is not implemented.');
    }

    public function hold(int $milestoneId, string $gatewayReference): never
    {
        throw new \LogicException('Escrow holding is not implemented.');
    }

    public function release(int $milestoneId, string $idempotencyKey): never
    {
        throw new \LogicException('Escrow release is not implemented.');
    }

    public function refund(int $milestoneId, string $idempotencyKey): never
    {
        throw new \LogicException('Escrow refund is not implemented.');
    }
}
