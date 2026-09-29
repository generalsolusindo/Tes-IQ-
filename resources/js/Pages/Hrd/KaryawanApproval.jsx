import Button from '@/Components/Absensi/Button';
import Card from '@/Components/Absensi/Card';
import AppLayout from '@/Layouts/Absensi/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { ClipboardList, UserCheck } from 'lucide-react';

function NAV_ITEMS(pendingCount) {
    return [
        {
            href: route('hrd.karyawan.index'),
            pattern: 'hrd.karyawan.index',
            icon: UserCheck,
            label: 'Persetujuan Karyawan',
            count: pendingCount,
        },
        {
            href: route('hrd.absensi.index'),
            pattern: 'hrd.absensi.index',
            icon: ClipboardList,
            label: 'Rekap Absensi',
        },
    ];
}

export default function KaryawanApproval({ pendingUsers }) {
    const { auth, flash } = usePage().props;

    const approve = (user) => {
        router.patch(route('hrd.karyawan.approve', user.id));
    };

    const reject = (user) => {
        router.patch(route('hrd.karyawan.reject', user.id));
    };

    return (
        <AppLayout
            navItems={NAV_ITEMS(pendingUsers.length)}
            user={auth?.user}
            roleLabel="HRD"
        >
            <Head title="Persetujuan Karyawan" />

            <h1 className="text-xl font-bold text-gray-900">
                Persetujuan Pendaftaran Karyawan
            </h1>
            <p className="mt-1 text-sm text-gray-500">
                Tinjau dan setujui karyawan baru sebelum mereka bisa mengakses
                absensi.
            </p>

            {flash?.success && (
                <div className="mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-700">
                    {flash.success}
                </div>
            )}

            <Card className="mt-6 overflow-hidden">
                {pendingUsers.length === 0 ? (
                    <p className="p-6 text-sm text-gray-500">
                        Tidak ada pendaftaran yang menunggu persetujuan.
                    </p>
                ) : (
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
                                    Kontak
                                </th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {pendingUsers.map((user) => (
                                <tr
                                    key={user.id}
                                    className="hover:bg-gray-50"
                                >
                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                        {user.name}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-600">
                                        {user.instansi?.nama} /{' '}
                                        {user.jabatan?.nama}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-600">
                                        <div>{user.email}</div>
                                        <div>{user.no_whatsapp}</div>
                                    </td>
                                    <td className="space-x-2 px-4 py-3 text-right">
                                        <Button
                                            type="button"
                                            size="sm"
                                            onClick={() => approve(user)}
                                        >
                                            Setujui
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="danger"
                                            onClick={() => reject(user)}
                                        >
                                            Tolak
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </Card>
        </AppLayout>
    );
}
