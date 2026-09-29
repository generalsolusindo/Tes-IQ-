import Card from '@/Components/Absensi/Card';
import { cn } from '@/Support/cn';
import { cva } from 'class-variance-authority';

const iconTintVariants = cva(
    'flex h-10 w-10 items-center justify-center rounded-xl',
    {
        variants: {
            tint: {
                blue: 'bg-blue-100 text-blue-600',
                amber: 'bg-amber-100 text-amber-600',
                red: 'bg-red-100 text-red-600',
                green: 'bg-green-100 text-green-600',
            },
        },
        defaultVariants: {
            tint: 'blue',
        },
    },
);

export default function IconStat({ icon: Icon, tint, value, label }) {
    return (
        <Card className="flex items-center gap-3 p-4">
            <div className={cn(iconTintVariants({ tint }))}>
                <Icon className="h-5 w-5" />
            </div>

            <div>
                <div className="text-2xl font-bold text-gray-900">
                    {value}
                </div>
                <div className="text-sm text-gray-500">{label}</div>
            </div>
        </Card>
    );
}
