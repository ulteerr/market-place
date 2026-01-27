<?php

declare(strict_types=1);

namespace App\Shared\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class CrudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    abstract protected function ruleset(): array;

    public function rules(): array
    {
        return $this->ruleset();
    }
}
