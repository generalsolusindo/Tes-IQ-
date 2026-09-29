import { cn } from '@/Support/cn';
import { forwardRef } from 'react';

/**
 * `icon` renders inside the input as a left-aligned prefix (matches the
 * person/lock icon style in the CRM reference). Omit it for a plain input.
 */
export default forwardRef(function Input(
    { className, icon: Icon, type = 'text', ...props },
    ref,
) {
    return (
        <div className="relative">
            {Icon && (
                <Icon className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            )}

            <input
                ref={ref}
                type={type}
                className={cn(
                    'block w-full rounded-xl border border-gray-300 bg-white py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:cursor-not-allowed disabled:opacity-50',
                    Icon ? 'pl-10 pr-3' : 'px-3',
                    className,
                )}
                {...props}
            />
        </div>
    );
});
