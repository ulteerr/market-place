export const ERROR_REPORT_MAX_ATTACHMENTS = 5;
export const ERROR_REPORT_MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;
export const ERROR_REPORT_ALLOWED_MIME_TYPES = [
  'image/png',
  'image/jpeg',
  'image/webp',
  'image/gif',
  'application/pdf',
  'text/plain',
] as const;

export type ReportAttachmentMeta = {
  name: string;
  safeName: string;
  type: string;
  size: number;
};

export type AttachmentValidationIssue = {
  fileName: string;
  reason: 'too-many-files' | 'invalid-type' | 'file-too-large' | 'empty-file';
};

export type AttachmentValidationResult = {
  accepted: ReportAttachmentMeta[];
  issues: AttachmentValidationIssue[];
};

const sanitizeFileName = (name: string): string => {
  const trimmed = name.trim();
  const safe = trimmed
    .replace(/[^\w.\-() ]+/g, '_')
    .replace(/\s+/g, ' ')
    .slice(0, 120);

  return safe || 'attachment';
};

const isAllowedMimeType = (type: string): boolean => {
  return ERROR_REPORT_ALLOWED_MIME_TYPES.includes(
    type as (typeof ERROR_REPORT_ALLOWED_MIME_TYPES)[number]
  );
};

export const validateReportAttachments = (
  files: ArrayLike<Pick<File, 'name' | 'type' | 'size'>>
): AttachmentValidationResult => {
  const list = Array.from(files || []);
  const issues: AttachmentValidationIssue[] = [];
  const accepted: ReportAttachmentMeta[] = [];

  if (list.length > ERROR_REPORT_MAX_ATTACHMENTS) {
    list.slice(ERROR_REPORT_MAX_ATTACHMENTS).forEach((file) => {
      issues.push({
        fileName: file.name,
        reason: 'too-many-files',
      });
    });
  }

  list.slice(0, ERROR_REPORT_MAX_ATTACHMENTS).forEach((file) => {
    if (!file.size || file.size <= 0) {
      issues.push({
        fileName: file.name,
        reason: 'empty-file',
      });
      return;
    }

    if (file.size > ERROR_REPORT_MAX_FILE_SIZE_BYTES) {
      issues.push({
        fileName: file.name,
        reason: 'file-too-large',
      });
      return;
    }

    if (!isAllowedMimeType(file.type)) {
      issues.push({
        fileName: file.name,
        reason: 'invalid-type',
      });
      return;
    }

    accepted.push({
      name: file.name,
      safeName: sanitizeFileName(file.name),
      size: file.size,
      type: file.type,
    });
  });

  return {
    accepted,
    issues,
  };
};
