import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

const KIND_LABELS = {
    example: 'Soal Contoh',
    scored: 'Soal Dinilai',
};

const ACTIVE_FILTERS = [
    { value: '', label: 'Semua' },
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Nonaktif' },
];

const normalize = (value) => (value ?? '').trim().toLowerCase();

function formatSeconds(seconds) {
    const total = Number(seconds) || 0;
    const minutes = Math.floor(total / 60);
    const rest = total % 60;

    return rest === 0 ? `${minutes} menit` : `${minutes} menit ${rest} detik`;
}

// ME prompts read "Kata yang mempunyai huruf permulaan X termasuk kelompok …",
// so the target word is resolved from the bank by that initial letter.
function resolveQuestion(question, groups) {
    const match = question.prompt?.match(/huruf permulaan\s+([A-Za-z])/i);
    const initial = match?.[1]?.toUpperCase() ?? null;
    const correct = question.options.find((option) => option.is_correct);

    let word = null;
    let group = null;

    if (initial) {
        for (const candidate of groups) {
            const found = candidate.words.find(
                (w) => w.charAt(0).toUpperCase() === initial,
            );

            if (found) {
                word = found;
                group = candidate;
                break;
            }
        }
    }

    let status = 'ok';

    if (!initial) {
        status = 'no-initial';
    } else if (!group) {
        status = 'no-word';
    } else if (!correct) {
        status = 'no-key';
    } else if (normalize(correct.option_text) !== normalize(group.name)) {
        status = 'mismatch';
    }

    return { initial, word, group, correct, status };
}

const STATUS_MESSAGES = {
    'no-initial': 'Huruf awal tidak terbaca dari teks soal.',
    'no-word': 'Tidak ada kata di bank hafalan dengan huruf awal ini.',
    'no-key': 'Belum ada opsi yang ditandai benar.',
    mismatch: 'Kunci jawaban tidak sesuai dengan kelompok kata di bank hafalan.',
};

