import { describe, expect, it } from 'vitest';
import {
  ERROR_REPORT_MAX_ATTACHMENTS,
  ERROR_REPORT_MAX_FILE_SIZE_BYTES,
  validateReportAttachments,
} from '~/composables/error-reporting/attachments';

describe('validateReportAttachments', () => {
  it('accepts valid attachments and sanitizes file names', () => {
    const result = validateReportAttachments([
      {
        name: '  screen shot #1.png  ',
        type: 'image/png',
        size: 1024,
      },
    ]);

    expect(result.issues).toEqual([]);
    expect(result.accepted).toEqual([
      {
        name: '  screen shot #1.png  ',
        safeName: 'screen shot _1.png',
        type: 'image/png',
        size: 1024,
      },
    ]);
  });

  it('rejects unsupported mime type and oversized files', () => {
    const result = validateReportAttachments([
      {
        name: 'archive.zip',
        type: 'application/zip',
        size: 1000,
      },
      {
        name: 'big.png',
        type: 'image/png',
        size: ERROR_REPORT_MAX_FILE_SIZE_BYTES + 1,
      },
    ]);

    expect(result.accepted).toEqual([]);
    expect(result.issues).toEqual([
      { fileName: 'archive.zip', reason: 'invalid-type' },
      { fileName: 'big.png', reason: 'file-too-large' },
    ]);
  });

  it('limits total amount of attachments', () => {
    const files = Array.from({ length: ERROR_REPORT_MAX_ATTACHMENTS + 1 }, (_, index) => ({
      name: `file-${index + 1}.png`,
      type: 'image/png',
      size: 512,
    }));

    const result = validateReportAttachments(files);
    expect(result.accepted).toHaveLength(ERROR_REPORT_MAX_ATTACHMENTS);
    expect(result.issues).toEqual([
      { fileName: `file-${ERROR_REPORT_MAX_ATTACHMENTS + 1}.png`, reason: 'too-many-files' },
    ]);
  });
});
