<?php

namespace App\Enums;

enum Permission: string implements HasLabel
{
    case ManageServices = 'manage-services';
    case ManageLeadStages = 'manage-lead-stages';
    case ManageCompanySettings = 'manage-company-settings';
    case CreateLeads = 'create-leads';
    case UpdateLeads = 'update-leads';
    case MoveLeads = 'move-leads';
    case ConvertLeads = 'convert-leads';
    case DeleteLeads = 'delete-leads';
    case ExportLeads = 'export-leads';
    case CreateClients = 'create-clients';
    case UpdateClients = 'update-clients';
    case DeleteClients = 'delete-clients';
    case ExportClients = 'export-clients';
    case CreateContracts = 'create-contracts';
    case UpdateContracts = 'update-contracts';
    case ApproveContracts = 'approve-contracts';
    case DeleteContracts = 'delete-contracts';
    case ExportContracts = 'export-contracts';
    case CreateInvoices = 'create-invoices';
    case UpdateInvoices = 'update-invoices';
    case DeleteInvoices = 'delete-invoices';
    case ExportInvoices = 'export-invoices';
    case ManagePayments = 'manage-payments';
    case ViewFinance = 'view-finance';
    case ManageFinance = 'manage-finance';
    case ExportFinance = 'export-finance';
    case ManageUsers = 'manage-users';

    public function label(): string
    {
        return match ($this) {
            self::ManageServices => 'Kelola layanan',
            self::ManageLeadStages => 'Kelola tahapan lead',
            self::ManageCompanySettings => 'Kelola pengaturan perusahaan',
            self::CreateLeads => 'Tambah lead',
            self::UpdateLeads => 'Ubah data lead',
            self::MoveLeads => 'Pindah tahap lead',
            self::ConvertLeads => 'Konversi lead',
            self::DeleteLeads => 'Hapus lead',
            self::ExportLeads => 'Ekspor data lead',
            self::CreateClients => 'Tambah klien',
            self::UpdateClients => 'Ubah data klien',
            self::DeleteClients => 'Hapus klien',
            self::ExportClients => 'Ekspor data klien',
            self::CreateContracts => 'Buat MoU/kontrak',
            self::UpdateContracts => 'Ubah MoU/kontrak & dokumen',
            self::ApproveContracts => 'Tandatangani & finalisasi',
            self::DeleteContracts => 'Hapus MoU/kontrak',
            self::ExportContracts => 'Ekspor data kontrak',
            self::CreateInvoices => 'Buat invoice',
            self::UpdateInvoices => 'Ubah/kirim/settle/void invoice',
            self::DeleteInvoices => 'Hapus invoice',
            self::ExportInvoices => 'Ekspor data invoice',
            self::ManagePayments => 'Catat & kelola pembayaran',
            self::ViewFinance => 'Lihat keuangan',
            self::ManageFinance => 'Kelola transaksi keuangan',
            self::ExportFinance => 'Ekspor data keuangan',
            self::ManageUsers => 'Kelola pengguna',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ManageServices => 'Menambah, mengubah, dan menghapus daftar layanan.',
            self::ManageLeadStages => 'Mengelola tahapan pipeline dan urutannya.',
            self::ManageCompanySettings => 'Mengubah profil, tanda tangan, dan cap perusahaan.',
            self::CreateLeads => 'Menambah lead baru ke pipeline.',
            self::UpdateLeads => 'Mengubah data, kontak, dan nilai lead.',
            self::MoveLeads => 'Memindahkan lead antar tahap.',
            self::ConvertLeads => 'Mengonversi lead menjadi klien/MoU.',
            self::DeleteLeads => 'Menghapus lead, termasuk hapus massal.',
            self::ExportLeads => 'Mengunduh data lead sebagai CSV.',
            self::CreateClients => 'Menambah klien baru.',
            self::UpdateClients => 'Mengubah data klien.',
            self::DeleteClients => 'Menghapus klien, termasuk hapus massal.',
            self::ExportClients => 'Mengunduh data klien sebagai CSV.',
            self::CreateContracts => 'Membuat MoU/kontrak baru.',
            self::UpdateContracts => 'Mengubah isi kontrak dan menyusun dokumen.',
            self::ApproveContracts => 'Menandatangani MoU (wajib upload tanda tangan klien), memfinalisasi, dan menerbitkan invoice dari MoU.',
            self::DeleteContracts => 'Menghapus kontrak, termasuk hapus massal.',
            self::ExportContracts => 'Mengunduh data kontrak sebagai CSV.',
            self::CreateInvoices => 'Membuat invoice baru.',
            self::UpdateInvoices => 'Mengubah, mengirim, melunasi (dengan unggah bukti pembayaran), dan membatalkan invoice.',
            self::DeleteInvoices => 'Menghapus invoice, termasuk hapus massal.',
            self::ExportInvoices => 'Mengunduh data invoice sebagai CSV.',
            self::ManagePayments => 'Mencatat dan menghapus pembayaran invoice, termasuk mengunggah bukti pembayaran.',
            self::ViewFinance => 'Melihat dashboard dan daftar transaksi keuangan.',
            self::ManageFinance => 'Menambah, mengubah, dan menghapus transaksi keuangan.',
            self::ExportFinance => 'Mengunduh data keuangan sebagai CSV.',
            self::ManageUsers => 'Menyetujui, mengubah peran, dan menghapus akun pengguna.',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ManageServices, self::ManageLeadStages, self::ManageCompanySettings => 'Master Data',
            self::CreateLeads, self::UpdateLeads, self::MoveLeads, self::ConvertLeads, self::DeleteLeads, self::ExportLeads => 'Leads',
            self::CreateClients, self::UpdateClients, self::DeleteClients, self::ExportClients => 'Klien',
            self::CreateContracts, self::UpdateContracts, self::ApproveContracts, self::DeleteContracts, self::ExportContracts => 'MoU & Kontrak',
            self::CreateInvoices, self::UpdateInvoices, self::DeleteInvoices, self::ExportInvoices, self::ManagePayments => 'Invoice',
            self::ViewFinance, self::ManageFinance, self::ExportFinance => 'Keuangan',
            self::ManageUsers => 'Admin',
        };
    }
}
