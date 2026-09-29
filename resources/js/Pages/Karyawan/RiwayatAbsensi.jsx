import Badge from '@/Components/Absensi/Badge';
import Card from '@/Components/Absensi/Card';
import Input from '@/Components/Absensi/Input';
import AppLayout from '@/Layouts/Absensi/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { History, LayoutDashboard } from 'lucide-react';

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

function formatWaktu(value) {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatTanggal(value) {
    return new Date(value).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
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

export default function RiwayatAbsensi({ bulan, riwayat }) {
    const { auth } = usePage().props;

    function handleBulanChange(value) {
        router.get(
            route('riwayat-absen.index'),
            { bulan: value },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <AppLayout
            navItems={NAV_ITEMS()}
            user={auth?.user}
            roleLabel="Karyawan"
        >
            <Head title="Riwayat Absensi" />

            <h1 className="text-xl font-bold text-gray-900">
                Riwayat Absensi
            </h1>
            <p className="mt-1 text-sm text-gray-500">
                Riwayat absen masuk dan pulang milikmu, difilter per bulan.
            </p>

            <div className="mt-4 max-w-50">
                <label
                    htmlFor="bulan"
                    className="mb-1 block text-sm font-medium text-gray-700"
                >
                    Bulan
                </label>
                <Input
                    id="bulan"
                    type="month"
                    defaultValue={bulan}
                    onChange={(e) => handleBulanChange(e.target.value)}
                />
            </div>

            <Card className="mt-6 overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Tanggal
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Masuk
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Pulang
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {riwayat.length === 0 && (
                            <tr>
                                <td
                                    colSpan={3}
                                    className="px-4 py-6 text-center text-sm text-gray-500"
                                >
                                    Belum ada riwayat absen bulan ini.
                                </td>
                            </tr>
                        )}

                        {riwayat.map((item) => (
                            <tr key={item.tanggal} className="hover:bg-gray-50">
                                <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                    {formatTanggal(item.tanggal)}
                                </td>
                                <td className="px-4 py-3 text-sm text-gray-600">
                                    <div className="flex items-center gap-2">
                                        {formatWaktu(item.jam_masuk)}
                                        {item.status_masuk && (
                                            <Badge
                                                variant={
                                                    STATUS_BADGE_VARIANT[
                                                        item.status_masuk
                                                    ]
                                                }
                                            >
                                                {STATUS_LABEL[
                                                    item.status_masuk
                                                ] ?? item.status_masuk}
                                            </Badge>
                                        )}
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-sm text-gray-600">
                                    <div className="flex items-center gap-2">
                                        {formatWaktu(item.jam_pulang)}
                                        {item.status_pulang && (
                                            <Badge
                                                variant={
                                                    STATUS_BADGE_VARIANT[
                                                        item.status_pulang
                                                    ]
                                                }
                                            >
                                                {STATUS_LABEL[
                                                    item.status_pulang
                                                ] ?? item.status_pulang}
                                            </Badge>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </AppLayout>
    );
}
