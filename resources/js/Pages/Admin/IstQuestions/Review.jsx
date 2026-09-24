import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useRef, useState } from 'react';

const KIND_LABELS = {
    example: 'Soal Contoh',
    scored: 'Soal Dinilai',
};

function BrokenImageNotice() {
    return (
        <span className="px-1 text-center text-xs font-medium text-red-500">
            Gambar tidak ditemukan
        </span>
    );
}

function useImageUpload(url) {
    const inputRef = useRef(null);
    const [uploading, setUploading] = useState(false);

    const trigger = () => inputRef.current?.click();

    const handleChange = (event) => {
        const file = event.target.files?.[0];
        event.target.value = '';

        if (!file) {
            return;
        }

        setUploading(true);
        router.post(
            url,
            { image: file },
            {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setUploading(false),
            },
        );
    };

    return { inputRef, trigger, handleChange, uploading };
}

function OptionCard({ question, option }) {
    const [broken, setBroken] = useState(false);
    const upload = useImageUpload(
        route('admin.ist-questions.upload-option-image', [
            question.id,
            option.id,
        ]),
    );

    return (
        <div
            className={`flex flex-col items-center gap-2 rounded-xl border p-3 ${
                option.is_correct
                    ? 'border-green-400 bg-green-50'
                    : 'border-gray-200 bg-white'
            }`}
        >
            <div className="flex h-24 w-full items-center justify-center overflow-hidden rounded-lg bg-white">
                {option.image_url && !broken ? (
                    <img
                        src={option.image_url}
                        alt={option.image_alt ?? `Opsi ${option.option_key}`}
                        className="max-h-24 max-w-full object-contain"
                        onError={() => setBroken(true)}
                    />
                ) : option.image_url ? (
                    <BrokenImageNotice />
                ) : (
                    <span className="px-1 text-center text-xs text-gray-600">
                        {option.option_text ?? '-'}
                    </span>
                )}
            </div>
            <div className="text-xs font-semibold text-gray-700">
                {option.option_key}
                {option.is_correct && (
                    <span className="ml-1 text-green-600">(benar)</span>
                )}
            </div>
            <div className="text-[11px] text-gray-500">
                skor {option.score_value}
            </div>
            <input
                type="file"
                accept="image/*"
                ref={upload.inputRef}
                onChange={upload.handleChange}
                className="hidden"
            />
            <button
                type="button"
                onClick={upload.trigger}
                disabled={upload.uploading}
                className="text-[11px] font-medium text-indigo-600 hover:text-indigo-800 disabled:opacity-50"
            >
                {upload.uploading ? 'Mengunggah...' : 'Upload Gambar'}
            </button>
        </div>
    );
}

function QuestionCard({ question }) {
    const [broken, setBroken] = useState(false);
    const upload = useImageUpload(
        route('admin.ist-questions.upload-image', question.id),
    );

    return (
        <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div className="mb-3 flex items-center justify-between">
                <div className="text-sm font-semibold text-gray-900">
                    Soal #{question.question_number}
                </div>
                <div className="flex items-center gap-2">
                    {!question.is_active && (
                        <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                            Nonaktif
                        </span>
                    )}
                    <input
                        type="file"
                        accept="image/*"
                        ref={upload.inputRef}
                        onChange={upload.handleChange}
                        className="hidden"
                    />
                    <SecondaryButton
                        type="button"
                        onClick={upload.trigger}
                        disabled={upload.uploading}
                    >
                        {upload.uploading
                            ? 'Mengunggah...'
                            : 'Upload Gambar'}
                    </SecondaryButton>
                    <Link
                        href={route('admin.ist-questions.edit', question.id)}
                    >
                        <SecondaryButton type="button">Ubah</SecondaryButton>
                    </Link>
                </div>
            </div>

            {question.prompt && (
                <p className="mb-3 text-sm text-gray-700">
                    {question.prompt}
                </p>
            )}

            <div className="mb-4 flex min-h-32 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-3">
                {question.image_url && !broken ? (
                    <img
                        src={question.image_url}
                        alt={question.image_alt ?? `Soal ${question.question_number}`}
                        className="max-h-56 max-w-full object-contain"
                        onError={() => setBroken(true)}
                    />
                ) : question.image_url ? (
                    <BrokenImageNotice />
                ) : (
                    <span className="text-xs text-gray-400">
                        Tidak ada gambar soal
                    </span>
                )}
            </div>

            {question.options.length > 0 && (
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    {question.options.map((option) => (
                        <OptionCard
                            key={option.option_key}
                            question={question}
                            option={option}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

const ACTIVE_FILTERS = [
    { value: '', label: 'Semua' },
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Nonaktif' },
];

export default function Review({ subtests, selectedSubtestId, questions, filters }) {
    const applyFilters = (next) => {
        router.get(
            route('admin.ist-questions.review'),
            {
                subtest_id: selectedSubtestId,
                active: filters.active,
                ...next,
            },
            { preserveState: true, replace: true },
        );
    };

    const selectSubtest = (subtestId) =>
        applyFilters({ subtest_id: subtestId });

    const examples = questions.filter((q) => q.kind === 'example');
    const scored = questions.filter((q) => q.kind === 'scored');

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Review Visual Soal FA/WU
                </h2>
            }
        >
            <Head title="Review Visual Soal FA/WU" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-2 bg-white p-4 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-center gap-2">
                            {subtests.map((subtest) => (
                                <SecondaryButton
                                    key={subtest.id}
                                    type="button"
                                    onClick={() => selectSubtest(subtest.id)}
                                    className={
                                        Number(selectedSubtestId) ===
                                        subtest.id
                                            ? 'ring-2 ring-indigo-500'
                                            : ''
                                    }
                                >
                                    {subtest.code} &mdash; {subtest.name}
                                </SecondaryButton>
                            ))}
                        </div>

                        <Link href={route('admin.ist-questions.index')}>
                            <SecondaryButton type="button">
                                Kembali ke Bank Soal
                            </SecondaryButton>
                        </Link>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 bg-white p-4 shadow-sm sm:rounded-lg">
                        <span className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Status
                        </span>
                        {ACTIVE_FILTERS.map((option) => (
                            <SecondaryButton
                                key={option.value}
                                type="button"
                                onClick={() =>
                                    applyFilters({ active: option.value })
                                }
                                className={
                                    (filters.active ?? '') === option.value
                                        ? 'ring-2 ring-indigo-500'
                                        : ''
                                }
                            >
                                {option.label}
                            </SecondaryButton>
                        ))}
                    </div>

                    {questions.length === 0 && (
                        <div className="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow-sm">
                            Belum ada soal untuk subtes ini.
                        </div>
                    )}

                    {examples.length > 0 && (
                        <div className="space-y-3">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                {KIND_LABELS.example}
                            </h3>
                            <div className="space-y-4">
                                {examples.map((question) => (
                                    <QuestionCard
                                        key={question.id}
                                        question={question}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {scored.length > 0 && (
                        <div className="space-y-3">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                {KIND_LABELS.scored}
                            </h3>
                            <div className="space-y-4">
                                {scored.map((question) => (
                                    <QuestionCard
                                        key={question.id}
                                        question={question}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