function WordBank({ groups }) {
    if (groups.length === 0) {
        return (
            <div className="rounded-lg bg-white p-6 text-center text-sm text-red-500 shadow-sm">
                Materi hafalan ME belum diatur atau tidak valid.
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            {groups.map((group) => (
                <div
                    key={group.key}
                    className="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        {group.key}
                    </div>
                    <div className="mb-3 text-base font-semibold text-gray-900">
                        {group.name}
                    </div>
                    <ul className="space-y-1">
                        {group.words.map((word) => (
                            <li
                                key={word}
                                className="font-mono text-sm tracking-wide text-gray-700"
                            >
                                {word}
                            </li>
                        ))}
                    </ul>
                </div>
            ))}
        </div>
    );
}

function QuestionCard({ question, groups }) {
    const { initial, word, group, status } = resolveQuestion(question, groups);
    const hasIssue = status !== 'ok';

    return (
        <div
            className={`rounded-2xl border bg-white p-5 shadow-sm ${
                hasIssue ? 'border-red-300' : 'border-gray-200'
            }`}
        >
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-3">
                    <div className="text-sm font-semibold text-gray-900">
                        Soal #{question.question_number}
                    </div>
                    {initial && (
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 font-mono text-lg font-bold text-indigo-700">
                            {initial}
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    {!question.is_active && (
                        <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                            Nonaktif
                        </span>
                    )}
                    <Link href={route('admin.ist-questions.edit', question.id)}>
                        <SecondaryButton type="button">Ubah</SecondaryButton>
                    </Link>
                </div>
            </div>

            <p className="mb-3 text-sm text-gray-700">{question.prompt}</p>

            <div className="grid grid-cols-2 gap-2 sm:grid-cols-5">
                {question.options.map((option) => (
                    <div
                        key={option.option_key}
                        className={`rounded-xl border px-3 py-2 text-sm ${
                            option.is_correct
                                ? 'border-green-400 bg-green-50'
                                : 'border-gray-200 bg-white'
                        }`}
                    >
                        <span className="mr-1 font-semibold text-gray-700">
                            {option.option_key}.
                        </span>
                        <span className="text-gray-800">
                            {option.option_text ?? '-'}
                        </span>
                        {option.is_correct && (
                            <span className="ml-1 text-xs text-green-600">
                                (benar)
                            </span>
                        )}
                    </div>
                ))}
            </div>

            <div
                className={`mt-3 rounded-lg px-3 py-2 text-xs ${
                    hasIssue
                        ? 'bg-red-50 text-red-700'
                        : 'bg-gray-50 text-gray-600'
                }`}
            >
                {word && group && (
                    <span>
                        Kata di bank hafalan: <b className="font-mono">{word}</b>{' '}
                        &rarr; kelompok <b>{group.name}</b>.{' '}
                    </span>
                )}
                {hasIssue ? STATUS_MESSAGES[status] : 'Kunci jawaban sesuai.'}
            </div>

            {question.example_explanation && (
                <p className="mt-3 text-xs italic text-gray-500">
                    {question.example_explanation}
                </p>
            )}
        </div>
    );
}

export default function MePreview({ subtest, groups, questions, filters }) {
    const applyFilter = (active) =>
        router.get(
            route('admin.ist-questions.me-preview'),
            { active },
            { preserveState: true, replace: true },
        );

    const examples = questions.filter((q) => q.kind === 'example');
    const scored = questions.filter((q) => q.kind === 'scored');
    const issueCount = questions.filter(
        (q) => resolveQuestion(q, groups).status !== 'ok',
    ).length;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Preview Soal ME
                </h2>
            }
        >
            <Head title="Preview Soal ME" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-2 bg-white p-4 shadow-sm sm:rounded-lg">
                        <div className="text-sm text-gray-700">
                            <div className="font-semibold text-gray-900">
                                {subtest
                                    ? `${subtest.code} — ${subtest.name}`
                                    : 'Subtes ME tidak ditemukan'}
                            </div>
                            {subtest && (
                                <div className="text-xs text-gray-500">
                                    Menghafal{' '}
                                    {formatSeconds(subtest.memorization_seconds)}{' '}
                                    &middot; Menjawab{' '}
                                    {formatSeconds(subtest.answering_seconds)}
                                </div>
                            )}
                        </div>

                        <Link href={route('admin.ist-questions.index')}>
                            <SecondaryButton type="button">
                                Kembali ke Bank Soal
                            </SecondaryButton>
                        </Link>
                    </div>

                    {subtest?.instruction_content && (
                        <div className="bg-white p-4 shadow-sm sm:rounded-lg">
                            <h3 className="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Instruksi
                            </h3>
                            <p className="whitespace-pre-line text-sm text-gray-700">
                                {subtest.instruction_content}
                            </p>
                        </div>
                    )}

                    <div className="space-y-3">
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                            Materi Hafalan
                        </h3>
                        <WordBank groups={groups} />
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-2 bg-white p-4 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Status
                            </span>
                            {ACTIVE_FILTERS.map((option) => (
                                <SecondaryButton
                                    key={option.value}
                                    type="button"
                                    onClick={() => applyFilter(option.value)}
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
                        <span
                            className={`text-xs font-medium ${
                                issueCount > 0
                                    ? 'text-red-600'
                                    : 'text-green-600'
                            }`}
                        >
                            {issueCount > 0
                                ? `${issueCount} dari ${questions.length} soal perlu dicek`
                                : `Semua ${questions.length} soal sesuai bank hafalan`}
                        </span>
                    </div>

                    {questions.length === 0 && (
                        <div className="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow-sm">
                            Belum ada soal untuk subtes ini.
                        </div>
                    )}

                    {[
                        ['example', examples],
                        ['scored', scored],
                    ].map(
                        ([kind, list]) =>
                            list.length > 0 && (
                                <div key={kind} className="space-y-3">
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                        {KIND_LABELS[kind]}
                                    </h3>
                                    <div className="space-y-4">
                                        {list.map((question) => (
                                            <QuestionCard
                                                key={question.id}
                                                question={question}
                                                groups={groups}
                                            />
                                        ))}
                                    </div>
                                </div>
                            ),
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
