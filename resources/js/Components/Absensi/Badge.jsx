import { cn } from '@/Support/cn';
import { cva } from 'class-variance-authority';

const badgeVariants = cva(
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
    {
        variants: {
            variant: {
                blue: 'bg-blue-100 text-blue-700',
                red: 'bg-red-100 text-red-700',
                green: 'bg-green-100 text-green-700',
                amber: 'bg-amber-100 text-amber-700',
                gray: 'bg-gray-100 text-gray-700',
            },
        },
        defaultVariants: {
            variant: 'gray',
        },
    },
);

export default function Badge({ className, variant, ...props }) {
    return (
        <span
            className={cn(badgeVariants({ variant }), className)}
            {...props}
        />
    );
}
