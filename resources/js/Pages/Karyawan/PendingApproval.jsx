import Button from '@/Components/Absensi/Button';
import AuthLayout from '@/Layouts/Absensi/AuthLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Clock, XCircle } from 'lucide-react';

const STATUS_COPY = {
    pending: {
        title: 'Menunggu Persetujuan',
        message:
            'Pendaftaran kamu sudah kami terima dan sedang menunggu persetujuan HRD. Kamu bisa login setelah akun disetujui.',
        icon: Clock,
        iconClass: 'bg-amber-100 text-amber-600',
    },
    ditolak: {
        title: 'Pendaftaran Ditolak',
        message:
            'Mohon maaf, pendaftaran kamu belum bisa disetujui. Silakan hubungi HRD untuk informasi lebih lanjut.',
        icon: XCircle,
        iconClass: 'bg-red-100 text-red-600',
    },
};

export default function PendingApproval({ status }) {
    const copy = STATUS_COPY[status] ?? STATUS_COPY.pending;
    const Icon = copy.icon;

    return (
        <AuthLayout
            heading="Bergabung dengan sistem absensi General Solusindo."
            description="Status pendaftaran kamu dapat dipantau di halaman ini."
        >
            <Head title={copy.title} />

            <div
                className={`flex h-12 w-12 items-center justify-center rounded-full ${copy.iconClass}`}
            >
                <Icon className="h-6 w-6" />
            </div>

            <h2 className="mt-4 text-2xl font-bold text-gray-900">
                {copy.title}
            </h2>

            <p className="mt-2 text-sm text-gray-500">{copy.message}</p>

            <div className="mt-6 flex items-center justify-between">
                <Link
                    href={route('login')}
                    className="text-sm text-blue-600 hover:underline"
                >
                    Kembali ke login
                </Link>

                <Button
                    type="button"
                    variant="outline"
                    onClick={() => router.post(route('logout'))}
                >
                    Keluar
                </Button>
            </div>
        </AuthLayout>
    );
}
