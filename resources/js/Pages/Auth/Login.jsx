import Button from '@/Components/Absensi/Button';
import Input from '@/Components/Absensi/Input';
import AuthLayout from '@/Layouts/Absensi/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Lock, ShieldCheck, User } from 'lucide-react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout
            heading="Satu portal untuk seluruh sistem internal General Solusindo."
            description="Masuk untuk mengakses tes psikotes rekrutmen dan absensi karyawan dalam satu sistem yang terintegrasi."
        >
            <Head title="Masuk" />

            <h2 className="text-2xl font-bold text-gray-900">Masuk</h2>
            <p className="mt-1 text-sm text-gray-500">
                Silakan masuk menggunakan akun Anda.
            </p>

            {status && (
                <div className="mt-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="mt-6 space-y-4">
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
                        icon={User}
                        value={data.email}
                        autoComplete="username"
                        autoFocus
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
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    {errors.password && (
                        <p className="mt-1 text-sm text-red-600">
                            {errors.password}
                        </p>
                    )}
                </div>

                <div className="flex items-center justify-between">
                    <label className="flex items-center gap-2 text-sm text-gray-600">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        Ingat saya
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-sm text-blue-600 hover:underline"
                        >
                            Lupa password?
                        </Link>
                    )}
                </div>

                <Button
                    type="submit"
                    className="w-full justify-center"
                    disabled={processing}
                >
                    Masuk
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-gray-500">
                Karyawan baru?{' '}
                <Link
                    href={route('karyawan.register')}
                    className="font-medium text-blue-600 hover:underline"
                >
                    Daftar di sini
                </Link>
            </p>

            <p className="mt-4 flex items-center justify-center gap-1.5 text-center text-xs text-gray-400">
                <ShieldCheck className="h-3.5 w-3.5" />
                Akses khusus pengguna internal
            </p>
        </AuthLayout>
    );
}
