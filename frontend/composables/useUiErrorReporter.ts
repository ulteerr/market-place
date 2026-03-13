import type { BlockResolveResult } from '~/composables/error-reporting/block-resolver';

export type SelectedReportBlock = BlockResolveResult & {
  selectedAt: string;
};

export const useUiErrorReporter = () => {
  const isSelectionMode = useState<boolean>('ui-error-reporter-selection-mode', () => false);
  const selectedBlock = useState<SelectedReportBlock | null>(
    'ui-error-reporter-selected',
    () => null
  );

  const startSelection = () => {
    isSelectionMode.value = true;
  };

  const stopSelection = () => {
    isSelectionMode.value = false;
  };

  const clearSelectedBlock = () => {
    selectedBlock.value = null;
  };

  const setSelectedBlock = (value: BlockResolveResult) => {
    selectedBlock.value = {
      ...value,
      selectedAt: new Date().toISOString(),
    };
  };

  return {
    isSelectionMode,
    selectedBlock,
    startSelection,
    stopSelection,
    clearSelectedBlock,
    setSelectedBlock,
  };
};
