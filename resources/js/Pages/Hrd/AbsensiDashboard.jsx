import Badge from '@/Components/Absensi/Badge';
import Card from '@/Components/Absensi/Card';
import Input from '@/Components/Absensi/Input';
import AppLayout from '@/Layouts/Absensi/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { ClipboardList, UserCheck } from 'lucide-react';

function NAV_ITEMS() {
    return [
        {
            href: route('hrd.karyawan.index'),
            pattern: 'hrd.karyawan.index',
            icon: UserCheck,
            label: 'Persetujuan Karyawan',
        },
        {
            href: route('hrd.absensi.index'),
            pattern: 'hrd.absensi.index',
            icon: ClipboardList,
            label: 'Rekap Absensi',
        },
    ];
}

function formatWaktu(value) {
    if (!value) {
        return null;
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

function StatusCell({ waktu, status, fotoUrl }) {
    if (!waktu) {
        return <span className="text-sm text-gray-400">Belum absen</span>;
    }

    return (
        <div className="flex items-center gap-2">
            <span className="text-sm text-gray-900">
                {formatWaktu(waktu)}
            </span>
            {status && (
                <Badge variant={STATUS_BADGE_VARIANT[status]}>
                    {STATUS_LABEL[status] ?? status}
                </Badge>
            )}
            {fotoUrl && (
                <a
                    href={fotoUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="text-xs text-blue-600 underline"
                >
                    Foto
                </a>
            )}
        </div>
    );
}

export default function AbsensiDashboard({ tanggal, karyawan }) {
    const { auth } = usePage().props;

    function handleDateChange(value) {
        router.get(
            route('hrd.absensi.index'),
            { tanggal: value },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <AppLayout navItems={NAV_ITEMS()} user={auth?.user} roleLabel="HRD">
            <Head title="Rekap Absensi" />

            <h1 className="text-xl font-bold text-gray-900">
                Rekap Absensi
            </h1>
            <p className="mt-1 text-sm text-gray-500">
                Daftar kehadiran seluruh karyawan aktif — murni untuk
                dilihat, tidak perlu verifikasi manual.
            </p>

            <div className="mt-4 max-w-50">
                <label
                    htmlFor="tanggal"
                    className="mb-1 block text-sm font-medium text-gray-700"
                >
                    Tanggal
                </label>
                <Input
                    id="tanggal"
                    type="date"
                    defaultValue={tanggal}
                    onChange={(e) => handleDateChange(e.target.value)}
                />
            </div>

            <Card className="mt-6 overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Nama
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Instansi / Jabatan
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Absen Masuk
                            </th>
                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Absen Pulang
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {karyawan.length === 0 && (
                            <tr>
                                <td
                                    colSpan={4}
                                    className="px-4 py-6 text-center text-sm text-gray-500"
                                >
                                    Belum ada karyawan aktif.
                                </td>
                            </tr>
                        )}

                        {karyawan.map((item) => (
                            <tr key={item.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                    {item.name}
                                </td>
                                <td className="px-4 py-3 text-sm text-gray-600">
                                    {item.instansi} / {item.jabatan}
                                </td>
                                <td className="px-4 py-3">
                                    <StatusCell
                                        waktu={item.jam_masuk}
                                        status={item.status_masuk}
                                        fotoUrl={item.foto_masuk_url}
                                    />
                                </td>
                                <td className="px-4 py-3">
                                    <StatusCell
                                        waktu={item.jam_pulang}
                                        status={item.status_pulang}
                                        fotoUrl={item.foto_pulang_url}
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>
        </AppLayout>
    );
}
