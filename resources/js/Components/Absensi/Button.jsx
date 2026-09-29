import { cn } from '@/Support/cn';
import { cva } from 'class-variance-authority';
import { forwardRef } from 'react';

const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
    {
        variants: {
            variant: {
                primary: 'bg-blue-600 text-white hover:bg-blue-700',
                dark: 'bg-absensi-navy-start text-white hover:bg-absensi-navy-end',
                outline:
                    'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                ghost: 'text-gray-600 hover:bg-gray-100',
                danger: 'bg-red-600 text-white hover:bg-red-700',
            },
            size: {
                default: 'px-4 py-2.5',
                sm: 'px-3 py-1.5 text-xs',
                lg: 'px-5 py-3 text-base',
            },
        },
        defaultVariants: {
            variant: 'primary',
            size: 'default',
        },
    },
);

export default forwardRef(function Button(
    { className, variant, size, ...props },
    ref,
) {
    return (
        <button
            ref={ref}
            className={cn(buttonVariants({ variant, size }), className)}
            {...props}
        />
    );
});
