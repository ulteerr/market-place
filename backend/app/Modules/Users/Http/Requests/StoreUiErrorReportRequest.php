<?php

declare(strict_types=1);

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreUiErrorReportRequest extends FormRequest
{
    private const FORBIDDEN_EXTENSIONS = [
        "exe",
        "dll",
        "bat",
        "cmd",
        "com",
        "scr",
        "js",
        "jar",
        "php",
        "phar",
        "phtml",
        "sh",
        "ps1",
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "page" => ["required", "array"],
            "page.url" => ["required", "string", "max:2000"],
            "page.path" => ["required", "string", "max:2000"],
            "page.routeName" => ["nullable", "string", "max:255"],

            "block" => ["required", "array"],
            "block.id" => ["required", "string", "max:255"],
            "block.strategy" => ["required", "string", "max:64"],
            "block.queryPath" => ["required", "string", "max:2000"],
            "block.selectedAt" => ["required", "date"],

            "description" => ["required", "string", "max:5000"],
            "attachments" => ["sometimes", "array", "max:5"],
            "attachments.*.name" => ["required", "string", "max:255"],
            "attachments.*.safeName" => [
                "required",
                "string",
                "max:255",
                "regex:/^[A-Za-z0-9._\\-() ]+$/",
            ],
            "attachments.*.type" => [
                "required",
                "string",
                Rule::in([
                    "image/png",
                    "image/jpeg",
                    "image/webp",
                    "image/gif",
                    "application/pdf",
                    "text/plain",
                ]),
            ],
            "attachments.*.size" => ["required", "integer", "min:1", "max:10485760"],

            "context" => ["required", "array"],
            "context.userAgent" => ["required", "string", "max:2000"],
            "context.viewport" => ["required", "array"],
            "context.viewport.width" => ["required", "integer", "min:0", "max:20000"],
            "context.viewport.height" => ["required", "integer", "min:0", "max:20000"],
            "context.theme" => ["required", "string", "max:32"],
            "context.locale" => ["required", "string", "max:16"],
            "context.timestamp" => ["required", "date"],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $attachments = $this->input("attachments", []);
            if (!is_array($attachments)) {
                return;
            }

            foreach ($attachments as $index => $attachment) {
                if (!is_array($attachment)) {
                    continue;
                }

                $safeName = strtolower((string) ($attachment["safeName"] ?? ""));
                if ($safeName === "") {
                    continue;
                }

                if (
                    str_contains($safeName, "..") ||
                    str_contains($safeName, "/") ||
                    str_contains($safeName, "\\")
                ) {
                    $validator
                        ->errors()
                        ->add("attachments.{$index}.safeName", "Unsafe attachment file name.");
                    continue;
                }

                $extension = pathinfo($safeName, PATHINFO_EXTENSION);
                if (
                    is_string($extension) &&
                    $extension !== "" &&
                    in_array($extension, self::FORBIDDEN_EXTENSIONS, true)
                ) {
                    $validator
                        ->errors()
                        ->add(
                            "attachments.{$index}.safeName",
                            "Forbidden attachment file extension.",
                        );
                }
            }
        });
    }
}
