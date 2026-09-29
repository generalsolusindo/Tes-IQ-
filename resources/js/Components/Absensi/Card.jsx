import { cn } from '@/Support/cn';

export default function Card({ className, ...props }) {
    return (
        <div
            className={cn(
                'rounded-2xl border border-gray-200 bg-white shadow-sm',
                className,
            )}
            {...props}
        />
    );
}
