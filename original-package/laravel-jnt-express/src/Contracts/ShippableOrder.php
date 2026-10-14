<?php

namespace JntExpress\Contracts;

interface ShippableOrder
{
    public function jntOrderKey(): int|string;

    public function jntReceiverName(): string;

    /** May contain several numbers separated by "/" - the first one is used. */
    public function jntReceiverPhone(): string;

    public function jntReceiverAddress(): string;

    public function jntReceiverPostcode(): string;

    /** @return array<int, array{name:string, quantity:int|float, weight:int|float|null, virtual:bool}> */
    public function jntItems(): array;

    public function jntSubtotal(): float;

    public function jntTotal(): float;

    public function jntIsCashOnDelivery(): bool;

    public function jntCustomerNote(): ?string;

    public function jntCustomerEmail(): ?string;

    /** Weight unit of the item weights: kg, g, lb or oz. */
    public function jntWeightUnit(): string;
}
