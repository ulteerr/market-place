import type { Component } from 'vue';
import { ref } from 'vue';

/**
 * Возвращает render-функцию для Storybook, связывающую args с v-model (modelValue).
 * Использовать в meta для компонентов с v-model, чтобы Controls и состояние компонента синхронизировались.
 * Параметр args типизирован как any, чтобы тип был совместим с ArgsStoryFn (ComponentPropsAndSlots не имеет index signature).
 */
export function createVModelRender(
  component: Component,
  componentName: string
): (args: any) => {
  components: Record<string, Component>;
  setup: () => { args: any; model: ReturnType<typeof ref> };
  template: string;
} {
  return (args: any) => ({
    components: { [componentName]: component },
    setup() {
      const model = ref(args.modelValue);
      return { args, model };
    },
    template: `<${componentName} v-bind="args" v-model="model" />`,
  });
}
