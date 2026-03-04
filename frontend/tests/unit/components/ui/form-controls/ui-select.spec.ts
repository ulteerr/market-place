// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';

describe('UiSelect', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('ref', ref);
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('watch', watch);
    vi.stubGlobal('onMounted', onMounted);
    vi.stubGlobal('onBeforeUnmount', onBeforeUnmount);
    vi.stubGlobal('useId', () => 'test');
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('sets combobox/listbox accessibility attributes for options', async () => {
    const wrapper = mount(UiSelect, {
      props: {
        modelValue: 'done',
        options: [
          { value: 'todo', label: 'To do' },
          { value: 'done', label: 'Done' },
        ],
      },
    });

    const input = wrapper.get('input[data-ui-select-input]');
    await input.trigger('focus');

    expect(input.attributes('role')).toBe('combobox');
    expect(input.attributes('aria-controls')).toBe('ui-select-test-listbox');
    expect(input.attributes('aria-expanded')).toBe('true');

    const options = wrapper.findAll('[role="option"]');
    expect(options).toHaveLength(2);
    expect(options[0].attributes('aria-selected')).toBe('false');
    expect(options[1].attributes('aria-selected')).toBe('true');
  });

  it('navigates options with keyboard and skips disabled items', async () => {
    const wrapper = mount(UiSelect, {
      props: {
        options: [
          { value: 'alpha', label: 'Alpha' },
          { value: 'beta', label: 'Beta', disabled: true },
          { value: 'gamma', label: 'Gamma' },
        ],
      },
    });

    const input = wrapper.get('input[data-ui-select-input]');

    await input.trigger('keydown', { key: 'ArrowDown' });
    expect(input.attributes('aria-activedescendant')).toBe('ui-select-test-option-alpha');

    await input.trigger('keydown', { key: 'ArrowDown' });
    expect(input.attributes('aria-activedescendant')).toBe('ui-select-test-option-gamma');

    await input.trigger('keydown', { key: 'ArrowUp' });
    expect(input.attributes('aria-activedescendant')).toBe('ui-select-test-option-alpha');

    await input.trigger('keydown', { key: 'Enter' });

    const emitted = wrapper.emitted('update:modelValue');
    expect(emitted).toBeTruthy();
    expect(emitted?.at(-1)).toEqual(['alpha']);
  });
});
