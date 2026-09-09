<?php

namespace Database\Seeders;

use App\Enums\ServiceBillingType;
use App\Enums\ServiceType;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedService(
            type: ServiceType::Umkm,
            name: 'Pendampingan Legalitas UMKM',
            description: 'Pengurusan izin usaha dan legalitas untuk pelaku UMKM.',
            packages: [
                [
                    'name' => 'Paket NIB',
                    'description' => 'Pengurusan Nomor Induk Berusaha melalui OSS.',
                    'price' => 750000,
                    'unit' => 'paket',
                    'billing_type' => ServiceBillingType::OneTime,
                    'points' => [
                        'Konsultasi kelengkapan dokumen',
                        'Pendaftaran akun OSS',
                        'Penerbitan NIB',
                    ],
                ],
                [
                    'name' => 'Paket Lengkap Legalitas',
                    'description' => 'NIB, sertifikat halal, dan izin edar dalam satu paket.',
                    'price' => 2500000,
                    'unit' => 'paket',
                    'billing_type' => ServiceBillingType::PerProject,
                    'points' => [
                        'Pengurusan NIB',
                        'Pendampingan sertifikasi halal',
                        'Pendampingan izin edar (PIRT/BPOM)',
                    ],
                ],
            ],
        );

        $this->seedService(
            type: ServiceType::Umkm,
            name: 'Pembukuan Bulanan UMKM',
            description: 'Jasa pencatatan keuangan rutin untuk UMKM.',
            packages: [
                [
                    'name' => 'Paket Dasar',
                    'description' => 'Pencatatan transaksi dan laporan laba rugi sederhana.',
                    'price' => 500000,
                    'unit' => 'bulan',
                    'billing_type' => ServiceBillingType::MonthlyRetainer,
                    'points' => [
                        'Pencatatan transaksi harian',
                        'Laporan laba rugi bulanan',
                    ],
                ],
            ],
        );

        $this->seedService(
            type: ServiceType::Brand,
            name: 'Identitas Visual Brand',
            description: 'Perancangan logo dan pedoman identitas visual.',
            packages: [
                [
                    'name' => 'Paket Logo',
                    'description' => 'Desain logo beserta variasi warna.',
                    'price' => 1500000,
                    'unit' => 'paket',
                    'billing_type' => ServiceBillingType::OneTime,
                    'points' => [
                        '3 alternatif konsep logo',
                        '2 kali revisi',
                        'File final (AI, PNG, SVG)',
                    ],
                ],
                [
                    'name' => 'Paket Brand Guideline',
                    'description' => 'Logo lengkap dengan buku panduan brand.',
                    'price' => 4000000,
                    'unit' => 'paket',
                    'billing_type' => ServiceBillingType::PerProject,
                    'points' => [
                        'Semua yang ada di Paket Logo',
                        'Buku panduan penggunaan logo',
                        'Panduan tone of voice',
                    ],
                ],
            ],
        );

        $this->seedService(
            type: ServiceType::Brand,
            name: 'Social Media Management',
            description: 'Pengelolaan konten dan iklan media sosial berlangganan bulanan.',
            packages: [
                [
                    'name' => 'Silver',
                    'description' => 'Paket dasar pengelolaan media sosial.',
                    'price' => 2500000,
                    'unit' => 'bulan',
                    'billing_type' => ServiceBillingType::MonthlyRetainer,
                    'points' => [
                        '8 konten per bulan',
                        '1 kali sesi foto produk',
                        'Laporan performa bulanan',
                    ],
                ],
                [
                    'name' => 'Gold',
                    'description' => 'Paket lengkap dengan iklan berbayar.',
                    'price' => 5000000,
                    'unit' => 'bulan',
                    'billing_type' => ServiceBillingType::MonthlyRetainer,
                    'points' => [
                        '16 konten per bulan',
                        '2 kali sesi foto produk',
                        'Pengelolaan iklan berbayar',
                        'Laporan performa dua mingguan',
                    ],
                ],
            ],
        );

        $this->seedService(
            type: ServiceType::Brand,
            name: 'Pengelolaan Media Sosial',
            description: 'Manajemen konten dan performa media sosial brand.',
            packages: [
                [
                    'name' => 'Paket Growth',
                    'description' => 'Pengelolaan konten dan iklan bulanan.',
                    'price' => 3500000,
                    'unit' => 'bulan',
                    'billing_type' => ServiceBillingType::MonthlyRetainer,
                    'points' => [
                        '12 konten per bulan',
                        'Pengelolaan iklan berbayar',
                        'Laporan performa bulanan',
                    ],
                ],
            ],
        );
    }

    /**
     * @param  list<array{name: string, description: string, price: float|int, unit: string, billing_type: ServiceBillingType, points: list<string>}>  $packages
     */
    private function seedService(ServiceType $type, string $name, string $description, array $packages): void
    {
        $service = Service::query()->updateOrCreate(
            ['type' => $type, 'name' => $name],
            ['description' => $description, 'is_active' => true],
        );

        foreach ($packages as $position => $package) {
            $servicePackage = $service->packages()->updateOrCreate(
                ['name' => $package['name']],
                [
                    'description' => $package['description'],
                    'price' => $package['price'],
                    'unit' => $package['unit'],
                    'billing_type' => $package['billing_type'],
                    'position' => $position,
                    'is_active' => true,
                ],
            );

            foreach ($package['points'] as $pointPosition => $label) {
                $servicePackage->points()->updateOrCreate(
                    ['label' => $label],
                    ['position' => $pointPosition],
                );
            }
        }
    }
}
