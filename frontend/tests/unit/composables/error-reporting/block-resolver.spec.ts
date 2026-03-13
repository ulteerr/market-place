import { describe, expect, it } from 'vitest';
import { resolveReportBlock } from '~/composables/error-reporting/block-resolver';

type FakeElement = {
  tagName: string;
  id: string;
  dataset: Record<string, string>;
  parentElement: FakeElement | null;
  children: FakeElement[];
  closest: (selector: string) => FakeElement | null;
  getAttribute: (name: string) => string | null;
};

const createElement = (options?: {
  tagName?: string;
  id?: string;
  dataset?: Record<string, string>;
  attributes?: Record<string, string>;
  parentElement?: FakeElement | null;
  closestMap?: Record<string, FakeElement | null>;
}): FakeElement => {
  const element: FakeElement = {
    tagName: options?.tagName ?? 'DIV',
    id: options?.id ?? '',
    dataset: options?.dataset ?? {},
    parentElement: options?.parentElement ?? null,
    children: [],
    closest: (selector: string) => {
      if (options?.closestMap && selector in options.closestMap) {
        return options.closestMap[selector] ?? null;
      }

      return null;
    },
    getAttribute: (name: string) => options?.attributes?.[name] ?? null,
  };

  if (element.parentElement) {
    element.parentElement.children.push(element);
  }

  return element;
};

describe('resolveReportBlock', () => {
  it('uses data-block-id as top priority identifier', () => {
    const root = createElement({
      tagName: 'SECTION',
      dataset: { blockId: 'hero' },
    });
    const target = createElement({
      tagName: 'BUTTON',
      parentElement: root,
      closestMap: {
        '[data-block-id]': root,
      },
    });

    const result = resolveReportBlock(target as unknown as HTMLElement);

    expect(result.strategy).toBe('data-block-id');
    expect(result.blockId).toBe('hero');
  });

  it('falls back to id when explicit block attrs are absent', () => {
    const withId = createElement({
      tagName: 'SECTION',
      id: 'catalog-filters',
    });
    const target = createElement({
      tagName: 'SPAN',
      closestMap: {
        '[id]': withId,
      },
    });

    const result = resolveReportBlock(target as unknown as HTMLElement);

    expect(result.strategy).toBe('id');
    expect(result.blockId).toBe('catalog-filters');
  });

  it('falls back to data-test when id is absent', () => {
    const withDataTest = createElement({
      tagName: 'ARTICLE',
      attributes: { 'data-test': 'stats-card' },
    });
    const target = createElement({
      tagName: 'P',
      closestMap: {
        '[data-test]': withDataTest,
      },
    });

    const result = resolveReportBlock(target as unknown as HTMLElement);

    expect(result.strategy).toBe('data-test');
    expect(result.blockId).toBe('stats-card');
  });
});
