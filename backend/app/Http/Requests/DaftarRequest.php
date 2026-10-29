<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DaftarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // tamu boleh mendaftar
    }

    public function rules(): array
    {
        return [
            'nama'       => ['required', 'string', 'max:120'],
            'email'      => ['required', 'email', 'max:160', 'unique:pengguna,email'],
            'password'   => ['required', 'string', 'min:8', 'max:128'],
            'organisasi' => ['nullable', 'string', 'max:160'],
            'no_hp'      => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required'       => 'Nama wajib diisi.',
            'nama.max'            => 'Nama maksimal 120 karakter.',
            'email.required'      => 'Email wajib diisi.',
            'email.email'         => 'Format email tidak valid.',
            'email.unique'        => 'Email sudah terdaftar.',
            'email.max'           => 'Email maksimal 160 karakter.',
            'password.required'   => 'Password wajib diisi.',
            'password.min'        => 'Password minimal 8 karakter.',
            'password.max'        => 'Password maksimal 128 karakter.',
            'organisasi.max'      => 'Nama organisasi maksimal 160 karakter.',
            'no_hp.max'           => 'Nomor HP maksimal 20 karakter.',
        ];
    }
}
