import { ShieldCheck } from 'lucide-react';

/**
 * Split-screen shell for karyawan/HRD-facing auth pages (login, register,
 * pending-approval). Left panel is a static navy-gradient brand pane;
 * `children` renders the actual form on the right, so each page keeps
 * full control over its own fields/validation.
 */
export default function AuthLayout({
    eyebrow = 'Portal Internal',
    heading,
    description,
    children,
}) {
    return (
        <div className="flex min-h-screen">
            <div className="relative hidden w-[45%] flex-col justify-between overflow-hidden bg-gradient-to-br from-absensi-navy-start to-absensi-navy-end p-12 lg:flex">
                <div>
                    <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white">
                        <span className="text-lg font-bold text-absensi-navy-start">
                            General
                        </span>
                    </div>

                    <span className="mt-10 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm text-white">
                        <ShieldCheck className="h-4 w-4" />
                        {eyebrow}
                    </span>

                    <h1 className="mt-6 max-w-md text-4xl font-extrabold leading-tight text-white">
                        {heading}
                    </h1>

                    <p className="mt-4 max-w-md text-blue-200">
                        {description}
                    </p>
                </div>

                <p className="text-sm text-blue-300">
                    © {new Date().getFullYear()} General Solusindo. Internal
                    use only.
                </p>
            </div>

            <div className="flex flex-1 items-center justify-center bg-white px-6 py-12">
                <div className="w-full max-w-sm">{children}</div>
            </div>
        </div>
    );
}
