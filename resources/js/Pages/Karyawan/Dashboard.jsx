import Badge from '@/Components/Absensi/Badge';
import Button from '@/Components/Absensi/Button';
import Card from '@/Components/Absensi/Card';
import AppLayout from '@/Layouts/Absensi/AppLayout';
import {
    buildAbsensiFormData,
    captureSelfie,
    getCurrentPosition,
} from '@/Support/Absensi/capture';
import { Head, router, usePage } from '@inertiajs/react';
import { Clock, History, LayoutDashboard, LogIn, LogOut } from 'lucide-react';
import { useState } from 'react';

function formatWaktu(value) {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

const STATUS_LABEL = {
    tepat_waktu: 'Tepat waktu',
    telat: 'Telat',
    pulang_cepat: 'Pulang cepat',
};

const STATUS_BADGE_VARIANT = {
    tepat_waktu: 'green',
    telat: 'red',
    pulang_cepat: 'amber',
};

function NAV_ITEMS() {
    return [
        {
            href: route('karyawan.dashboard'),
            pattern: 'karyawan.dashboard',
            icon: LayoutDashboard,
            label: 'Dashboard',
        },
        {
            href: route('riwayat-absen.index'),
            pattern: 'riwayat-absen.index',
            icon: History,
            label: 'Riwayat Absen',
        },
    ];
}

export default function Dashboard({ todayAttendance }) {
    const { auth } = usePage().props;
    const [submitting, setSubmitting] = useState(false);
    const [statusMessage, setStatusMessage] = useState(null);
    const [error, setError] = useState(null);

    const hasMasuk = !!todayAttendance?.jam_masuk;
    const hasPulang = !!todayAttendance?.jam_pulang;

    async function handleAbsen(type) {
        setError(null);
        setSubmitting(true);

        try {
            const photo = await captureSelfie();
            setStatusMessage('Mencari lokasi GPS akurat, mohon tunggu...');
            const position = await getCurrentPosition();
            setStatusMessage('Mengirim absen...');
            const formData = buildAbsensiFormData(photo, position);
            const url =
                type === 'masuk'
                    ? route('absen.masuk')
                    : route('absen.pulang');

            router.post(url, formData, {
                forceFormData: true,
                onError: (errors) => {
                    setError(
                        Object.values(errors)[0] ?? 'Gagal mengirim absen.',
                    );
                },
                onFinish: () => {
                    setSubmitting(false);
                    setStatusMessage(null);
                },
            });
        } catch (caughtError) {
            setSubmitting(false);
            setStatusMessage(null);
            setError(caughtError?.message ?? 'Gagal mengambil lokasi/foto.');
        }
    }

    return (
        <AppLayout
            navItems={NAV_ITEMS()}
            user={auth?.user}
            roleLabel="Karyawan"
        >
            <Head title="Dashboard Absensi" />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Card className="p-5">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                            <LogIn className="h-5 w-5" />
                        </div>
                        <span className="text-sm text-gray-500">
                            Absen Masuk
                        </span>
                    </div>
                    <div className="mt-3 text-2xl font-bold text-gray-900">
                        {formatWaktu(todayAttendance?.jam_masuk)}
                    </div>
                    {todayAttendance?.status_masuk && (
                        <Badge
                            variant={
                                STATUS_BADGE_VARIANT[
                                    todayAttendance.status_masuk
                                ]
                            }
                            className="mt-2"
                        >
                            {STATUS_LABEL[todayAttendance.status_masuk]}
                        </Badge>
                    )}
                </Card>

                <Card className="p-5">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                            <LogOut className="h-5 w-5" />
                        </div>
                        <span className="text-sm text-gray-500">
                            Absen Pulang
                        </span>
                    </div>
                    <div className="mt-3 text-2xl font-bold text-gray-900">
                        {formatWaktu(todayAttendance?.jam_pulang)}
                    </div>
                    {todayAttendance?.status_pulang && (
                        <Badge
                            variant={
                                STATUS_BADGE_VARIANT[
                                    todayAttendance.status_pulang
                                ]
                            }
                            className="mt-2"
                        >
                            {STATUS_LABEL[todayAttendance.status_pulang]}
                        </Badge>
                    )}
                </Card>
            </div>

            <Card className="mt-6 max-w-md p-6">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-absensi-navy-start text-white">
                        <Clock className="h-5 w-5" />
                    </div>
                    <div>
                        <h2 className="font-semibold text-gray-900">
                            Absensi Hari Ini
                        </h2>
                        <p className="text-sm text-gray-500">
                            Gunakan tombol di bawah untuk absen.
                        </p>
                    </div>
                </div>

                {error && (
                    <p className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">
                        {error}
                    </p>
                )}

                {!error && statusMessage && (
                    <p className="mt-4 rounded-xl bg-blue-50 p-3 text-sm text-blue-700">
                        {statusMessage}
                    </p>
                )}

                <div className="mt-6">
                    {!hasMasuk && (
                        <Button
                            type="button"
                            className="w-full justify-center"
                            onClick={() => handleAbsen('masuk')}
                            disabled={submitting}
                        >
                            Absen Masuk
                        </Button>
                    )}

                    {hasMasuk && !hasPulang && (
                        <Button
                            type="button"
                            className="w-full justify-center"
                            onClick={() => handleAbsen('pulang')}
                            disabled={submitting}
                        >
                            Absen Pulang
                        </Button>
                    )}

                    {hasMasuk && hasPulang && (
                        <p className="text-center text-sm text-gray-500">
                            Anda sudah menyelesaikan absen hari ini.
                        </p>
                    )}
                </div>
            </Card>
        </AppLayout>
    );
}
