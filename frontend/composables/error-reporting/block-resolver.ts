export type BlockResolveResult = {
  blockId: string;
  strategy: 'data-block-id' | 'data-report-block-id' | 'id' | 'data-test' | 'dom-path';
  queryPath: string;
};

const MAX_PATH_DEPTH = 6;

const escapeForSelector = (value: string): string => {
  if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
    return CSS.escape(value);
  }

  return value.replace(/([ !"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1');
};

const buildNodeSelector = (element: HTMLElement): string => {
  if (element.id) {
    return `${element.tagName.toLowerCase()}#${escapeForSelector(element.id)}`;
  }

  const parent = element.parentElement;
  if (!parent) {
    return element.tagName.toLowerCase();
  }

  const siblings = Array.from(parent.children).filter((child) => child.tagName === element.tagName);
  const index = Math.max(1, siblings.indexOf(element) + 1);
  return `${element.tagName.toLowerCase()}:nth-of-type(${index})`;
};

const buildDomPath = (element: HTMLElement): string => {
  const chunks: string[] = [];
  let current: HTMLElement | null = element;
  let depth = 0;

  while (current && depth < MAX_PATH_DEPTH) {
    chunks.unshift(buildNodeSelector(current));
    if (current.id) {
      break;
    }

    current = current.parentElement;
    depth += 1;
  }

  return chunks.join(' > ');
};

export const resolveReportBlock = (target: HTMLElement): BlockResolveResult => {
  const withDataBlockId = target.closest<HTMLElement>('[data-block-id]');
  if (withDataBlockId?.dataset.blockId) {
    return {
      blockId: withDataBlockId.dataset.blockId,
      strategy: 'data-block-id',
      queryPath: buildDomPath(withDataBlockId),
    };
  }

  const withReportId = target.closest<HTMLElement>('[data-report-block-id]');
  if (withReportId?.dataset.reportBlockId) {
    return {
      blockId: withReportId.dataset.reportBlockId,
      strategy: 'data-report-block-id',
      queryPath: buildDomPath(withReportId),
    };
  }

  const withId = target.closest<HTMLElement>('[id]');
  if (withId?.id) {
    return {
      blockId: withId.id,
      strategy: 'id',
      queryPath: buildDomPath(withId),
    };
  }

  const withDataTest = target.closest<HTMLElement>('[data-test]');
  const dataTestValue = withDataTest?.getAttribute('data-test');
  if (withDataTest && dataTestValue) {
    return {
      blockId: dataTestValue,
      strategy: 'data-test',
      queryPath: buildDomPath(withDataTest),
    };
  }

  return {
    blockId: buildDomPath(target),
    strategy: 'dom-path',
    queryPath: buildDomPath(target),
  };
};
