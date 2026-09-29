import { cn } from '@/Support/cn';
import { Link } from '@inertiajs/react';

export default function SidebarNavItem({
    href,
    pattern,
    icon: Icon,
    label,
    count,
}) {
    const active = route().current(pattern);

    return (
        <Link
            href={href}
            className={cn(
                'flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors',
                active
                    ? 'bg-absensi-navy-start text-white'
                    : 'text-gray-600 hover:bg-gray-100',
            )}
        >
            <span className="flex items-center gap-3">
                <Icon className="h-4 w-4" />
                {label}
            </span>

            {count > 0 && (
                <span className="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-semibold text-white">
                    {count}
                </span>
            )}
        </Link>
    );
}
