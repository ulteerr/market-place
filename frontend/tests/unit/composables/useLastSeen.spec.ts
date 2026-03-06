import { describe, expect, it } from 'vitest';
import { formatLastSeen } from '~/composables/useLastSeen';
import ruCommon from '~/i18n/locales/ru/common';

const now = new Date('2026-03-06T12:00:00.000Z');

const secondsAgo = (seconds: number): Date => new Date(now.getTime() - seconds * 1000);
const dictionary = { common: ruCommon } as Record<string, unknown>;

const getByPath = (source: Record<string, unknown>, path: string): string => {
  const value = path.split('.').reduce<unknown>((acc, segment) => {
    if (typeof acc !== 'object' || acc === null) {
      return undefined;
    }

    return (acc as Record<string, unknown>)[segment];
  }, source);

  return typeof value === 'string' ? value : path;
};

const t = (key: string, params?: Record<string, string | number>): string => {
  let result = getByPath(dictionary, key);

  for (const [name, value] of Object.entries(params ?? {})) {
    result = result.replace(`{${name}}`, String(value));
  }

  return result;
};

describe('formatLastSeen', () => {
  it('formats required threshold ranges', () => {
    expect(formatLastSeen(secondsAgo(30), { nowDate: now, locale: 'ru', t })).toBe('только что');
    expect(formatLastSeen(secondsAgo(2 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '2 минуты назад'
    );
    expect(formatLastSeen(secondsAgo(3 * 60 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '3 часа назад'
    );
    expect(formatLastSeen(secondsAgo(7 * 24 * 60 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '7 дней назад'
    );
    expect(
      formatLastSeen(secondsAgo(2 * 30 * 24 * 60 * 60), { nowDate: now, locale: 'ru', t })
    ).toBe('2 месяца назад');
    expect(
      formatLastSeen(secondsAgo(2 * 12 * 30 * 24 * 60 * 60), { nowDate: now, locale: 'ru', t })
    ).toBe('2 года назад');
  });

  it('uses correct russian plural forms for key values', () => {
    expect(formatLastSeen(secondsAgo(1 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '1 минута назад'
    );
    expect(formatLastSeen(secondsAgo(2 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '2 минуты назад'
    );
    expect(formatLastSeen(secondsAgo(5 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '5 минут назад'
    );
    expect(formatLastSeen(secondsAgo(21 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '21 минута назад'
    );

    expect(formatLastSeen(secondsAgo(1 * 60 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '1 час назад'
    );
    expect(formatLastSeen(secondsAgo(2 * 60 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '2 часа назад'
    );
    expect(formatLastSeen(secondsAgo(5 * 60 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '5 часов назад'
    );
    expect(formatLastSeen(secondsAgo(21 * 60 * 60), { nowDate: now, locale: 'ru', t })).toBe(
      '21 час назад'
    );
  });
});
