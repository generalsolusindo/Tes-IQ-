import SidebarNavItem from '@/Components/Absensi/SidebarNavItem';
import { router } from '@inertiajs/react';
import { Bell, LogOut } from 'lucide-react';

function todayLabel() {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

/**
 * Sidebar shell for every page after login (karyawan dashboard/riwayat,
 * HRD approval/rekap). `navItems` lets each page group render its own
 * menu (karyawan and HRD see different items) through the same chrome.
 */
export default function AppLayout({ navItems, user, roleLabel, children }) {
    return (
        <div className="flex min-h-screen bg-gray-50">
            <aside className="flex w-72 flex-col border-r border-gray-200 bg-white px-4 py-6">
                <div className="px-2">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-absensi-navy-start">
                        <span className="text-sm font-bold text-white">
                            G
                        </span>
                    </div>
                    <p className="mt-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
                        Absensi Internal
                    </p>
                </div>

                <nav className="mt-8 flex flex-1 flex-col gap-1">
                    {navItems.map((item) => (
                        <SidebarNavItem key={item.href} {...item} />
                    ))}
                </nav>

                <div className="border-t border-gray-200 px-2 pt-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-absensi-navy-start text-sm font-semibold text-white">
                            {user?.name?.charAt(0)?.toUpperCase()}
                        </div>
                        <div>
                            <p className="text-sm font-semibold text-gray-900">
                                {user?.name}
                            </p>
                            <p className="text-xs text-gray-500">
                                {roleLabel}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={() => router.post(route('logout'))}
                        className="mt-3 flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900"
                    >
                        <LogOut className="h-4 w-4" />
                        Keluar
                    </button>
                </div>
            </aside>

            <div className="flex-1">
                <header className="flex items-center justify-between border-b border-gray-200 bg-white px-8 py-5">
                    <div>
                        <p className="text-lg font-semibold text-gray-900">
                            Halo, {user?.name} 👋
                        </p>
                        <p className="text-sm text-gray-500">
                            {todayLabel()}
                        </p>
                    </div>

                    <button
                        type="button"
                        className="flex h-10 w-10 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        aria-label="Notifikasi"
                    >
                        <Bell className="h-5 w-5" />
                    </button>
                </header>

                <main className="p-8">{children}</main>
            </div>
        </div>
    );
}
