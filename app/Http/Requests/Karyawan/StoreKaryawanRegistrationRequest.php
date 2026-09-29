<?php

namespace App\Http\Requests\Karyawan;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class StoreKaryawanRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'no_whatsapp' => ['required', 'string', 'max:20'],
            'instansi_id' => ['required', 'integer', 'exists:instansi,id'],
            'jabatan_id' => ['required', 'integer', 'exists:jabatan,id'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * jabatan_id alone isn't enough: it must actually be attached to the
     * chosen instansi_id via instansi_jabatan, otherwise a karyawan could
     * self-register into a jabatan that instansi never offered.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            if (! $this->filled('instansi_id') || ! $this->filled('jabatan_id')) {
                return;
            }

            $isAttached = DB::table('instansi_jabatan')
                ->where('instansi_id', $this->integer('instansi_id'))
                ->where('jabatan_id', $this->integer('jabatan_id'))
                ->exists();

            if (! $isAttached) {
                $validator->errors()->add(
                    'jabatan_id',
                    'Jabatan yang dipilih tidak tersedia untuk instansi ini.',
                );
            }
        });
    }
}
