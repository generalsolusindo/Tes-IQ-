import Button from '@/Components/Absensi/Button';
import Input from '@/Components/Absensi/Input';
import AuthLayout from '@/Layouts/Absensi/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Lock, Mail, Phone, User } from 'lucide-react';
import { useMemo } from 'react';

const selectClassName =
    'mt-1 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:cursor-not-allowed disabled:opacity-50';

export default function Register({ instansiOptions }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        no_whatsapp: '',
        instansi_id: '',
        jabatan_id: '',
        password: '',
        password_confirmation: '',
    });

    const jabatanOptions = useMemo(() => {
        const instansi = instansiOptions.find(
            (item) => String(item.id) === String(data.instansi_id),
        );

        return instansi?.jabatan ?? [];
    }, [instansiOptions, data.instansi_id]);

    const submit = (e) => {
        e.preventDefault();

        post(route('karyawan.register.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            heading="Bergabung dengan sistem absensi General Solusindo."
            description="Daftar sebagai karyawan untuk mulai menggunakan absensi digital — persetujuan dari HRD diperlukan sebelum akun aktif."
        >
            <Head title="Daftar Karyawan" />

            <h2 className="text-2xl font-bold text-gray-900">
                Daftar Karyawan
            </h2>
            <p className="mt-1 text-sm text-gray-500">
                Isi data di bawah ini untuk membuat akun.
            </p>

            <form onSubmit={submit} className="mt-6 space-y-4">
                <div>
                    <label
                        htmlFor="name"
                        className="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Nama
                    </label>
                    <Input
                        id="name"
                        icon={User}
                        value={data.name}
                        autoComplete="name"
                        autoFocus
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    {errors.name && (
                        <p className="mt-1 text-sm text-red-600">
                            {errors.name}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="email"
                        className="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Email
                    </label>
                    <Input
                        id="email"
                        type="email"
                        icon={Mail}
                        value={data.email}
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    {errors.email && (
                        <p className="mt-1 text-sm text-red-600">
                            {errors.email}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="no_whatsapp"
                        className="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Nomor WhatsApp
                    </label>
                    <Input
                        id="no_whatsapp"
                        icon={Phone}
                        value={data.no_whatsapp}
                        autoComplete="tel"
                        onChange={(e) =>
                            setData('no_whatsapp', e.target.value)
                        }
                    />
                    {errors.no_whatsapp && (
                        <p className="mt-1 text-sm text-red-600">
                            {errors.no_whatsapp}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="instansi_id"
                        className="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Instansi
                    </label>
                    <select
                        id="instansi_id"
                        value={data.instansi_id}
                        className={selectClassName}
                        onChange={(e) => {
                            setData('instansi_id', e.target.value);
                            setData('jabatan_id', '');
                        }}
                    >
                        <option value="">Pilih instansi</option>
                        {instansiOptions.map((instansi) => (
                            <option key={instansi.id} value={instansi.id}>
                                {instansi.nama}
                            </option>
                        ))}
                    </select>
                    {errors.instansi_id && (
                        <p className="mt-1 text-sm text-red-600">
                            {errors.instansi_id}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="jabatan_id"
                        className="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Jabatan/Divisi
                    </label>
                    <select
                        id="jabatan_id"
                        value={data.jabatan_id}
                        className={selectClassName}
                        onChange={(e) =>
                            setData('jabatan_id', e.target.value)
                        }
                        disabled={!data.instansi_id}
                    >
                        <option value="">
                            {data.instansi_id
                                ? 'Pilih jabatan'
                                : 'Pilih instansi terlebih dahulu'}
                        </option>
                        {jabatanOptions.map((jabatan) => (
                            <option key={jabatan.id} value={jabatan.id}>
                                {jabatan.nama}
                            </option>
                        ))}
                    </select>
                    {errors.jabatan_id && (
                        <p className="mt-1 text-sm text-red-600">
                            {errors.jabatan_id}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="password"
                        className="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Password
                    </label>
                    <Input
                        id="password"
                        type="password"
                        icon={Lock}
                        value={data.password}
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    {errors.password && (
                        <p className="mt-1 text-sm text-red-600">
                            {errors.password}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="password_confirmation"
                        className="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Konfirmasi Password
                    </label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        icon={Lock}
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                    />
                    {errors.password_confirmation && (
                        <p className="mt-1 text-sm text-red-600">
                            {errors.password_confirmation}
                        </p>
                    )}
                </div>

                <Button
                    type="submit"
                    className="w-full justify-center"
                    disabled={processing}
                >
                    Daftar
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-gray-500">
                Sudah punya akun?{' '}
                <Link
                    href={route('login')}
                    className="font-medium text-blue-600 hover:underline"
                >
                    Masuk di sini
                </Link>
            </p>
        </AuthLayout>
    );
}
