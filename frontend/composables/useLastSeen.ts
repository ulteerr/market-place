import { useI18n } from 'vue-i18n';

const MINUTE_SECONDS = 60;
const HOUR_SECONDS = 60 * MINUTE_SECONDS;
const DAY_SECONDS = 24 * HOUR_SECONDS;
const MONTH_SECONDS = 30 * DAY_SECONDS;
const YEAR_SECONDS = 12 * MONTH_SECONDS;

type LastSeenUnit = 'minute' | 'hour' | 'day' | 'month' | 'year';
type TranslateFn = (key: string, params?: Record<string, string | number>) => string;

const pluralCategoryRu = (value: number): 'one' | 'few' | 'many' => {
  const abs = Math.abs(value) % 100;
  const last = abs % 10;

  if (abs > 10 && abs < 20) {
    return 'many';
  }

  if (last > 1 && last < 5) {
    return 'few';
  }

  if (last === 1) {
    return 'one';
  }

  return 'many';
};

const pluralCategory = (value: number, locale: string): 'one' | 'few' | 'many' => {
  if (locale === 'ru') {
    return pluralCategoryRu(value);
  }

  return value === 1 ? 'one' : 'many';
};

const formatWithUnit = (
  value: number,
  unit: LastSeenUnit,
  locale: string,
  t: TranslateFn
): string => {
  const category = pluralCategory(value, locale);
  const unitLabel = t(`common.lastSeen.units.${unit}.${category}`);

  return t('common.lastSeen.ago', { value, unit: unitLabel });
};

export const formatLastSeen = (
  lastSeenAt: Date | string | number | null | undefined,
  options: {
    nowDate?: Date;
    locale?: string;
    t: TranslateFn;
  }
): string => {
  const nowDate = options.nowDate ?? new Date();
  const locale = options.locale ?? 'ru';
  const t = options.t;

  if (!lastSeenAt) {
    return t('common.lastSeen.justNow');
  }

  const parsed = new Date(lastSeenAt);
  if (Number.isNaN(parsed.getTime())) {
    return t('common.lastSeen.justNow');
  }

  const diffSeconds = Math.floor((nowDate.getTime() - parsed.getTime()) / 1000);

  if (diffSeconds < MINUTE_SECONDS) {
    return t('common.lastSeen.justNow');
  }

  if (diffSeconds < HOUR_SECONDS) {
    const minutes = Math.floor(diffSeconds / MINUTE_SECONDS);
    return formatWithUnit(minutes, 'minute', locale, t);
  }

  if (diffSeconds < DAY_SECONDS) {
    const hours = Math.floor(diffSeconds / HOUR_SECONDS);
    return formatWithUnit(hours, 'hour', locale, t);
  }

  if (diffSeconds < MONTH_SECONDS) {
    const days = Math.floor(diffSeconds / DAY_SECONDS);
    return formatWithUnit(days, 'day', locale, t);
  }

  if (diffSeconds < YEAR_SECONDS) {
    const months = Math.floor(diffSeconds / MONTH_SECONDS);
    return formatWithUnit(months, 'month', locale, t);
  }

  const years = Math.floor(diffSeconds / YEAR_SECONDS);
  return formatWithUnit(years, 'year', locale, t);
};

export const useLastSeen = () => {
  const { t, locale } = useI18n();

  return {
    formatLastSeen: (lastSeenAt: Date | string | number | null | undefined, nowDate?: Date) =>
      formatLastSeen(lastSeenAt, {
        nowDate,
        locale: locale.value,
        t: (key, params) => t(key, params),
      }),
  };
};
