<?php

namespace App\Enums;

/**
 * Metode verifikasi quantity di `customer_logistik.quantity_checking_method`,
 * daftar opsi dari form publik `CustomerUpdateForm.vue`.
 */
enum QuantityCheckingMethod: string
{
    case WeighbridgeTruckScale     = 'weighbridge_truck_scale';
    case PlatformScale             = 'platform_scale';
    case VolumeMeasurement         = 'volume_measurement';
    case TruckCounting             = 'truck_counting';
    case DeliveryOrderVerification = 'delivery_order_verification';
    case NetWeightVerification     = 'net_weight_verification';
    case Sampling                  = 'sampling';
    case Other                     = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WeighbridgeTruckScale     => 'Weighbridge (Truck Scale)',
            self::PlatformScale             => 'Platform Scale',
            self::VolumeMeasurement         => 'Volume Measurement',
            self::TruckCounting             => 'Truck Counting',
            self::DeliveryOrderVerification => 'Delivery Order Verification',
            self::NetWeightVerification     => 'Net Weight Verification',
            self::Sampling                  => 'Sampling',
            self::Other                     => 'Lainnya',
        };
    }
}
