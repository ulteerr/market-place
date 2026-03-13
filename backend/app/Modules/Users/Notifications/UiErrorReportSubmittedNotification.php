<?php

declare(strict_types=1);

namespace Modules\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Users\Models\UiErrorReport;

final class UiErrorReportSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly UiErrorReport $report) {}

    public function via(object $notifiable): array
    {
        return ["mail"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $attachments = $this->report->attachments;
        $attachmentsCount = is_array($attachments) ? count($attachments) : 0;

        return (new MailMessage())
            ->subject("Новый UI error report #{$this->report->id}")
            ->line("Поступил новый отчет об ошибке из пользовательского интерфейса.")
            ->line("Report ID: {$this->report->id}")
            ->line("Статус: {$this->report->status}")
            ->line("Маршрут: " . ((string) ($this->report->route_name ?? "-")))
            ->line("Page URL: " . ((string) ($this->report->page_url ?? "-")))
            ->line("Block ID: {$this->report->block_id}")
            ->line("Вложений: {$attachmentsCount}")
            ->line("Описание: {$this->report->description}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "report_id" => (string) $this->report->id,
            "status" => (string) $this->report->status,
            "route_name" => (string) ($this->report->route_name ?? ""),
            "page_url" => (string) ($this->report->page_url ?? ""),
            "block_id" => (string) $this->report->block_id,
            "attachments_count" => is_array($this->report->attachments)
                ? count($this->report->attachments)
                : 0,
        ];
    }
}
